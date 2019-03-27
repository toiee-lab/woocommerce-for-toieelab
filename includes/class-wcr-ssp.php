<?php

/**
 * Seriously Simple Podcast を拡張するためのクラス
 *
 * @package woocommerce for toiee lab
 */

define( 'WC4T_WCRTOKEN', 'wcrtoken' );

/**
 * Class WCR_SSP
 */
class WCR_SSP {

	/**
	 * 設定を保存
	 *
	 * @var array
	 */
	private $options;
	/**
	 * プラグインのslugを保持
	 *
	 * @var string
	 */
	protected $plugin_slug;
	/**
	 * プラグインのURL
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * WCR_SSP constructor.
	 */
	public function __construct() {
		$this->plugin_slug = 'seriously-simple-podcasting';

		/* Series の拡張 */
		add_filter( 'ssp_settings_fields', array( $this, 'ssp_setting_fields' ), 10, 1 );
		add_action( 'series_edit_form_fields', array( $this, 'add_detail_url' ) );

		/* Episode の拡張 */
		add_filter( 'ssp_episode_fields', array( $this, 'ssp_episode_fields' ), 10, 1 );

		/* テンプレートの差し替え */
		add_filter( 'ssp_feed_template_file', array( $this, 'ssp_feed_template_file' ), 1, 1 );

		/* ショートコード */
		add_shortcode( 'wcr_ssp', array( $this, 'add_wcr_ssp_shortcode' ) );

		/* ショートコードを series column に追加 */
		add_filter( 'manage_edit-series_columns', array( $this, 'edit_series_columns' ), 10 );
		add_filter( 'manage_series_custom_column', array( $this, 'add_series_columns' ), 2, 3 );

		/* Episode に pub date を 追加し、並び替えを可能にする */
		add_filter( 'manage_edit-podcast_columns', array( $this, 'edit_podcast_columns' ), 10 );
		add_filter( 'manage_podcast_posts_custom_column', array( $this, 'add_podcast_columns' ), 1, 3 );

		add_filter( 'request', array( $this, 'podcast_column_orderby_post_date' ) );
		add_filter( 'manage_edit-podcast_sortable_columns', array( $this, 'podcasts_register_sortable' ) );

		/* Episode の size を　filesize を返す*/

		/* add_filter( 'ssp_feed_item_size', array($this, 'get_size'), 10, 2 ); */

		/* 管理画面設定 */
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_plugin_page_episodeupdate' ) );
			add_action( 'admin_init', array( $this, 'page_init_episodeupdate' ) );
		}

		/* 表示数を変更 */
		add_action( 'pre_get_posts', array( $this, 'change_episode_per_page' ) );
		add_filter( 'get_the_archive_title', array( $this, 'remove_tax_name' ) );

		/* RSS の表示数を増やす */
		add_filter(
			'ssp_feed_number_of_posts',
			function ( $num ) {
				return 300;
			}
		);

		/* Slug が日本語にされてしまうkとを抑制する */
		add_filter(
			'ssp_archive_slug',
			function () {
				return 'podcast';
			}
		);

