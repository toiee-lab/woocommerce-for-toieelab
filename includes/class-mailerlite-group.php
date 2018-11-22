<?php
	
/**
 * Mailerlite のグループと連動させるための機能を提供する
 *
 *
 *
 */

class Toiee_Mailerlite_Group {
	
	var $apikey;

	public function __construct() {
		
		
		//グループ選択を追加（通常商品）
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'create_ml_select' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_ml_select' ) );
		
		//グループ選択を追加（バリエーション）
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'create_ml_select_variation'), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_ml_select_variation'), 10, 2 );
		
		//設定を追加
		add_filter( 'woocommerce_get_sections_advanced', array($this, 'ml_group_section') );
		add_filter( 'woocommerce_get_settings_advanced', array($this, 'ml_group_setting'), 10, 2 );

		//管理画面設定
		if( is_admin() ){
	        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'page_init' ) );
		}
		
		//購入時などに使う
		add_action('woocommerce_order_status_changed', array( $this, 'update_mailerlite_group' ) , 10, 3);
		
		//! TODO Subscription への対応をする
		
	}
	
	private function get_key(){
		
		if( is_null( $this->apikey ) ){
			$this->apikey = get_option( 'woocommerce_mailerlite_group_apikey' );
		}
		
		return $this->apikey;
	}
	
	
	/* オーダーの状態が変わったことを検知して、行動する */
	public function update_mailerlite_group( $order_id, $old_status, $new_status ) {

		if( $new_status == "completed" ) { 		// completed なら登録を実行
            $this->add_group( $order_id );
		}
		else if($old_status == 'completed' ) { // 削除を実行
			$this->delete_group( $order_id );
		}
	}


    /**
     * オーダーからユーザーを検索して、オーダーに紐づけられているMaileliteのグループに登録します。
     * @param $order_id
     * @param bool $add
     * @return array
     * @throws \MailerLiteApi\Exceptions\MailerLiteSdkException
     * @throws Exception
     */
	public function add_delete_group( $order_id , $add = true ) {
		
		
		$order = wc_get_order( $order_id );

		// user の更新
		$user_id    = $order->get_customer_id();
		$subscriber = $this->update_user( $user_id );

		// 商品に対応したグループの登録				
		foreach ( $order->get_items() as $item_id => $item_values ) {

            // 準備
            $product_id = $item_values->get_product_id();
            $data = $item_values->get_data();

            // 登録先を探す (variation を考慮）
            if (isset($data['variation_id']) && $data['variation_id'] != 0) {  //variation なら
                $gid = get_post_meta($data['variation_id'], '_variation_mailerlite_group', true);
            } else { //通常なら
                $gid = get_post_meta($product_id, '_mailerlite_group', true);
            }

            // 登録する
            if ($gid) {
                $groupsApi = (new \MailerLiteApi\MailerLite($this->get_key()))->groups();

                if( $add ) { //追加
                    $subscriber = $groupsApi->addSubscriber($gid, $subscriber); // returns added subscriber
                }
                else{ //削除
                    $subscriber = $groupsApi->removeSubscriber($gid, $subscriber->id); // return empty
                }
                return $subscriber;
            }
        }

        return false;
	}

	public function add_group( $order_id ) {
        try {
            $this->add_delete_group($order_id);
        } catch (\MailerLiteApi\Exceptions\MailerLiteSdkException $e) {
        } catch (Exception $e) {
        }
    }
    /**
     * @param $order_id
     */
	public function delete_group( $order_id ) {
        try {
            $this->add_delete_group($order_id, false);
        } catch (\MailerLiteApi\Exceptions\MailerLiteSdkException $e) {
        } catch (Exception $e) {
        }
    }

    /**
     * ユーザーをアップデートする（必要なら追加する）
     * subscriber を返す
     * @throws Exception
     */
	function update_user( $user_id ) {
		// get wordpress user data
		$user_data = get_userdata( $user_id );
		$user_meta_data = get_metadata( 'user', $user_id, '', true );
		
		// generate data for mailerlite
		$email = $user_data->user_email;
		$name = $user_meta_data['first_name'][0];
		$fields = array(
			'last_name'	=> $user_meta_data['last_name'][0],
//			'company'   => $user_meta_data['last_name'][0],
			'country'   => $user_meta_data['billing_country'][0],
			'city'      => $user_meta_data['billing_city'][0],
			'phone'     => $user_meta_data['billing_phone'][0],
			'state'     => $user_meta_data['billing_state'][0],
			'zip'       => $user_meta_data['billing_postcode'][0]
		);		
		//! apply_filter とか設計したいなー。追加でデータをマッピングできる

		// user check
		$subscribersApi = (new \MailerLiteApi\MailerLite( $this->get_key() ))->subscribers();
        $subscriber = $subscribersApi->find( $email );

		if( isset( $subscriber->error ) ) { // ユーザーがいないなら、登録
			$subscriber = [
			  'email' => $email,
			  'name' => $name,
			  'fields' => $fields
			];
			
			$addedSubscriber = $subscribersApi->create($subscriber);
			
			return $addedSubscriber;
		}
		else { // 更新する
			
			$subscriberEmail = $email;
			$subscriberData = [
			  'fields' => $fields
			];
			
			$subscriber = $subscribersApi->update($subscriberEmail, $subscriberData); // returns object of updated subscriber
			
			return $subscriber;
		}	
	}
	
	
	
	/* ----------------------------------------------------------------------------- 
		Simple Product に追加、保存
		
	--------------------------------------------------------------------------------	*/	
	public function create_ml_select() {
		
		// options から値を取得しておく
		$groups = get_option( 'woocommerce_mailerlite_group_list' , false);
		asort( $groups );
				
		// 表示
		woocommerce_wp_select( 
			array( 
				'id'      => '_mailerlite_group', 
				'label'   => __( 'MailerLiteグループ', 'wc-ext-toiee' ), 
				'options' => $groups
			)
		);
		
		echo '<p><a href="edit.php?post_type=product&page=update-mlg">グループリストの更新はこちら</a></p>';

	}
	
	public function save_ml_select( $post_id ) {
		$woocommerce_select = $_POST['_mailerlite_group'];
		update_post_meta( $post_id, '_mailerlite_group', esc_attr( $woocommerce_select ) );
	}

	
	/* ----------------------------------------------------------------------------- 
		Variation Product に追加、保存
		
	--------------------------------------------------------------------------------	*/	
	/**
	*
	* Ref : https://gist.github.com/maddisondesigns/e7ee7eef7588bbba2f6d024a11e8875a
	*
	*/
	public function create_ml_select_variation( $loop, $variation_data, $variation ) {
		
		// options から値を取得
		$value = get_post_meta( $variation->ID, '_variation_mailerlite_group', '' );
		$groups = get_option( 'woocommerce_mailerlite_group_list' , false);
		asort( $groups );

		// 表示		
		woocommerce_wp_select( 
			array( 
				'id'      => '_variation_mailerlite_group[' .$variation->ID. ']', 
				'label'   => __( 'MailerLiteグループ', 'woocommerce' ), 
				'options' => $groups,
				'value' => $value,
			)
		);
	}
	
	public function save_ml_select_variation( $variation_id ) {
		
		$woocommerce_select = $_POST['_variation_mailerlite_group'][ $variation_id ];

		if ( ! empty( $woocommerce_select ) ) {
	        update_post_meta( $variation_id, '_variation_mailerlite_group', esc_attr( $woocommerce_select ) );
		} else {
			delete_post_meta( $variation_id, '_variation_mailerlite_group' );
		}
	}
	
	
	/*
	* 設定ページ
	*/
	public function ml_group_section( $sections ) { 
		$sections['mailerlite_group'] = __( 'MailerLiteグループ', 'wc-ext-toiee');
		return $sections;
	}
		
	public function ml_group_setting( $settings, $current_section ) {
		
		if( 'mailerlite_group' === $current_section ) {
			
			
			$my_settings = array(
				array(
					'title'     => __( 'Mailerlite Group設定', 'wc-ext-toiee' ),
					'type'      => 'title',
					'id'        => 'mlg_setting_title',
				),
				array(
					'id'       => 'woocommerce_mailerlite_group_apikey',
					'type'     => 'text',
					'title'    => __( 'Mailerlite APIキー', 'wc-ext-toiee' ),
					'default'  => '',
					'desc'     => __( 'MailerLiteにて取得してください', 'wc-ext-toiee' ),
					'desc_tip' => true,
				),
				array('type' => 'sectionend', 'id' => 'test-options'),
			);
			
			return $my_settings;
		}
		else {
			return $settings;
		}
	}
	
	
	
	
	/* ----------------------------------------------------------
		管理ページ作成
	------------------------------------------------------------- */
    public function add_plugin_page()
    {
		add_submenu_page( 
			'edit.php?post_type=product',
			'MalierLiteグループ設定',
			'MalierLiteグループ設定', 
			'administrator',
			'update-mlg',
			array( $this, 'create_admin_page' )
		);
    }

    /**
     * @throws \MailerLiteApi\Exceptions\MailerLiteSdkException
     */
    public function create_admin_page() {
	    
        if ( isset($_POST['update_mlg'])) {
            check_admin_referer('update_options');
                        
            //apiを取得
   			$apikey = get_option( 'woocommerce_mailerlite_group_apikey' );
   			
   			//問い合わせ
   			$mailerliteClient = new \MailerLiteApi\MailerLite( $apikey );
   			$groupsApi = $mailerliteClient->groups();
   			$allGroups = $groupsApi->get();

   			//配列にする
   			$items = $allGroups->toArray();
   			$groups = array();
   			$groups[0] = '---';
   			foreach( $items as $group ) {
	   			$groups[ $group->id ] = $group->name;
   			}
            
            //optionsに保存する
			update_option( 'woocommerce_mailerlite_group_list', $groups, 'no');
			update_option( 'woocommerce_mailerlite_group_list_modified', date("Y-m-d H:i:s"), 'no' );
        }
        
		$groups = get_option( 'woocommerce_mailerlite_group_list' , false);
		$modified_date = get_option( 'woocommerce_mailerlite_group_list_modified', 'なし');
		$text = '';
		asort($groups);
		foreach($groups as $id => $name ) {
			$text .= "{$name} ({$id})\n";
		}
?>	    
        <div class="wrap">

            <h2>Mailerliteグループの取得</h2>           
            <p>Mailerlite に問い合わせて、グループを取得します。<br>
	            ここで取得したグループ一覧を、各商品にて登録できます。
            </p>
            <p><a href="admin.php?page=wc-settings&tab=advanced&section=mailerlite_group">Mailerlite APIの設定はこちら</a></p>
	           
            <form method="post" action="edit.php?post_type=product&page=update-mlg">
	            <?php wp_nonce_field('update_options'); ?>
            	<input type="hidden" name="update_mlg" value="update_mlg">
            	<?php submit_button( "グループの更新を実行する" ); ?>
            </form>
			<p>前回の更新日時: <?php echo $modified_date; ?></p>
            
            <h2>グループ一覧</h2>
            <textarea readonly="readonly" style="width: 100%" rows="10"><?php echo $text; ?></textarea>
        </div>
        
<?php	    
		$order_id = '15740'; //variation を買ったオーダーID
		$order_id = '15730'; // シンプルな商品を購入したID

		//! テスト
        //$this->add_group( '15740' );
        //$this->delete_group( '15740' );
  
    }
    
    public function page_init() {
	    
    }
}