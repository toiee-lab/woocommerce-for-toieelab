<?php

/**
 * WooCommerce の 認証を行うクラス
 */
class Woocommerce_SimpleRestrictContent {

	private $options;
	public $plugin_url;

	function __construct() {

		add_action( 'init', array( $this, 'create_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes_wcr' ) );

		add_shortcode( 'wcr-content', array( $this, 'wcr_content_shortdode' ) );
		add_shortcode( 'wcr-content-free', array( $this, 'wcr_content_free_shortdode' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		}

		$this->options = get_option( 'wc_src_options' );
		$this->add_acf();
	}

	public function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :


			// wcrestrict post type に付属するもの
			acf_add_local_field_group(
				array(
					'key'                   => 'group_5be17d7a9a9d7',
					'title'                 => '商品登録',
					'fields'                => array(
						array(
							'key'               => 'field_5be17da0c750c',
							'label'             => '商品、購読、会員',
							'name'              => 'wcr_product_ids',
							'type'              => 'post_object',
							'instructions'      => 'ここで登録した商品、購読、会員をまとめて「閲覧許可」を与えることができます',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'post_type'         => array(
								0 => 'product',
								1 => 'product_variation',
								2 => 'wc_membership_plan',
							),
							'taxonomy'          => '',
							'allow_null'        => 1,
							'multiple'          => 1,
							'return_format'     => 'id',
							'ui'                => 1,
						),
					),
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'wcrestrict',
							),
						),
					),
					'menu_order'            => 0,
					'position'              => 'acf_after_title',
					'style'                 => 'default',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'hide_on_screen'        => '',
					'active'                => 1,
					'description'           => 'WC Restrict に商品登録するためのもの',
				)
			);

		endif;
	}

	// -----------------------------------------------------------------------------
	//
	// ! Create post type for WC Restriction
	//
	// -----------------------------------------------------------------------------
	function create_post_type() {
		register_post_type(
			'wcrestrict',
			array(
				'label'               => '商品まとめ',
				'public'              => false,
				'exclude_from_search' => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 5,
				'menu_icon'           => 'dashicons-portfolio',
				'hierarchical'        => false,
				'has_archive'         => false,
				'supports'            => array(
					'title',
					'editor',
				),
				'capability_type'     => 'product',
			)
		);
	}

	// 登録情報を表示するための meta box の表示
	function add_meta_boxes_wcr() {
		add_meta_box(
			'wc_restrict',
			'ショートコード例',
			array( $this, 'display_wcr_meta_box' ),
			'wcrestrict',
			'advanced'
		);
	}
	function display_wcr_meta_box( $post ) {
		$id = get_the_ID();

		$wc_param = array(
			'wcr_product_ids' => '',
			'wcr_sub_ids'     => '',
			'wcr_mem_ids'     => '',
		);

		$wc_param_data = get_post_meta( $id, 'wcr_param', true );

		if ( $wc_param_data != '' ) {
			$wc_param_arr = unserialize( $wc_param_data );

			foreach ( $wc_param_arr as $key => $v ) {
				$wc_param[ $key ] = implode( ',', $v );
			}
		}

		wp_nonce_field( 'wcr_meta_box', 'wcr_meta_box_nonce' );

		echo <<<EOD
<p>閲覧制限のためのショートコード</p>

<pre id="s_code" style="height:6em;overflow:scroll;background-color:#eee;border:1px solid #999;padding:1em;">
[wcr-content ids="{$id}"]

here is contents

[/wcr-content]
</pre>



EOD;

	}

	// -----------------------------------------------------------------------------
	//
	// ! Shortcode
	//
	// -----------------------------------------------------------------------------
	function wcr_content_free_shortdode( $atts, $content ) {
		$atts = shortcode_atts(
			array(
				'title'   => '会員限定',
				'message' => 'このコンテンツをご覧になるには、会員ログインが必要です。',
			),
			$atts
		);

		$content = do_shortcode( $content );

		if ( is_user_logged_in() ) {
			if ( is_super_admin() ) {
				return '<div style="border:#f99 dashed 1px"><p style="background-color:#fcc;">このコンテンツは制限付きです</p>' . $content . '</div>';
			} else {
				return $content;
			}
		} else {

			$html = get_popup_login_form();

			// 表示するもの
			$html .= <<<EOD
    <div class="uk-inline uk-padding-small uk-width-1-1">
    
        {$content}
    
        <div class="uk-overlay-primary uk-position-cover"></div>
        <div class="uk-overlay uk-position-top uk-light">
            <h3>{$atts['title']}</h3>
            <p>{$atts['message']}</p>
            <p><button class="uk-button uk-button-primary" uk-toggle="target: #modal_login_form">ログインする</button></p>
            <p>会員登録は、無料です</p>
        </div>    
    </div>
EOD;

			return $html;
		}
	}

	function wcr_content_shortdode( $atts, $content ) {
		$atts = shortcode_atts(
			array(
				'ids'                      => '',
				'offer'                    => '',
				'message'                  => '',
				'show_to_not_grantee_mode' => false,
				'show_to_grantee_mode'     => false,
			),
			$atts
		);

		$ids                      = $atts['ids'];
		$offer                    = $atts['offer'];
		$message                  = $atts['message'];
		$show_to_not_grantee_mode = $atts['show_to_not_grantee_mode'];
		$show_to_grantee_mode     = $atts['show_to_grantee_mode'];

		$ids = explode( ',', $ids );
		if ( $offer == '' ) {
			$offer = $this->get_offer_product_id( $ids );
		}

		// admin の場合は制限せず、表示する。ただし、制限コンテンツの範囲を示す
		if ( is_super_admin() ) {
			return '<div style="border:#f99 dashed 1px"><p style="background-color:#fcc;">このコンテンツは制限付きです</p>' . do_shortcode( $content ) . '</div>';
		}
		// アクセスOKの場合、表示する
		$access = $this->check_access( $ids );
		if ( $access ) {
			return do_shortcode( $content );
		}

		// ----------------------------------------
		// アクセス制限時のメッセージボックスの作成
		// ----------------------------------------
		// message の取得と調整
		$not_access_message = $this->options['message'];

		// データの作成
		$product_url  = get_permalink( $offer );
		$product_name = get_the_title( $offer );
		$login_url    = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
		$modal_id     = 'modal-' . $offer;

		// error message がある場合、モーダルウィンドウを表示する（ための準備）
		ob_start();
		if ( function_exists( 'wc_print_notices' ) ) {  // Gutenbergとの兼ね合いで、不意に呼び出される
			wc_print_notices();
		}
		$wc_notices = ob_get_contents();
		ob_end_clean();

		if ( $wc_notices != '' ) {
			$js = <<<EOD
<script>			
el = document.getElementById('{$modal_id}');
UIkit.modal(el).show();
</script>
EOD;
		} else {
			$js = '';
		}

		// ログインフォームの取得
		ob_start();
		echo $wc_notices;
		woocommerce_login_form( array( 'redirect' => get_permalink() ) );
		echo $js;
		$login_form = ob_get_contents();
		ob_end_clean();

		// アクセス制限時のメッセージを作成
		$current_user = wp_get_current_user();

		$display_none = ( $current_user->ID != 0 ) ? 'style="display:none;"' : '';

		$not_access_message = str_replace(
			array( '{{product_url}}', '{{product_name}}', '{{message}}', '{{login_url}}', '{{modal_id}}', '{{login_form}}', '{{display_none}}' ),
			array( $product_url, $product_name, $message, $login_url, $modal_id, $login_form, $display_none ),
			$not_access_message
		);
		$not_access_message = do_shortcode( $not_access_message );  // ショートコードを適用する

		// show_to_not_grantee_mode = true の場合、not_access_message は、コンテンツ部分を使い、$content は null とする
		if ( $show_to_not_grantee_mode ) {
			$not_access_message = do_shortcode( $content );
			$content            = '';
		}

		// show_to_grantee_mode = true の場合、not_access_message は null、$content を表示する
		if ( $show_to_grantee_mode ) {
			$not_access_message = '';
		}

		// --------------------------------------------------------
		// Start Restrict Check
		// --------------------------------------------------------
		// ユーザーとして、ログインしているかチェック（ログインしていなければ、$not_access_message を表示）
		if ( $current_user->ID == 0 ) {
			return $not_access_message;
		}

		return $not_access_message;
	}

	/**
	 * 現在のユーザーで、アクセスをチェックします。様々なプロダクトが混ざった状態で動作する設計です
	 * wcrestrict、product、
	 */
	public function check_access( $ids, $user_id = '' ) {

		// user check
		if ( $user_id == '' ) {
			if ( is_user_logged_in() ) {
				$user = wp_get_current_user();
			} else {
				return false;
			}
		} else {
			$user = get_userdata( $user_id );
			if ( $user == false ) {
				return false;
			}
		}

		$user_id    = $user->ID;
		$user_email = $user->user_email;

		$ret = false;

		if ( ! is_array( $ids ) ) {
			return false;
		}

		foreach ( $ids as $i ) {
			$post_type = get_post_type( $i );

			switch ( $post_type ) {

				case 'wcrestrict':    // 商品まとめなので、再帰的な呼び出し
					$wcr_ids = get_field( 'wcr_product_ids', $i );
					$ret     = $this->check_access( $wcr_ids, $user_id );

					break;

				case 'wc_membership_plan': // WooCommerce メンバーシップ
					if ( function_exists( 'wc_memberships' ) ) {
						$ret = ( $i != '' ) ? wc_memberships_is_user_active_member( $user_id, $i ) : false;
					}

					break;

				case 'product':  // その他は商品としてチェックする
				default:
					$product      = wc_get_product( $i );
					$product_type = $product->get_type();

					// subscription
					if ( function_exists( 'wcs_user_has_subscription' )
					&& ( $product_type == 'subscription' || $product_type == 'variable-subscription' )
					) {
							$ret = ( $i != '' ) ? wcs_user_has_subscription( $user_id, $i, 'active' ) : false;
					} else {  // 今の所、 product_varidation ぐらいか？
						$ret = wc_customer_bought_product( $user_email, $user_id, $i );
					}
			}

			if ( $ret ) {
				return true;
			}
		}

		return $ret;
	}

	// ごちゃ混ぜのID(wc restrict 含む）から、最初のプロダクトを提供する
	function get_offer_product_id( $ids ) {

		foreach ( $ids as $i ) {
			$post_type = get_post_type( $i );

			if ( $post_type == 'product' ) {
				return $i;
			} elseif ( $post_type == 'wcrestrict' ) {
				$wcr_ids = get_field( 'wcr_product_ids', $i );
				$ret     = $this->get_offer_product_id( $wcr_ids );

				if ( $ret != false ) {
					return $ret;
				}
			}
		}

		return false;
	}


	// -----------------------------------------------------------------------------
	//
	// ! meta box setting
	//
	// -----------------------------------------------------------------------------
	function register_meta_boxes() {
		$screens = array( 'post', 'page', 'podcast' );
		foreach ( $screens as $screen ) {
			add_meta_box( 'wc_src_product_id', 'WooCommerce Simple Restrict Content', array( $this, 'display_meta_box' ), $screen, 'side' );
		}

	}
	function display_meta_box( $post ) {
		// 関連する WooCommerce の Product ID を取得
		$id                = get_the_ID();
		$wc_src_product_id = get_post_meta( $id, 'wc_src_product_id', true );

		// woocommerce の product を読み込んで、一覧を作って出力する
		wp_nonce_field( 'wc_src_product_id_meta_box', 'wc_src_product_id_meta_box_nonce' );

		$list = $this->get_woocommerce_product_list();

		$current_setting = '関連づけなし';

		$html  = '<select name="wc_src_product_id" id="wc_src_select" onChange="change_code();">' . "\n";
		$html .= '    <option value="">関連づけなし</option>' . "\n";

		$code         = '<div id="code_0">none</div>';
		$current_code = 'none';
		foreach ( $list as $k => $v ) {
			$tmp_code = htmlspecialchars( $this->get_shortcode( $v ), ENT_HTML5 );
			$code    .= '<div id="code_' . ( $k + 1 ) . '">' . $tmp_code . '</div>' . "\n";

			if ( $v['ID'] == $wc_src_product_id ) {
				$selected        = ' selected';
				$current_setting = $v['post_title'] . " (ID: {$v['ID']})\n";
				$current_code    = $tmp_code;
				$add_txt         = '【現在の設定】 ';
			} else {
				$selected = '';
				$add_txt  = '';
			}

			$html .= '    <option value="' . $v['ID'] . '"' . $selected . '>' . $add_txt . $v['post_title'] . '[ID: ' . $v['ID'] . ']</option>' . "\n";
		}
		$html .= '</select>';

		echo <<<EOD
<p>このコンテンツと関連づける商品を選択して下さい。</span><br>
</p>
{$html}
<div>
<pre id="s_code" style="height:10em;overflow:scroll;background-color:#eee;border:1px solid #999;padding:1em;">
{$current_code}
</pre>
<p style="font-size:small;color:#999">バリエーション商品の場合のルール : 親プロダクトIDを指定すると、子プロダクト(バリエーション)購入者全員が閲覧できます。<br>
複数のIDを指定する: id="xxxx,yyyy" と書くことで「いずれか」の商品を持つユーザーならアクセスできるようになります</p>
</div>
<div style="display:none;">
{$code}
</div>
<script>
function change_code(){
	sel = document.getElementById('wc_src_select');
	index = sel.selectedIndex;
	
	code = document.getElementById('code_'+index);
	txt  = document.getElementById('s_code');
	
	txt.innerHTML = code.innerHTML;

}
</script>
EOD;
		$wcr_posts = get_posts( array( 'post_type' => 'wcrestrict' ) );
		$code      = '';
		foreach ( $wcr_posts as $p ) {
			$code .= <<<EOD

[wc-restrict wcr_id="{$p->ID}" ]
{$p->post_title}
[/wc-restrict]
EOD;
			$code .= "\n";
		}

		echo <<<EOD
<p>WC Restrict で設定したデータに基づいて制限をかける場合は、以下を使ってください。</p>		
<pre style="height:10em;overflow:scroll;background-color:#eee;border:1px solid #999;padding:1em;">
{$code}
</pre>		
EOD;

	}
	function save_meta_boxes( $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['wc_src_product_id_meta_box_nonce'] ) ) {
			return $post_id;
		}

		$nonce = $_POST['wc_src_product_id_meta_box_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'wc_src_product_id_meta_box' ) ) {
			return $post_id;
		}

		$exclude_menu = isset( $_POST['wc_src_product_id'] ) ? $_POST['wc_src_product_id'] : null;
		$before       = get_post_meta( $post_id, 'wc_src_product_id', true );
		if ( $exclude_menu ) {
			update_post_meta( $post_id, 'wc_src_product_id', $exclude_menu );
		} else {
			delete_post_meta( $post_id, 'wc_src_product_id', $before );
		}
	}

	function get_woocommerce_product_list() {
		$prodct_list = array();

		$loop = new WP_Query(
			array(
				'post_type'      => array( 'product', 'product_variation' ),
				'posts_per_page' => -1,
			)
		);

		while ( $loop->have_posts() ) :
			$loop->the_post();
			$theid = get_the_ID();

			$prodct_list[ $theid ] = array(
				'ID'          => $theid,
				'post_title'  => get_the_title(),
				'post_type'   => get_post_type(),
				'post_parent' => wp_get_post_parent_id( $theid ),
			);

		endwhile;
		wp_reset_query();

		// product タイプのみを選び出し、product_variation は、子要素に移動する
		foreach ( $prodct_list as $key => $v ) {
			if ( $v['post_type'] == 'product_variation' ) {
				$parent = $v['post_parent'];
				if ( ! isset( $prodct_list[ $parent ]['child'] ) ) {
					$prodct_list[ $parent ]['child'] = array();
				}

				$prodct_list[ $parent ]['child'][ $key ] = $v;
				unset( $prodct_list[ $key ] );
			}
		}

		usort(
			$prodct_list,
			function ( $a, $b ) {
				return strnatcmp( $a['post_title'], $b['post_title'] );
			}
		);

		return $prodct_list;
	}
	function get_shortcode( $arr ) {
		$code = '<!-- xxx -->
[wc-restrict id="yyy" message="zzz"]
here is contents
[/wc-restrict]
';

		$ret  = str_replace( array( 'xxx', 'yyy', 'zzz' ), array( $arr['post_title'], $arr['ID'], $arr['post_title'] ), $code );
		$ret .= "\n";
		if ( isset( $arr['child'] ) ) {
			foreach ( $arr['child'] as $v ) {
				$ret .= str_replace( array( 'xxx', 'yyy', 'zzz' ), array( $v['post_title'], $v['ID'], $v['post_title'] ), $code );
				$ret .= "\n";
			}
		}
		return $ret;
	}

	// -----------------------------------------------------------------------------
	//
	// ! Admin settings
	//
	// -----------------------------------------------------------------------------
	public function add_plugin_page() {
		// This page will be under "Settings"
		add_options_page(
			'WooCommerce for toiee Lab',
			'WC for toiee Lab',
			'manage_options',
			'wc4t-admin',
			array( $this, 'create_admin_page' )
		);
	}
	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		// Set class property
		$this->options = get_option( 'wc_src_options' );
		?>
		<div class="wrap">

			<h2>WooCommerce for toiee Lab</h2>
			<?php settings_errors(); ?>
			<?php
			if ( isset( $_GET['tab'] ) ) {
				$active_tab = $_GET['tab'];
			} else {
				$active_tab = 'general';
			}
			?>
			<h2 class="nav-tab-wrapper">
				<a href="?page=wc4t-admin&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">概要</a>
				<a href="?page=wc4t-admin&tab=restrict" class="nav-tab <?php echo 'restrict' === $active_tab ? 'nav-tab-active' : ''; ?>">コンテンツ制限</a>
				<a href="?page=wc4t-admin&tab=preference" class="nav-tab <?php echo 'preference' === $active_tab ? 'nav-tab-active' : ''; ?>">機能設定</a>
			</h2>
			<?php
			switch ( $active_tab ) {
				case 'restrict':
					$this->setting_restrict();
					break;
				case 'preference':
					$this->setting_preference();
					break;
				default:
					$this->setting_general();
			}
			?>
		</div>
		<?php
	}

	public function setting_general() {
		?>
		<div style="max-width:600px">
			<p>WooCommerce for toiee Lab は、 toiee.jp 専用に開発されています。toiee.jp で、必要とする様々な機能を含んでいます。<br>
			初期設定では、以下の機能を有効にしています。それ以外は、必要に応じて有効化してください。</p>

			<ol>
				<li>コンテンツ制限</li>
				<li>商品まとめ機能</li>
			</ol>
		</div>
		<?php
	}

	public function setting_restrict() {
		$text = isset( $this->options['message'] ) ? $this->options['message'] : '';

		if ( isset( $_POST['cmd'] ) && 'restrict' === $_POST['cmd'] ) {
			check_admin_referer( 'toiee_wc4t' );

			$dat = stripslashes_deep( $_POST['wc_src_options'] );
			update_option( 'wc_src_options', $dat );
			$text = $dat['message'];
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong>更新しました。</strong></p>
			</div>
			<?php
		}

		?>
		<p>コンテンツ制限機能の設定を行います。</p>
		<h3>メッセージボックス</h3>
		<div style="max-width:600px">
		<form method="post" action="<?php admin_url( 'options-general.php?page=wc4t-admin&tab=restrict' ); ?>">
			<textarea name="wc_src_options[message]" style="width:100%;height: 20em;"><?php echo esc_html( $text ); ?></textarea>
			<p><a href="https://github.com/toiee-lab/woocommerce-for-toieelab/wiki/%E5%88%9D%E6%9C%9F%E8%A8%AD%E5%AE%9A" target="_blank">ヘルプは、こちら</a></a></p>
			<?php wp_nonce_field( 'toiee_wc4t' ); ?>
			<input type="hidden" name="cmd" value="restrict" />
			<?php submit_button( '実行' ); ?>
		</form>
		</div>
		<?php
	}

	public function setting_preference() {

		$funcs = [
			'mylib' => [
				'title' => 'マイライブラリ機能',
				'desc'  => 'WooCommerceのユーザーダッシュボードに「マイライブラリ」を表示し、コンテンツに素早くアクセスできるようにします',
			],
			'ctab'       => [
				'title' => 'カスタムタブ',
				'desc'  => 'WooCommerceの商品ページに独自のタブを追加できます（カテゴリなどを指定することで）',
			],
			'mailerlite' => [
				'title' => 'Mailerlite連携',
				'desc'  => '商品購入とMailerliteグループを紐付けます。定期購読、バリエーション、返品にも対応しています',
			],
			'sub_inst' => [
				'title' => 'WooCommerce Subscriptions 分割支払い',
				'desc'  => 'WooCommerce Subscriptionsを分割支払いに使えるように機能を拡張します',
			],
			'sub_bank' => [
				'title' => 'WooCommerce Subscriptions 銀行支払い期限延長',
				'desc'  => 'WooCommerce Subscriptions + WooCommerce for Japan で有効になる銀行振込による定期購読では、銀行支払い期間が短いため有効期限が切れやすくなります。これを1週間に延長します。',
			],
			'pcast' => [
				'title' => 'Podcast機能',
				'desc'  => 'Podcast機能を有効にします。マガジン、スクラム、耳デミー、ポケてらなどを利用する場合は、必ず ON にしてください。',
			],
			'mag'   => [
				'title' => 'マガジン機能',
				'desc'  => 'Magazine投稿タイプを有効にします。',
			],
			'mdy' => [
				'title' => '耳デミー機能',
				'desc'  => '耳デミー（ビデオ、オーディオ、Podcast配信、授業資料）を有効にします',
			],
			'pkt' => [
				'title' => 'ポケてら機能',
				'desc'  => 'ポケてら（ビデオ、オーディオ、Podcast配信、授業資料、LFT資料、ノート、フィードバック）を有効にします',
			],
			'tkb' => [
				'title' => '関連ナレッジ機能',
				'desc'  => '関連ナレッジを投稿できるようにします。耳デミー、ポケてらに関連します',
			],
			'scrum' => [
				'title' => 'スクラム機能',
				'desc'  => '専用ブログ、お知らせ、Podcast配信、教材の関連付けができるスクラム機能です',
			],
			'event' => [
				'title' => 'シンプルイベント機能',
				'desc'  => 'シンプルなイベント機能です。イベントの申し込みなどは、外部サイトを想定しています。',
			],
			'rlogin' => [
				'title' => 'rlogin機能',
				'desc'  => '別のWordPressを認証、ログインさせるための機能',
			],
			'ssp'       => [
				'title' => 'Seriously Simple Podcast拡張',
				'desc'  => 'Seriously Simple Podcastを拡張し、購入者限定などを実現します',
			],

		];

		$depend_pcast   = [ 'mdy', 'pkt', 'scrum', 'mag' ];
		$depend_rewrite = [ 'mylib', 'mag', 'mdy', 'pkt', 'scrum', 'event', 'rlogin', 'ssp' ];

		?>
		<p>以下、必要な機能をOn/Offしてください。<br><br></p>
		<?php
		foreach ( $funcs as $key => $v ) {
			?>
			<h3><?php echo esc_html( $v['title'] ); ?></h3>
			<p><?php echo esc_html( $v['desc'] ); ?></p>
			<?php
		}
	}

	/*
	* Podcast feed のアクセス許可を出すために、ユーザーを識別する固有のIDを取得する。
	* もし、ユーザーが持っていなければ生成する
	*/
	public function get_user_wcrtoken() {

		if ( is_user_logged_in() ) {

			$user_id  = get_current_user_id();
			$wcrtoken = get_user_meta( $user_id, 'wcrtoken', true );

			if ( $wcrtoken == '' ) {
				$wcrtoken = uniqid();
				update_user_meta( $user_id, 'wcrtoken', $wcrtoken );
			}

			return $wcrtoken;
		}

		return null;
	}
}

