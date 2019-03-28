<?php

/**
 * Mailerlite のグループと連動させるための機能を提供する
 */

class Toiee_Mailerlite_Group {


	var $apikey;

	public function __construct() {
		// 設定タブ（連携）を追加
		add_filter( 'plugins_loaded', array( $this, 'init_integration' ) );

		if ( $this->get_key() ) {

			// グループ選択を追加（通常商品）
			add_action( 'woocommerce_product_options_general_product_data', array( $this, 'create_ml_select' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_ml_select' ) );

			// グループ選択を追加（バリエーション）
			add_action(
				'woocommerce_product_after_variable_attributes',
				array(
					$this,
					'create_ml_select_variation',
				),
				10,
				3
			);
			add_action( 'woocommerce_save_product_variation', array( $this, 'save_ml_select_variation' ), 10, 2 );

			// 管理画面設定
			if ( is_admin() ) {
				   add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
				   add_action( 'admin_init', array( $this, 'page_init' ) );
			}

			// 注文の状態変化を検知する
			add_action( 'woocommerce_order_status_changed', array( $this, 'update_mailerlite_group' ), 10, 3 );
			add_action(
				'woocommerce_subscription_status_updated',
				array(
					$this,
					'update_mailerlite_group_subscription',
				),
				10,
				3
			);

			// ユーザーのプロフィール設定
			add_action( 'woocommerce_save_account_details', array( $this, 'update_user' ), 10, 1 );
			add_action( 'woocommerce_checkout_update_user_meta', array( $this, 'update_user' ), 10, 1 );
			add_action( 'woocommerce_customer_save_address', array( $this, 'update_user' ), 10, 2 );

			add_action( 'personal_options_update', array( $this, 'update_user' ), 10, 1 );
			add_action( 'edit_user_profile_update', array( $this, 'update_user' ), 10, 1 );
			add_action( 'user_register', array( $this, 'update_user' ), 10, 1 ); // ユーザーの作成
		}
	}

	private function get_key() {
		if ( is_null( $this->apikey ) ) {
			$settings     = get_option( 'woocommerce_integration-mailerlite-group_settings' );
			$this->apikey = ( isset( $settings['api_key'] ) ) ? $settings['api_key'] : false;
		}
		return $this->apikey;
	}

	/* オーダーの状態が変わったことを検知して、行動する */
	public function update_mailerlite_group( $order_id, $old_status, $new_status ) {

		if ( $new_status == 'completed' ) {         // completed なら登録を実行
			$this->add_group( $order_id );
		} elseif ( $old_status == 'completed' ) { // 削除を実行
			$this->delete_group( $order_id );
		}
	}

	/**
	 * サブスクリプションの状態変化に対して、Mailerliteのグループを設定する
	 *
	 * @param $subscription
	 * @param $new_status
	 * @param $old_status
	 */
	public function update_mailerlite_group_subscription( $subscription, $new_status, $old_status ) {

		$order_id = $subscription->get_order_number();

		// istallement の場合、expire を active として処理する
		$order = wc_get_order( $order_id );
		foreach ( $order->get_items() as $item_id => $item_values ) {
			$product_id  = $item_values->get_product_id();
			$installment = get_post_meta( $product_id, '_installment_subscription', true );
			if ( $installment && $new_status == 'expired' ) {
				$new_status = 'active';
				break;
			}
		}

		if ( $new_status == 'active' ) {
			$this->add_group( $order_id );
		} elseif ( $old_status == 'active' ) {
			$this->delete_group( $order_id );
		}
	}


	/**
	 * オーダーからユーザーを検索して、オーダーに紐づけられているMaileliteのグループに登録します。
	 *
	 * @param  $order_id
	 * @param  bool     $add
	 * @return array
	 * @throws \MailerLiteApi\Exceptions\MailerLiteSdkException
	 * @throws Exception
	 */
	public function add_delete_group( $order_id, $add = true ) {

		$order = wc_get_order( $order_id );

		// user の更新
		$user_id    = $order->get_customer_id();
		$subscriber = $this->update_user( $user_id );

		// 商品に対応したグループの登録
		foreach ( $order->get_items() as $item_id => $item_values ) {

			// 準備
			$product_id = $item_values->get_product_id();
			$data       = $item_values->get_data();

			// 登録先を探す (variation を考慮）
			if ( isset( $data['variation_id'] ) && $data['variation_id'] != 0 ) {  // variation なら
				$gid = get_post_meta( $data['variation_id'], '_variation_mailerlite_group', true );
			} else { // 通常なら
				$gid = get_post_meta( $product_id, '_mailerlite_group', true );
			}

			// 登録する
			if ( $gid ) {
				$groupsApi = ( new \MailerLiteApi\MailerLite( $this->get_key() ) )->groups();

				if ( $add ) { // 追加
					$subscriber = $groupsApi->addSubscriber( $gid, $subscriber ); // returns added subscriber
				} else { // 削除
					$subscriber = $groupsApi->removeSubscriber( $gid, $subscriber->id ); // return empty
				}
				return $subscriber;
			}
		}

		return $subscriber;
	}

	public function add_group( $order_id ) {
		try {
			$this->add_delete_group( $order_id );
		} catch ( \MailerLiteApi\Exceptions\MailerLiteSdkException $e ) {
			// TODO
		} catch ( Exception $e ) {
			// TODO
		}
	}
	/**
	 * @param $order_id
	 */
	public function delete_group( $order_id ) {
		try {
			$this->add_delete_group( $order_id, false );
		} catch ( \MailerLiteApi\Exceptions\MailerLiteSdkException $e ) {
			// TODO
		} catch ( Exception $e ) {
			// TODO
		}
	}

	/**
	 * ユーザーをアップデートする（必要なら追加する）
	 * subscriber を返す
	 *
	 * @param  $user_id
	 * @return array
	 * @throws \MailerLiteApi\Exceptions\MailerLiteSdkException
	 */
	function update_user( $user_id, $load_address = '' ) {
		// get WordPress user data
		$user_data      = get_userdata( $user_id );
		$user_meta_data = get_metadata( 'user', $user_id, '', true );

		// generate data for mailerlite
		$email  = $user_data->user_email;
		$name   = $user_meta_data['first_name'][0];
		$fields = array(
			'last_name' => $user_meta_data['last_name'][0],
			// 'company'   => $user_meta_data['last_name'][0],
			'country'   => $user_meta_data['billing_country'][0],
			'city'      => $user_meta_data['billing_city'][0],
			'phone'     => $user_meta_data['billing_phone'][0],
			'state'     => $user_meta_data['billing_state'][0],
			'zip'       => $user_meta_data['billing_postcode'][0],
		);
		// ! apply_filter とか設計したいなー。追加でデータをマッピングできる
		// user check
		$subscribersApi = ( new \MailerLiteApi\MailerLite( $this->get_key() ) )->subscribers();
		try {
			$subscriber = $subscribersApi->find( $email );
		} catch ( Exception $e ) {
			// TODO ユーザーがアップデートできなかったときの処理
		}

		if ( isset( $subscriber->error ) ) { // ユーザーがいないなら、登録
			$subscriber = [
				'email'  => $email,
				'name'   => $name,
				'fields' => $fields,
			];

			// default group に追加
			if ( $group_id = get_option( 'woocommerce_mailerlite_group_default', false ) ) {
				$MailerLiteApi = ( new \MailerLiteApi\MailerLite( $this->get_key() ) );
				$groupsApi     = $MailerLiteApi->groups();
				// グループに登録
				$options         = [
					'resubscribe'    => true,
					'autoresponders' => true, // send autoresponders for successfully imported subscribers
				];
				$addedSubscriber = $groupsApi->importSubscribers( $group_id, $subscriber, $options );
			} else {
				$addedSubscriber = $subscribersApi->create( $subscriber );
			}

			return $addedSubscriber;
		} else { // 更新する

			 $subscriberEmail = $email;
			 $subscriberData  = [
				 'name'   => $name,
				 'fields' => $fields,
			 ];

			 $subscriber = $subscribersApi->update( $subscriberEmail, $subscriberData ); // returns object of updated subscriber

			 return $subscriber;
		}
	}



	/*
	 -----------------------------------------------------------------------------
	Simple Product に追加、保存

	--------------------------------------------------------------------------------    */
	public function create_ml_select() {

		// options から値を取得しておく
		$groups = get_option( 'woocommerce_mailerlite_group_list', false );
		asort( $groups );

		// 表示
		woocommerce_wp_select(
			array(
				'id'      => '_mailerlite_group',
				'label'   => __( 'MailerLiteグループ', 'wc-ext-toiee' ),
				'options' => $groups,
			)
		);

		echo '<p><a href="' . admin_url( 'edit.php?post_type=product&page=update-mlg' ) . '">グループリストの更新はこちら</a></p>';

	}

	public function save_ml_select( $post_id ) {
		$woocommerce_select = $_POST['_mailerlite_group'];
		update_post_meta( $post_id, '_mailerlite_group', esc_attr( $woocommerce_select ) );
	}


	/*
	 -----------------------------------------------------------------------------
	Variation Product に追加、保存

	--------------------------------------------------------------------------------    */
	/**
	 * Ref : https://gist.github.com/maddisondesigns/e7ee7eef7588bbba2f6d024a11e8875a
	 *
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 */
	public function create_ml_select_variation( $loop, $variation_data, $variation ) {

		// options から値を取得
		$value  = get_post_meta( $variation->ID, '_variation_mailerlite_group', '' );
		$groups = get_option( 'woocommerce_mailerlite_group_list', false );
		asort( $groups );

		// 表示
		woocommerce_wp_select(
			array(
				'id'      => '_variation_mailerlite_group[' . $variation->ID . ']',
				'label'   => __( 'MailerLiteグループ', 'woocommerce' ),
				'options' => $groups,
				'value'   => $value,
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
	 ----------------------------------------------------------
	管理ページ作成
	------------------------------------------------------------- */
	public function add_plugin_page() {
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
	 */
	public function create_admin_page() {

		$notice = '';

		// mailerlite group をアップデート
		if ( isset( $_POST['do_action'] )
			&& isset( $_POST['_wpnonce'] )
			&& wp_verify_nonce( $_POST['_wpnonce'], 'update_options' )
		) {

			check_admin_referer( 'update_options' );

			switch ( $_POST['do_action'] ) {

				case 'update_mlg':
					$ret = $this->update_mlg();
					break;
				case 'update_default':
					$ret = $this->update_default( $_POST['group_id'] );
					break;
				case 'update_users':
					$ret = $this->update_users_to_mailerlite();
					break;
				case 'update_product':
					$ret = $this->update_product_to_mailerlite( $_POST['product_id'] );
					break;
				case 'update_all':
					$ret = $this->update_all_to_mailerlite();
					break;
			}

			if ( $ret != '' ) {
				$notice = '<div class="notice notice-success is-dismissible">' .
						  '  <p>' . $ret . '</p>' .
						  '</div>';
			}
		}

		?>
		<div class="wrap">

			<?php echo $notice; ?>

			<h2>Mailerliteグループ設定</h2>
			<?php

			// グループリストを取得
			$groups        = get_option( 'woocommerce_mailerlite_group_list', false );
			$modified_date = get_option( 'woocommerce_mailerlite_group_list_modified', 'なし' );
			$text          = '';
			asort( $groups );
			foreach ( $groups as $id => $name ) {
				$text .= "{$name} ({$id})\n";
			}

			?>
			<h3>グループ一覧を取得</h3>
			<p>Mailerlite に問い合わせて、グループを取得します。ここで取得したグループ一覧を、各商品にて登録できます。<br>
			<a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=integration&section=integration-mailerlite-group' ); ?>">Mailerlite APIの設定はこちら</a></p>
			<textarea readonly="readonly" style="width: 100%" rows="10" title="mailerlite group list"><?php echo $text; ?></textarea>
			<label>前回の更新日時: <?php echo $modified_date; ?></label>

			<form method="post" action="<?php echo admin_url( 'edit.php?post_type=product&page=update-mlg' ); ?>">
				<?php wp_nonce_field( 'update_options' ); ?>
				<input type="hidden" name="do_action" value="update_mlg">
				<?php submit_button( 'グループの更新を実行する' ); ?>
			</form>


			<h3>デフォルトグループを設定</h3>
			<p>無料登録ユーザーを追加するグループを指定してください。</p>
			<form method="post" action="<?php echo admin_url( 'edit.php?post_type=product&page=update-mlg' ); ?>">
				<?php wp_nonce_field( 'update_options' ); ?>
				<input type="hidden" name="do_action" value="update_default">
				<select name="group_id">
					<?php
					$default_gid = get_option( 'woocommerce_mailerlite_group_default', null );
					foreach ( $groups as $id => $name ) {
						$selected = ( $id == $default_gid ) ? ' selected="selected"' : '';
						echo '<option value="' . $id . '"' . $selected . '>' . $name . '</option>' . "\n";
					}
					?>
				</select>
				<?php submit_button( 'デフォルトグループを更新する' ); ?>
			</form>

			<br>

			<hr>

			<h1>既存ユーザーを同期</h1>

			<h2>顧客リスト</h2>
			<p>このWordPressのユーザーを登録します。登録時に自動で
				「wc import 2018-11-18 11:08」のようなグループを作成し、インポートします。デフォルトグループには、追加しません。
				必要に応じて、Mailerlite上でグループ登録を行なってください。</p>
			<form method="post" action="<?php echo admin_url( 'edit.php?post_type=product&page=update-mlg' ); ?>">
				<?php wp_nonce_field( 'update_options' ); ?>
				<input type="hidden" name="do_action" value="update_users">
				<?php submit_button( 'ユーザーを同期する' ); ?>
			</form>


			<br>
			<hr>

			<h2>特定の商品を購入したユーザーを取得</h2>
			<p>指定した商品を購入しているユーザーを取得します。</p>
			<form method="post" action="<?php echo admin_url( 'edit.php?post_type=product&page=update-mlg' ); ?>">
				<?php wp_nonce_field( 'update_options' ); ?>
				<input type="hidden" name="do_action" value="update_product">
				<select name="product_id">
					<?php
					$product_list = $this->get_products_related_mailerlite();
					foreach ( $product_list as $option ) {
						echo '<option value="' . $option['post_id'] . '">' . $option['product_name'] . ' (id:' . $option['post_id'] . ')</option>';
					}
					?>
				</select>
				<?php submit_button( '商品別ユーザー登録を実行' ); ?>
			</form>


			<br>
			<hr>

			<h2>全ての商品を登録</h2>
			<p>Mailerliteグループが設定されている全ての商品のユーザーを登録します。</p>
			<form method="post" action="<?php echo admin_url( 'edit.php?post_type=product&page=update-mlg' ); ?>">
				<?php wp_nonce_field( 'update_options' ); ?>
				<input type="hidden" name="do_action" value="update_all">
				<?php submit_button( '全商品のユーザーの登録を実行' ); ?>
			</form>


		</div>
		
		<?php
	}

	public function page_init() {

	}

	/**
	 * Mailerlite Groupが設定されている商品一覧を取得する
	 *
	 * @return array
	 */
	public function get_products_related_mailerlite() {
		$posts        = get_posts(
			array(
				'post_type'      => 'product',
				'posts_per_page' => - 1,
				'post_status'    => 'publish,private,draft',
			)
		);
		$product_list = array();
		foreach ( $posts as $key => $post ) {

			$ret = $this->get_group_id_by_product_id( $post->ID );
			if ( is_array( $ret ) ) {
				$product_list = array_merge( $product_list, $ret );
			}
		}

		return $product_list;
	}

	public function get_group_id_by_product_id( $product_id ) {

		$group_ids = array();

		$product = wc_get_product( $product_id );
		$type    = $product->get_type();

		if ( $type == 'variable' || $type == 'variable-subscription' ) { // variation を取得して設定する
			$variations = $product->get_available_variations();
			foreach ( $variations as $v ) {
				$post_id = $v['variation_id'];
				$mlg_id  = get_post_meta( $post_id, '_variation_mailerlite_group', true );
				$atts    = array_shift( $v['attributes'] );
				$label   = wc_attribute_label( $atts, $product );
				if ( $mlg_id != '' ) {
					$group_ids[] = [
						'group_id'     => $mlg_id,
						'post_id'      => $post_id,
						'product_name' => $product->get_name() . ' - ' . $label,
						'product_type' => 'variation',
					];
				}
			}
		} elseif ( $type == 'subscription_variation' ) { // variable-subscription の product の1つが指定された場合
			$mlg_id = get_post_meta( $product_id, '_variation_mailerlite_group', true );
			if ( $mlg_id != '' ) {
				$group_ids[] = [
					'group_id'     => $mlg_id,
					'post_id'      => $product->get_id(),
					'product_name' => $product->get_name(),
					'product_type' => 'subscription_variation',
				];
			}
		} else {
			$mlg_id = get_post_meta( $product->get_id(), '_mailerlite_group', true );
			if ( $mlg_id != '' ) {
				$group_ids[] = [
					'group_id'     => $mlg_id,
					'post_id'      => $product->get_id(),
					'product_name' => $product->get_name(),
					'product_type' => 'simple',
				];
			}
		}

		if ( count( $group_ids ) ) {
			return $group_ids;
		} else {
			return false;
		}
	}

	public function update_mlg() {
		// apiを取得
		$apikey = $this->get_key();

		// 問い合わせ
		$mailerliteClient = new \MailerLiteApi\MailerLite( $apikey );
		$groupsApi        = $mailerliteClient->groups();
		$allGroups        = $groupsApi->get();

		// 配列にする
		$items     = $allGroups->toArray();
		$groups    = array();
		$groups[0] = '---';
		foreach ( $items as $group ) {
			$groups[ $group->id ] = $group->name;
		}

		// optionsに保存する
		update_option( 'woocommerce_mailerlite_group_list', $groups, 'no' );
		update_option( 'woocommerce_mailerlite_group_list_modified', date( 'Y-m-d H:i:s' ), 'no' );

		return 'グループ一覧を取得、更新しました';

	}

	public function update_default( $group_id ) {

		// optionsに保存する
		update_option( 'woocommerce_mailerlite_group_default', $group_id, 'no' );
		return 'デフォルトグループ更新しました';

	}


	public function get_subscriber_array( $user, $user_meta_data ) {
		$subscriber = [
			'email'  => $user->user_email,
			'fields' => [
				'last_name' => $user_meta_data['last_name'][0],
				// 'company'   => $user_meta_data['last_name'][0],
				'country'   => $user_meta_data['billing_country'][0],
				'city'      => $user_meta_data['billing_city'][0],
				'phone'     => $user_meta_data['billing_phone'][0],
				'state'     => $user_meta_data['billing_state'][0],
				'zip'       => $user_meta_data['billing_postcode'][0],
			],
		];
		return $subscriber;
	}

	public function update_users_to_mailerlite() {

		// 登録するユーザーリストの作成
		$users          = get_users();
		$ml_subscribers = array();
		foreach ( $users as $user ) {
			$user_meta_data   = get_metadata( 'user', $user->ID, '', true );
			$ml_subscribers[] = $this->get_subscriber_array( $user, $user_meta_data );
		}

		// グループの作成
		$group_name    = 'import from wc ' . date( 'Y-m-d H:i' );
		$MailerLiteApi = ( new \MailerLiteApi\MailerLite( $this->get_key() ) );
		$groupsApi     = $MailerLiteApi->groups();
		$groups        = $groupsApi->create( [ 'name' => $group_name ] );

		// グループに登録
		$groupId = $groups->id;
		$options = [
			'resubscribe'    => false,
			'autoresponders' => false, // send autoresponders for successfully imported subscribers
		];

		$addedSubscribers = $groupsApi->importSubscribers( $groupId, $ml_subscribers, $options );

		return 'グループ名: ' . $group_name . 'で、ユーザーを追加しました。結果は、Mailerliteでご確認ください。';
	}

	public function update_product_to_mailerlite( $product_id ) {
		/*
		 * 1.商品を購入したユーザーIDのリストを作る
		 * 2.IDからユーザー情報を作成する
		 * 3.グループを500ずつに分ける
		 * 4.指定されたグループに登録する
		 *
		 */

		// 商品に応じて、検索する
		global $wpdb;
		$wpre    = $wpdb->prefix;
		$product = wc_get_product( $product_id );
		$type    = $product->get_type();

		$product_id_first = $product_id;  // $product_id を親で上書きすることがあるので、バックアップ

		switch ( $type ) {
			case 'variation':
				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT meta_value
FROM   {$wpre}postmeta
WHERE  post_id IN (
  SELECT order_id
  FROM {$wpre}woocommerce_order_items
  WHERE order_item_id IN (
    SELECT order_item_id
    FROM {$wpre}woocommerce_order_itemmeta
    WHERE meta_key = '_variation_id' AND meta_value = '%s'
  )
  AND order_item_type = 'line_item'
)
AND meta_key = '_customer_user'",
						$product_id
					),
					ARRAY_A
				);
				break;

			case 'subscription_variation':
				$product_id = $product->get_parent_id();
			case 'subscription':
				$installment = get_post_meta( $product_id, '_installment_subscription', true );
				if ( $installment ) {
					$status_condition = "( post_status = 'wc-active' OR post_status = 'wc-expired' )";
				} else {
					$status_condition = "post_status = 'wc-active'";
				}

				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT meta_value
FROM wp_postmeta
WHERE post_id IN (
 SELECT ID
 FROM {$wpre}posts
 WHERE ID IN (
   SELECT order_id
   FROM {$wpre}woocommerce_order_items
   WHERE order_item_id IN (
     SELECT order_item_id
     FROM {$wpre}woocommerce_order_itemmeta
     WHERE meta_key = '_product_id' AND meta_value = '%s'
   )
  AND order_item_type = 'line_item'
 )
AND post_type = 'shop_subscription' AND {$status_condition}
)
AND meta_key = '_customer_user'",
						$product_id
					),
					ARRAY_A
				);
				break;

			default:
				$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT meta_value
FROM   {$wpre}postmeta
WHERE  post_id IN (
  SELECT order_id
  FROM {$wpre}woocommerce_order_items
  WHERE order_item_id IN (
    SELECT order_item_id
    FROM {$wpre}woocommerce_order_itemmeta
    WHERE meta_key = '_product_id' AND meta_value = '%s'
  )
  AND order_item_type = 'line_item'
)
AND meta_key = '_customer_user'",
						$product_id
					),
					ARRAY_A
				);

		}

		// 登録するユーザー一覧を作る
		$subscribers      = array();
		$subscribers_hash = array();
		$i                = 0;
		foreach ( $result as $id ) {
			$user           = get_user_by( 'id', $id['meta_value'] );
			$user_meta_data = get_metadata( 'user', $user->ID, '', true );
			$subscribers[]  = $this->get_subscriber_array( $user, $user_meta_data );

			$subscribers_hash[ $user->user_email ] = $user->ID;
		}

		// Mailerlite Group インスタンスを作成
		$MailerLiteApi = ( new \MailerLiteApi\MailerLite( $this->get_key() ) );
		$groupsApi     = $MailerLiteApi->groups();

		// グループの取得
		$ret = $this->get_group_id_by_product_id( $product_id_first );
		if ( isset( $ret[0] ) ) {
			$group_id = $ret[0]['group_id'];
		} else {
			return 'グループが登録されていませんでした。';
		}

		// 存在しないユーザーをグループから削除
		$groupSubscribers = $groupsApi->getSubscribers( $group_id );
		$deleted          = array();
		foreach ( $groupSubscribers as $single_sub ) {
			$email = $single_sub->email;
			if ( ! isset( $subscribers_hash[ $email ] ) ) { // 現在のユーザーの中にいない
				// 削除
				$groupsApi->removeSubscriber( $group_id, $single_sub->id );
				$deleted[] = $single_sub;
			}
		}

		// グループに登録
		$options          = [
			'resubscribe'    => false,
			'autoresponders' => false, // send autoresponders for successfully imported subscribers
		];
		$addedSubscribers = $groupsApi->importSubscribers( $group_id, $subscribers, $options );

		return '商品別のグループ登録が完了しました。';
	}

	public function update_all_to_mailerlite() {

		$product_list = $this->get_products_related_mailerlite();
		foreach ( $product_list as $p ) {
			$this->update_product_to_mailerlite( $p['post_id'] );
		}
	}

	public function init_integration( $integrations ) {

		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Integration' ) ) {
			// Include our integration class.
			include_once 'class-wc-integration-tab.php';
			// Register the integration.
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
		} else {
			// throw an admin error if you like
		}

	}

	public function add_integration( $integrations ) {
		$integrations[] = 'ToieeLab_Integration';
		return $integrations;
	}
}