		$this->options = get_option( 'wcr_ssp_options' );
	}


	public static function rewrite_flush() {

	}

	// Series に、アクセス制限のための項目と、Podcastの形式（オーディオ、デフォルト）を追加する
	function ssp_setting_fields( $settings ) {

		if ( ! array_key_exists( 'feed-series', $_GET ) ) {
			return $settings;
		}

		$series_slug = $_GET['feed-series'];
		$term        = get_term_by( 'slug', $series_slug, 'series' );

		if ( $term == false ) {
			$series_url      = '#';
			$series_edit_url = '#';
		} else {
			$series_url      = get_term_link( $term, 'series' );
			$series_edit_url = get_edit_term_link( $term, 'series' );
		}

		array_unshift(
			$settings['feed-details']['fields'],
			array(
				'id'          => 'podcast_info',
				'label'       => __( 'リンク集', 'seriously-simple-podcasting' ),
				'description' => '<a href="' . $series_url . '">視聴ページ</a> : <a href="' . $series_edit_url . '">編集ページ</a>',
				'type'        => 'none',
				'default'     => '',
				'placeholder' => __( '100,200,...', 'seriously-simple-podcasting' ),
				'callback'    => '',
				'class'       => 'regular-text',
			)
		);

		return $settings;
	}

	// Episode に、アクセス制限のための項目を追加
	function ssp_episode_fields( $fields ) {

		$fields['wcr_ssp_episode_restrict'] = array(
			'name'             => __( 'Restrict :', $this->plugin_slug ),
			'description'      => '',
			'type'             => 'radio',
			'default'          => 'disable',
			'options'          => array(
				'enable'  => __( '制限する', $this->plugin_slug ),
				'disable' => __( '制限しない', $this->plugin_slug ),
			),
			'section'          => 'info',
			'meta_description' => __( 'The setting of restriction', $this->plugin_slug ),
		);

		return $fields;
	}

	// Series に便利なリンクを用意する
	function add_detail_url( $term ) {
		$url        = get_admin_url() . 'edit.php?post_type=podcast&page=podcast_settings&tab=feed-details&feed-series=' . $term->slug;
		$enc_url    = htmlentities( $url );
		$series_url = get_term_link( $term, 'series' );
		?>

		<tr class="form-field term-meta-text-wrap">
			<th scope="row"><label for="term-meta-text"><?php _e( '便利なリンク集', 'text_domain' ); ?></label></th>
			<td>
				<a href="<?php echo $enc_url; ?>">フィード設定</a> : <a href="<?php echo $series_url; ?>">視聴ページ</a> :
				<input type="text" readonly="readonly" value='[wcr_ssp id="<?php echo $term->term_id; ?>" /]'  >
			</td>
		</tr>
		<tr class="form-field term-meta-text-wrap">
			<th scope="row"><hr></th>
			<td><hr></td>
		</tr>
		<?php
	}

	function ssp_feed_template_file( $template_file ) {
		$template_file = dirname( dirname( __FILE__ ) ) . '/templates/feed-podcast.php';
		return $template_file;
	}



	// -----------------------------------------------------------------------------
	//
	// ! Shortcode
	//
	// -----------------------------------------------------------------------------
	/* [wcr_ssp id="x" /] */
	function add_wcr_ssp_shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'id'                => '',
				'label_podcast'     => 'iPhone、iPad、スマホ',
				'label_pcast'       => 'Mac、パソコン',
				'label_url'         => 'その他(URL)',
				'label_web'         => 'Web視聴する',
				'label_ok'          => '全編をご覧いただけます',
				'label_trial'       => '一部コンテンツをご覧いただけます',
				'label_ok_offer'    => '全編のお申し込みはこちら',
				'label_offer_trial' => '無料登録で、一部コンテンツをご覧いただけます',
				'label_toc'         => '(目次一覧)',
				'template'          => '',
				'template_name'     => 'default',
				'redirect_url'      => '',
			],
			$atts
		);

		$id                = $atts['id'];
		$label_podcast     = $atts['label_podcast'];
		$label_pcast       = $atts['label_pcast'];
		$label_url         = $atts['label_url'];
		$label_web         = $atts['label_web'];
		$label_ok          = $atts['label_ok'];
		$label_trial       = $atts['label_trial'];
		$label_ok_offer    = $atts['label_ok_offer'];
		$label_offer_trial = $atts['label_offer_trial'];
		$label_toc         = $atts['label_toc'];
		$template          = $atts['template'];
		$template_name     = $atts['template_name'];
		$redirect_url      = $atts['redirect_url'];

		// template の決定
		if ( $template == '' ) {
			switch ( $template_name ) {
				case 'on_episode_audio':
					$template = $this->get_dummy_player( 'audio', '%MESSAGE%' );
					break;

				case 'on_episode_video':
					$template = $this->get_dummy_player( 'video', '%MESSAGE%' );
					break;

				case 'on_archive':
					$template = '
<p uk-margin><a href="%FEED%" class="uk-button uk-button-default uk-box-shadow-small" %TARGET_TOGLE%>' . $label_podcast . '</a>
<a href="%PCAST%" class="uk-button uk-button-default uk-box-shadow-small" %TARGET_TOGLE%>' . $label_pcast . '</a><br>
<a href="%URL%" %TARGET_TOGLE% class="uk-button uk-button-text">' . $label_url . '</a>
<br>
<span class="uk-text-meta uk-text-small">%MESSAGE%</span>&nbsp;
</p>';
					break;

				default:
					$template = '
<p uk-margin><a href="%FEED%" class="uk-button uk-button-default uk-box-shadow-small" %TARGET_TOGLE%>' . $label_podcast . '</a>
<a href="%PCAST%" class="uk-button uk-button-default uk-box-shadow-small" %TARGET_TOGLE%>' . $label_pcast . '</a><br>
<a href="%URL%" %TARGET_TOGLE% class="uk-button uk-button-text">' . $label_url . '</a>
<a href="%TERM_LINK%" target="_blank" class="uk-button uk-button-text">' . $label_web . '</a>
<br>
<span class="uk-text-meta uk-text-small">%MESSAGE%</span>&nbsp;
<a href="%TERM_LINK%" target="_blank"><span class="uk-text-small">%ARCHIVE%</span></a>
</p>';
			}
		}

		// check
		if ( $id == '' ) {
			return '<p>invalid series id</p>';
		}

		// feed url の生成
		global $ss_podcasting;

		$series_id   = $id;
		$series      = get_term( $series_id, 'series' );
		$series_url  = get_term_link( $series );
		$series_slug = $series->slug;

		if ( is_wp_error( $series ) ) {
			return '<p>invalid series id</p>';
		}

		if ( get_option( 'permalink_structure' ) ) {
			$feed_slug = apply_filters( 'ssp_feed_slug', $ss_podcasting->token );
			$feed_url  = $ss_podcasting->home_url . 'feed/' . $feed_slug . '/' . $series_slug;
		} else {
			$feed_url = add_query_arg(
				array(
					'feed'           => $ss_podcasting->token,
					'podcast_series' => $series_slug,
				),
				$ss_podcasting->home_url
			);
		}

		// seckey から、feed url に付属させるパラメタを作成
		// TODO ここで token を作るのではなく「登録しておいたもの」を使うこととする
		// uniqid を使う( wctoken )
		$user       = wp_get_current_user();
		$user_id    = $user->ID;
		$user_email = $user->user_email;

		if ( $user_id != 0 ) {
			$user_logined = true;

			$add_param = '/' . WC4T_WCRTOKEN . '/' . $this->get_user_wcrtoken();
		} else {
			$user_logined = false;
			$add_param    = '';
		}
		$wcr_feed_url = $feed_url . $add_param;

		// アクセスできる場合は、target="_blank" を入れる。そうでない場合は、uk-toggle="target: #modal" をセットする
		$target_toggle = 'target="_blank"';
		$message       = '';

		// ! いずれ、このソースコードは修正だ（データを入れ替えられたら消す）
		$wc_restrict_ssp = get_option( 'ss_podcasting_wc_restrict_ssp_' . $series_id, false );  // デフォルトは false

		// 新しい方の設定（ term の場合は、term object を渡す必要がある）
		$wcr_content_ssp = get_field( 'series_limit', $series );

		if ( $wc_restrict_ssp == 'restrict_enable' || $wcr_content_ssp ) {  // もし、制限ありなら
			if ( $user_logined ) {  // ユーザーの制限をチェックして、メッセージを切り替える

				$product_url = '';
				$modal_html  = '';

				$ret = $this->get_access_and_product_url( $user_email, $user_id, $series_id );

				// メッセージの生成
				if ( $ret['access'] ) {
					$message = $label_ok;
				} else {
					$message = $label_trial;
					if ( $ret['url'] != '' ) {
						 $message .= ' <a href="' . $ret['url'] . '" class="uk-button uk-button-text">(' . $label_ok_offer . ')</a>';
					}
				}
			} else {  // ログインフォームを出す

				$modal_html = get_popup_login_form();

				$wcr_feed_url  = '#';
				$target_toggle = 'uk-toggle="target: #modal_login_form"';
				$message       = $label_offer_trial;
			}
		}

		$url_scheme_feed  = str_replace( 'https://', 'podcast://', $wcr_feed_url );
		$url_scheme_pcast = str_replace( 'https://', 'pcast://', $wcr_feed_url );

		return str_replace(
			array( '%FEED%', '%PCAST%', '%URL%', '%TARGET_TOGLE%', '%MESSAGE%', '%ARCHIVE%', '%TERM_LINK%' ),
			array( $url_scheme_feed, $url_scheme_pcast, $wcr_feed_url, $target_toggle, $message, $label_toc, $series_url ),
			$template
		) . $modal_html;
	}


	public function get_wc_login_form_modal( $redirect_url = '' ) {

		// error message がある場合、モーダルウィンドウを表示する（ための準備）
		ob_start();
		wc_print_notices();
		$wc_notices = ob_get_contents();
		ob_end_clean();

		$js = ( $wc_notices != '' ) ?
		"<script>el = document.getElementById('modal_login_form');UIkit.modal(el).show();</script>"
		: '';

		// ログインフォームの取得
		if ( $redirect_url == '' ) {
			$redirect_url = get_permalink();
		}

		ob_start();
		echo $wc_notices;
		woocommerce_login_form( array( 'redirect' => $redirect_url ) );
		echo $js;
		$login_form = ob_get_contents();
		ob_end_clean();

		// 登録フォームの取得
		ob_start();
		?>
		<h2><?php esc_html_e( 'Register', 'woocommerce' ); ?></h2>

		<form method="post" class="woocommerce-form woocommerce-form-register register">

		<?php do_action( 'woocommerce_register_form_start' ); ?>

		<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
				</p>

		<?php endif; ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
				<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
			</p>

		<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
				</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_register_form' ); ?>

			<p class="woocommerce-FormRow form-row">
		<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				<button type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
			</p>

		<?php do_action( 'woocommerce_register_form_end' ); ?>

		</form>
		<?php
		$register_form = ob_get_contents();
		ob_end_clean();   // 登録フォーム取得、ここまで

		// <a href="#" uk-toggle="target: #modal_login_form">ログインはこちら</a> で開く
		$modal_html = <<<EOD
<!-- This is the modal -->
<div id="modal_login_form" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
        <h4>会員ログインが必要です</h4>
        <div class="uk-alert-success" uk-alert><p>無料登録することで、一部をご覧いただけます。<br>
        <a href="#toggle-form" uk-toggle="target: #toggle-form; animation: uk-animation-fade">無料登録はこちらをクリック</a>
        </p>
        </div>
        <div id="toggle-form" hidden class="uk-card uk-card-default uk-card-body uk-margin-small">
        {$register_form}
        </div>
        {$login_form}
        <p class="uk-text-right">
            <button class="uk-button uk-button-default uk-modal-close" type="button">閉じる</button>
        </p>
    </div>
</div>
EOD;

		return $modal_html;
	}

	public function get_dummy_player( $type = 'video', $message = '' ) {

		if ( $type == 'video' ) {
			$img = $this->plugin_url . '/images/na-video.png';
		} else {
			$img = $this->plugin_url . '/images/na-audio.png';
		}

		return str_replace(
			array( '%IMG%', '%MESSAGE%' ),
			array( $img, $message ),
			'
<div class="uk-margin-medium-top uk-margin-small-bottom">
<img src="%IMG%" /><br>
<span class="uk-text-meta uk-text-small">%MESSAGE%</span>&nbsp;
</div>					
'
		);
	}

	public function get_access_and_product_url( $user_email = '', $user_id = '', $series_id ) {

		global $wcr_content;

		if ( $user_email == '' ) { // ユーザーが指定されていない場合
			if ( is_user_logged_in() ) {
				$user       = wp_get_current_user();
				$user_id    = $user->ID;
				$user_email = $user->user_email;
			}
		}

		// 許可商品の取得
		$series  = get_term( $series_id, 'series' );
		$wcr_ids = get_field( 'series_products', $series );

		// 商品URLを取得
		$offer       = $wcr_content->get_offer_product_id( $wcr_ids );
		$product     = wc_get_product( $offer );
		$product_url = is_object( $product ) ? get_permalink( $product->get_id() ) : '';

		// user 不明なら
		if ( $user_email == '' || $user_id == '' ) {
			return array(
				'access' => false,
				'url'    => $product_url,
			);
		}

		// アクセスチェック
		$access = $wcr_content->check_access( $wcr_ids, $user_id );
		return array(
			'access' => $access,
			'url'    => $product_url,
		);
	}

	public function edit_series_columns( $columns ) {
		$columns['shortcode'] = __( 'Shortcode', 'wcr-ssp' );
		return $columns;
	}

	public function add_series_columns( $column_data, $column_name, $term_id ) {
		switch ( $column_name ) {
			case 'shortcode':
				$column_data = '[wcr_ssp id="' . $term_id . '" /]';
				break;
		}
		return $column_data;
	}

	public function edit_podcast_columns( $columns ) {
		$columns['post_date'] = __( '公開日', 'wcr-ssp' );
		return $columns;
	}

	public function add_podcast_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'post_date':
				echo get_the_date( 'Y/n/j G:i', $post_id );
				break;
		}
	}

	public function podcast_column_orderby_post_date( $vars ) {
		if ( isset( $vars['orderby'] ) && $vars['orderby'] == 'post_data' ) {
			$vars = array_merge(
				$vars,
				array(
					'meta_key' => 'post_data',
					'orderby'  => 'meta_value',
				)
			);
		}
		return $vars;
	}

	public function podcasts_register_sortable( $sortable_column ) {
		$sortable_column['post_date'] = 'post_date';
		return $sortable_column;
	}

	// -----------------------------------------------------------------------------
	//
	// ! Admin settings
	//
	// -----------------------------------------------------------------------------

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input ) {
		return $input;
		// サニタイズしない
		// $new_input = array();
		//
		// if( isset( $input['seckey'] ) )
		// $new_input['seckey'] = wp_kses_post( $input['seckey'] );
		//
		// return $new_input;
	}
	/**
	 * Print the Section text
	 */
	public function print_section_info() {
		print '以下に設定を指定し、変更を保存をクリックしてください。';
	}
	/**
	 * Get the settings option array and print one of its values
	 */
	public function seckey_callback() {
		$text = isset( $this->options['seckey'] ) ? $this->options['seckey'] : '';
		?>

		<input type="text" name="wcr_ssp_options[seckey]" value="<?php echo $text; ?>">


		<?php
	}



	function add_plugin_page_episodeupdate() {
		add_submenu_page(
			'edit.php?post_type=podcast',
			'エピソードの更新',
			'エピソードの更新',
			'administrator',
			'wc4toiee-ssp-update',
			array( $this, 'create_update_page' )
		);
	}

	function create_update_page() {

		// エピソードの length を更新
		if ( isset( $_POST['updated_series_term'] ) ) {
			check_admin_referer( 'update_options' );

			$term_id = $_POST['updated_series_term'];
			$posts   = get_posts(
				array(
					'post_type'      => 'podcast',
					'posts_per_page' => -1,
					'order'          => 'ASC',
					'post_status'    => 'publish',
					'tax_query'      => array(
						array(
							'taxonomy' => 'series',
							'field'    => 'term_id',
							'terms'    => $term_id,
						),
					),
				)
			);

			// ssl check を無視
			stream_context_set_default(
				[
					'ssl' => [
						'verify_peer'      => false,
						'verify_peer_name' => false,
					],
				]
			);

			$log = '<h3>log</h3><ul>';
			foreach ( $posts as $e ) {
				   $e_id      = $e->ID;
				   $enclosure = filter_var( get_post_meta( $e_id, 'enclosure', true ), FILTER_VALIDATE_URL );
				   get_post_meta( $e_id, 'enclosure', true );

				   $location = $enclosure;
				for ( $i = 0; $i < 5; $i++ ) { // 5回までのリダイレクトを処理する
					$header = get_headers( $location, 1 );
					if ( isset( $header['Location'] ) ) {
						$location = $header['Location'];
					} else {
						break;
					}
				}

				   $length = isset( $header['Content-Length'] ) ? $header['Content-Length'] : 10000;
				   update_post_meta( $e_id, 'filesize_raw', $length );

				   $log .= "<li>id:{$e_id}, name:{$e->name}, size: {$length}</li>";
			}
			$log .= '</ul>';

		}

		// Vimeo APIキーの保存
		if ( isset( $_POST['ssp_vimeo_api_access_token'] ) ) {
			update_option( 'ssp_vimeo_api_access_token', esc_attr( $_POST['ssp_vimeo_api_access_token'] ), false );
			update_option( 'ssp_vimeo_api_cid', esc_attr( $_POST['ssp_vimeo_api_cid'] ), false );
			update_option( 'ssp_vimeo_api_csr', esc_attr( $_POST['ssp_vimeo_api_csr'] ), false );
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong>saved Vimeo API settings.</strong></p>
			</div>
			<?php
		}
		$vimeo_api_access_token = get_option( 'ssp_vimeo_api_access_token', '' );
		$vimeo_api_cid          = get_option( 'ssp_vimeo_api_cid', '' );
		$vimeo_api_csr          = get_option( 'ssp_vimeo_api_csr', '' );

		// Vimeo から一覧を取得する
		if ( '' !== $vimeo_api_access_token && isset( $_POST['update_vimeo_list'] ) ) {

			$lib = new \Vimeo\Vimeo( $vimeo_api_cid, $vimeo_api_csr );
			$lib->setToken( $vimeo_api_access_token );

			$response = $lib->request(
				'/me/projects',
				[
					'direction' => 'desc',
					'per_page'  => 20,
					'sort'      => 'date',
				],
				'GET'
			);
			foreach ( $response['body']['data'] as $d ) {
				$vimeo_list[] = array(
					'name'  => $d['name'],
					'value' => $d['uri'],
				);
			}

			$response = $lib->request(
				'/me/albums',
				[
					'direction' => 'desc',
					'per_page'  => 20,
					'sort'      => 'date',
				],
				'GET'
			);
			foreach ( $response['body']['data'] as $d ) {
				$vimeo_list[] = array(
					'name'  => $d['name'],
					'value' => $d['uri'],
				);
			}

			if ( count( $vimeo_list ) ) {
				update_option( 'ssp_vimeo_list', $vimeo_list );
			}
		}

		// vimeo から podcast をインポート
		if ( isset( $_POST['vimeo_proj'] ) ) {

			$lib = new \Vimeo\Vimeo( $vimeo_api_cid, $vimeo_api_csr );
			$lib->setToken( $vimeo_api_access_token );

			if ( null === $lib ) {
				?>
				<div class="notice notice-warning is-dismissible">
					<p><strong>vimeo にアクセスできませんでした。</strong></p>
				</div>
				<?php
			} else {

				// term info
				$term = get_term_by( 'id', $_POST['updated_series_term'], 'series' );

				// データの取得
				$ret    = $lib->request( $_POST['vimeo_proj'] . '/videos', [ 'per_page' => 100 ], 'GET' );
				$videos = $ret['body']['data'];
				usort(
					$videos,
					function ( $a, $b ) {
						return strnatcmp( $a['name'], $b['name'] );
					}
				);

				// 登録作業
				$cnt   = 0;
				$total = count( $videos );
				$time  = time() - 60 * ( $total + 2 );

				foreach ( $videos as $i => $v ) {
					$cnt++;
					$time += 60;

					$arr          = array();
					$arr['title'] = $v['name'];

					$arr['duration'] = sprintf(
						'%02d:%02d:%02d',
						floor( $v['duration'] / 3600 ),
						floor( ( $v['duration'] / 60 ) % 60 ),
						$v['duration'] % 60
					);

					foreach ( $v['files'] as $d ) {
						if ( $d['quality'] == 'hd' ) {
							$link              = preg_replace( '/&oauth2_token_id=([0-9]+)/', '', $d['link'] ) . '&download=1';
							$arr['audio_file'] = $arr['enclosure'] = $link;
							$arr['filesize']   = $arr['filesize_raw'] = $d['size']; // そのままでいいかも
							break;
						}
					}

					$post = array(
						'ID'           => null,
						'post_content' => '',
						'post_name'    => $term->slug . '-' . $cnt,
						'post_title'   => $arr['title'],
						'post_status'  => 'publish',
						'post_type'    => 'podcast',
						'post_date'    => date( 'Y-m-d H:i:s', $time ), // TODO 今の時間
						'tax_input'    => array( 'series' => $term->term_id ),
					);

					$post_id = wp_insert_post( $post );

					$arr['wcr_ssp_episode_restrict'] = 'enable';
					$arr['episode_type']             = 'video';

					$meta = array(
						'audio_file',
						'enclosure',
						'episode_type',
						'filesize',
						'filesize_raw',
						'wcr_ssp_episode_restrict',
						'duration',
					);

					foreach ( $meta as $key ) {
							  update_post_meta( $post_id, $key, $arr[ $key ] );
					}
				}

				$url = admin_url( 'edit.php?series=' . $term->slug . '&post_type=podcast' );

				?>
				<div class="notice notice-success is-dismissible">
					<p><strong>登録作業が完了しました。</strong> <a href="<?php echo $url; ?>">結果はこちら</a></p>
				</div>
				<?php
			}
		}

		// シリーズの一覧を取得
		$terms = get_terms(
			'series',
			array(
				'orderby'    => 'id',
				'order'      => 'DESC',
				'hide_empty' => 0,
			)
		);

		// vimeo のリストを取得
		$vimeo_list = get_option( 'ssp_vimeo_list', array() );

		?>
		<div class="wrap">

			<h2>エピソードの更新</h2>
			<p>エピソードのlengthを、一度に更新します。</p>

			<form method="post" action="<?php admin_url( 'edit.php?post_type=podcast&page=wc4toiee-ssp-update' ); ?>">
		<?php wp_nonce_field( 'update_options' ); ?>
				<select name="updated_series_term">
		<?php foreach ( $terms as $t ) : ?>
						<option value="<?php echo $t->term_id; ?>"><?php echo $t->name; ?></option>
		<?php endforeach; ?>
				</select>
				<input type="hidden" name="update_episodes" value="update_episodes">
		<?php submit_button( '更新を実行する' ); ?>
			</form>
		<?php
		if ( isset( $log ) ) {
			echo $log;
		}
		?>


			<hr>
			<h2>Vimeoからインポート</h2>
			<h3>インポート</h3>
			<p>最新のプロジェクト、アルバム20件のみを表示します。</p>
			<form method="post" action="<?php admin_url( 'edit.php?post_type=podcast&page=wc4toiee-ssp-vimeo-import' ); ?>">
		<?php wp_nonce_field( 'update_options' ); ?>
				<label>インポートするプロジェクト（アルバム）</label>
				<select name="vimeo_proj">
		<?php foreach ( $vimeo_list as $v ) : ?>
						<option value="<?php echo $v['value']; ?>"><?php echo $v['name']; ?></option>
		<?php endforeach; ?>
				</select>
				<br>
				<label>インポート先のシリーズ</label>
				<select name="updated_series_term">
		<?php foreach ( $terms as $t ) : ?>
						<option value="<?php echo $t->term_id; ?>"><?php echo $t->name; ?></option>
		<?php endforeach; ?>
				</select>
		<?php submit_button( 'エピソードのインポート' ); ?>
			</form>

			<form method="post" action="<?php admin_url( 'edit.php?post_type=podcast&page=wc4toiee-ssp-vimeo-import' ); ?>">
		<?php wp_nonce_field( 'update_options' ); ?>
				<input type="hidden" name="update_vimeo_list" value="true">
		<?php submit_button( 'vimeoのリスト一覧を更新' ); ?>
			</form>


			<h3>Vimeo API Setting</h3>
			<form method="post" action="<?php admin_url( 'edit.php?post_type=podcast&page=wc4toiee-ssp-vimeo-api' ); ?>">
		<?php wp_nonce_field( 'update_options' ); ?>
				<p><label>Client identifier</label><input type="text" name="ssp_vimeo_api_cid" value="<?php echo $vimeo_api_cid; ?>"></p>
				<p><label>Access token</label><input type="text" name="ssp_vimeo_api_access_token" value="<?php echo $vimeo_api_access_token; ?>"></p>
				<p><label>Client secrets</label><input type="text" name="ssp_vimeo_api_csr" value="<?php echo $vimeo_api_csr; ?>"></p>
		<?php submit_button( 'API Keyを保存' ); ?>
			</form>


		</div>
		<?php
	}

	function page_init_episodeupdate() {

	}


	/**
	 * episode の一覧数（seriesタクソノミーのアーカイブ表示の場合）を制御
	   表示数を増やす。
	 */
	public function change_episode_per_page( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->is_tax( 'series' ) ) {
			$query->set( 'posts_per_page', '20' ); // 表示件数を指定

			$term          = get_term_by( 'slug', $query->get( 'series' ), 'series' );
			$series_id     = $term->term_id;
			$consume_order = get_option( 'ss_podcasting_consume_order_' . $series_id, 'episodic' );

			if ( $consume_order == 'serial' ) { // 順序の変更
				$query->set( 'orderby', 'post_date' );
				$query->set( 'order', 'ASC' );
			} else {
				$query->set( 'orderby', 'post_date' );
				$query->set( 'order', 'DESC' );
			}
		}
	}

	function remove_tax_name( $title ) {

		if ( is_tax() ) {
			$title = single_cat_title( '', false );

		}

		return $title;

	}

	public function get_size( $size, $id ) {
		if ( $size == 1 ) {
			return get_post_meta( $id, 'filesize', true );
		} else {
			return $size;
		}
	}

	/*
	* Podcast feed のアクセス許可を出すために、ユーザーを識別する固有のIDを取得する。
	* もし、ユーザーが持っていなければ生成する
	*/
	public function get_user_wcrtoken() {

		if ( is_user_logged_in() ) {

			$user_id  = get_current_user_id();
			$wcrtoken = get_user_meta( $user_id, WC4T_WCRTOKEN, true );

			if ( $wcrtoken == '' ) {
				$wcrtoken = uniqid();
				update_user_meta( $user_id, WC4T_WCRTOKEN, $wcrtoken );
			}

			return $wcrtoken;
		}

		return null;
	}
}

