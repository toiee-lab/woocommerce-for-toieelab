<?php

/**
 * Scrum 機能を提供する
 */
class Toiee_Scrum_Post {

	/**
	 * タブを管理するための変数
	 *
	 * @var array
	 */
	public $tabs;

	/**
	 * プラグインのルートファイルを格納する
	 *
	 * @var string
	 */
	public $file;

	/**
	 * Toiee_Scrum_Post constructor.
	 *
	 * @param $file string
	 */
	public function __construct( $file ) {

		$this->file = $file;

		add_action( 'init', array( $this, 'cptui_register_my_cpts_scrum_post' ) );
		add_action( 'init', array( $this, 'cptui_register_my_taxes_scrum' ) );

		register_activation_hook( $file, array( $this, 'activate' ) );
		register_deactivation_hook( $file, array( $this, 'deactivate' ) );

		$this->add_acf();

		add_action( 'transition_post_status', array( $this, 'slack_notification' ), 10, 3 );
		add_action( 'wp_head', array( $this, 'noindex' ) );
	}

	/**
	 * カスタム投稿タイプ、タクソノミーを rewrite rule に登録する
	 */
	public function activate() {
		$this->cptui_register_my_cpts_scrum_post();
		$this->cptui_register_my_taxes_scrum();
		flush_rewrite_rules( true );
	}

	/**
	 * リライトルールをキャンセルする
	 */
	public function deactivate() {
		flush_rewrite_rules( true );
	}

	/**
	 * Scrum投稿、カテゴリは検索結果を除外するためのもの
	 */
	public function noindex() {
		if ( is_tax( 'scrum' ) || get_post_type() === 'scrum_post' ) {
			echo '<meta id="scrum-plugin" name="robots" content="noindex" />' . "\n";
		}
	}

	function cptui_register_my_cpts_scrum_post() {

		/**
		 * Post Type: スクラム投稿.
		 */

		$labels = array(
			'name'          => __( 'スクラム投稿', 'kanso general child' ),
			'singular_name' => __( 'スクラム投稿', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'スクラム投稿', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'スクラムを実施する際に投稿するデータ',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'exclude_from_search'   => true,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'scrum_post',
				'with_front' => true,
			),
			'query_var'             => true,
			'menu_icon'             => 'dashicons-groups',
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'scrum' ),
		);

		register_post_type( 'scrum_post', $args );
	}

	function cptui_register_my_taxes_scrum() {

		/**
		 * Taxonomy: スクラム.
		 */

		$labels = array(
			'name'          => __( 'スクラム', 'kanso general child' ),
			'singular_name' => __( 'スクラム', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'スクラム', 'kanso general child' ),
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'hierarchical'          => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'scrum',
				'with_front' => true,
			),
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'rest_base'             => 'scrum',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'show_in_quick_edit'    => false,
		);
		register_taxonomy( 'scrum', array( 'scrum_post' ), $args );
	}

	function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5c6f815000dc3',
					'title'                 => 'スクラム',
					'fields'                => array(
						array(
							'key'               => 'field_5c6f828407feb',
							'label'             => 'ヘッダー背景',
							'name'              => 'scrum_headerbg',
							'type'              => 'image',
							'instructions'      => 'スクラムのページのヘッダー画像です',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'return_format'     => 'array',
							'preview_size'      => 'medium',
							'library'           => 'all',
							'min_width'         => '',
							'min_height'        => '',
							'min_size'          => '',
							'max_width'         => '',
							'max_height'        => '',
							'max_size'          => '',
							'mime_types'        => '',
						),
						array(
							'key'               => 'field_5c764b5d6171c',
							'label'             => 'タイトル色',
							'name'              => 'title_color',
							'type'              => 'select',
							'instructions'      => 'タイトル、サブタイトルの色',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'uk-light' => '白',
								'uk-dark'  => '黒',
							),
							'default_value'     => array(
								0 => 'uk-dark',
							),
							'allow_null'        => 0,
							'multiple'          => 0,
							'ui'                => 0,
							'return_format'     => 'value',
							'ajax'              => 0,
							'placeholder'       => '',
						),
						array(
							'key'               => 'field_5c6f824607fea',
							'label'             => 'サブタイトル',
							'name'              => 'scrum_subtitle',
							'type'              => 'text',
							'instructions'      => 'スクラムのトップページに表示されるサブタイトルです',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '目標、ミッションを短く',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5c761f223d1da',
							'label'             => 'ファーストタグ',
							'name'              => 'first_tag_type',
							'type'              => 'select',
							'instructions'      => 'ページを開いたときに、アクティブにするタグを選んでください',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'getting_start' => 'はじめての方へ',
								'updates'       => '更新情報一覧',
								'materials'     => '教材一覧',
							),
							'default_value'     => array(
								0 => 'getting_start',
							),
							'allow_null'        => 0,
							'multiple'          => 0,
							'ui'                => 0,
							'return_format'     => 'value',
							'ajax'              => 0,
							'placeholder'       => '',
						),
						array(
							'key'               => 'field_5c761d7e3d1d6',
							'label'             => '初めての方へタブ',
							'name'              => 'getting-start-body',
							'type'              => 'wysiwyg',
							'instructions'      => '初めての方へタブに表示する内容を記載します。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'tabs'              => 'all',
							'toolbar'           => 'full',
							'media_upload'      => 1,
							'delay'             => 0,
						),
						array(
							'key'               => 'field_5c761dd43d1d7',
							'label'             => '更新情報一覧タブ',
							'name'              => 'updates_body',
							'type'              => 'wysiwyg',
							'instructions'      => '更新情報一覧の上部にメモを挿入することができます。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'tabs'              => 'all',
							'toolbar'           => 'full',
							'media_upload'      => 1,
							'delay'             => 0,
						),
						array(
							'key'               => 'field_5c761e1e3d1d8',
							'label'             => '更新情報(お知らせPodcast)',
							'name'              => 'updates_news_podcast',
							'type'              => 'taxonomy',
							'instructions'      => '更新情報一覧に表示する「お知らせ用」のPodcast（シリーズ）のIDを入力してください。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'series',
							'field_type'        => 'select',
							'allow_null'        => 1,
							'add_term'          => 0,
							'save_terms'        => 0,
							'load_terms'        => 0,
							'return_format'     => 'id',
							'multiple'          => 0,
						),
						array(
							'key'               => 'field_5c761ec13d1d9',
							'label'             => '更新情報(アーカイブPodcast)',
							'name'              => 'updates_archive_podcast',
							'type'              => 'taxonomy',
							'instructions'      => '更新情報一覧に表示する「お知らせ用」のPodcast（シリーズ）のIDを入力してください。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'series',
							'field_type'        => 'select',
							'allow_null'        => 1,
							'add_term'          => 0,
							'save_terms'        => 0,
							'load_terms'        => 0,
							'return_format'     => 'id',
							'multiple'          => 0,
						),
						array(
							'key'               => 'field_5c761fb13d1db',
							'label'             => '教材一覧タブ',
							'name'              => 'materials-body',
							'type'              => 'wysiwyg',
							'instructions'      => '教材一覧の上部にメモを挿入することができます。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'tabs'              => 'all',
							'toolbar'           => 'full',
							'media_upload'      => 1,
							'delay'             => 0,
						),
						array(
							'key'               => 'field_5c76205b98d78',
							'label'             => '教材一覧（特集）',
							'name'              => 'materials_featured',
							'type'              => 'taxonomy',
							'instructions'      => '特集（トップに固定）する Podcast の教材を選んでください。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'series',
							'field_type'        => 'checkbox',
							'add_term'          => 0,
							'save_terms'        => 0,
							'load_terms'        => 0,
							'return_format'     => 'id',
							'multiple'          => 0,
							'allow_null'        => 0,
						),
						array(
							'key'               => 'field_5c7620a498d79',
							'label'             => '教材一覧（耳デミー）',
							'name'              => 'materials_mimidemy',
							'type'              => 'taxonomy',
							'instructions'      => '特集（トップに固定）する Podcast の教材を選んでください。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'series',
							'field_type'        => 'checkbox',
							'add_term'          => 0,
							'save_terms'        => 0,
							'load_terms'        => 0,
							'return_format'     => 'id',
							'multiple'          => 0,
							'allow_null'        => 0,
						),
						array(
							'key'               => 'field_5c7620b898d7a',
							'label'             => '教材一覧（ポケてら）',
							'name'              => 'materials_pocketera',
							'type'              => 'taxonomy',
							'instructions'      => '特集（トップに固定）する Podcast の教材を選んでください。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'series',
							'field_type'        => 'checkbox',
							'add_term'          => 0,
							'save_terms'        => 0,
							'load_terms'        => 0,
							'return_format'     => 'id',
							'multiple'          => 0,
							'allow_null'        => 0,
						),
						array(
							'key'               => 'field_5c772f4ed8f07',
							'label'             => '管理者タブ',
							'name'              => 'admin-body',
							'type'              => 'wysiwyg',
							'instructions'      => 'スクラムマスターにしか見えないタブです。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'tabs'              => 'all',
							'toolbar'           => 'full',
							'media_upload'      => 1,
							'delay'             => 0,
						),
						array(
							'key'               => 'field_5c7aa1ff79381',
							'label'             => '【Slack自動通】Webhook URL',
							'name'              => 'scrum_slack_webhook',
							'type'              => 'url',
							'instructions'      => 'Slackの「Incoming Webhook」で設定し、取得したURLを記載してください',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => 'https://hook.slack.com/...',
						),
						array(
							'key'               => 'field_5c7aa28779382',
							'label'             => '【Slack自動通知】ブログ',
							'name'              => 'scrum_slack_notification_blog',
							'type'              => 'textarea',
							'instructions'      => '%URL% で「記事へのリンク」を、%TITLE% で「記事のタイトル」を、%SCRUM% で「スクラムサイト」',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => ':spiral_note_pad: *ブログを更新しました！* 
内容は、以下の通りです
```
タイトル : %TITLE%
内容 :
%DESP%
```

<%URL%|Webサイト> でご覧ください！
',
							'placeholder'       => '',
							'maxlength'         => '',
							'rows'              => '',
							'new_lines'         => '',
						),
						array(
							'key'               => 'field_5c7aa30e79383',
							'label'             => '【Slack自動通知】お知らせPodcast',
							'name'              => 'scrum_slack_notification_news_podcast',
							'type'              => 'textarea',
							'instructions'      => '%EPISODE_URL% : エピソードのURL、%EPISODE_TITLE% : エピソードのタイトル、%EPISODE_DESP% : エピソードの内容、 %SERIES_URL% : シリーズのURL、%SERIES_TITLE% : シリーズのタイトル',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => ':studio_microphone: *お知らせPodcastを追加しました！*
内容は以下の通りです。

```
タイトル : %EPISODE_TITLE%
内容 :
%EPISODE_DESP%
```

<%SERIES_URL%|Webで視聴> するか、Podcastアプリで、どうぞ！！',
							'placeholder'       => '',
							'maxlength'         => '',
							'rows'              => '',
							'new_lines'         => '',
						),
						array(
							'key'               => 'field_5c7aa36579384',
							'label'             => '【Slack自動通知】アーカイブPodcast',
							'name'              => 'scrum_slack_notification_archive_podcast',
							'type'              => 'textarea',
							'instructions'      => '%EPISODE_URL% : エピソードのURL、%EPISODE_TITLE% : エピソードのタイトル、%EPISODE_DESP% : エピソードの内容、 %SERIES_URL% : シリーズのURL、%SERIES_TITLE% : シリーズのタイトル',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => ':studio_microphone: *アーカイブPodcastを追加しました！*
内容は以下の通りです。

```
タイトル : %EPISODE_TITLE%
内容 :
%EPISODE_DESP%
```

<%SERIES_URL%|Webで視聴> するか、Podcastアプリで、どうぞ！！',
							'placeholder'       => '',
							'maxlength'         => '',
							'rows'              => '',
							'new_lines'         => '',
						),
					),
					'location'              => array(
						array(
							array(
								'param'    => 'taxonomy',
								'operator' => '==',
								'value'    => 'scrum',
							),
						),
					),
					'menu_order'            => 0,
					'position'              => 'normal',
					'style'                 => 'default',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'hide_on_screen'        => '',
					'active'                => true,
					'description'           => '',
				)
			);

		endif;
	}

	function slack_notification( $new_status, $old_status, $post ) {

		// 更新以外の「公開」
		if ( $new_status == 'publish' && $old_status != 'publish' ) {

			// scrum_post の場合
			if ( $post->post_type == 'scrum_post' ) {

				$scrum = wp_get_post_terms( $post->ID, 'scrum' );
				if ( is_wp_error( $scrum ) ) {
					return null;
				}

				$scrum_fields = get_fields( $scrum[0] );
				if ( $scrum_fields['scrum_slack_webhook'] == '' ) {
					return null;
				}

				$webhook_url = $scrum_fields['scrum_slack_webhook'];

				$scrum_url = get_term_link( $scrum[0] );
				$url       = get_permalink( $post );
				$post_desp = substr( wp_strip_all_tags( $post->post_content ), 0, 300 );

				$message = str_replace(
					array( '%SCRUM%', '%URL%', '%TITLE%', '%DESP%' ),
					array( $scrum_url, $url, $post->post_title, $post_desp ),
					$scrum_fields['scrum_slack_notification_blog']
				);
				$this->send_slack( $message, $webhook_url );
			}

			// エピソードの場合
			if ( $post->post_type == 'podcast' ) {

				// エピソードのurl
				$episode_url  = get_permalink( $post->ID );
				$episode_desp = mb_substr( wp_strip_all_tags( $post->post_content ), 0, 150 );

				// エピソードの series を取得する
				$rets = wp_get_post_terms( $post->ID, 'series' );
				if ( is_wp_error( $rets ) ) {
					return null;
				}

				foreach ( $rets as $series ) {
					$series_url = get_term_link( $series );

					$replace = array(
						'episode_url'   => $episode_url,
						'episode_title' => $post->post_title,
						'episode_desp'  => $episode_desp,
						'series_url'    => $series_url,
						'series_title'  => $series->name,
					);

					// お知らせ Podcastに $series を登録している scrum を見つける
					$scrums = $this->get_scrums_by_series_id( $series->term_id, 'updates_news_podcast' );
					// 見つかった scrums に通知を送る
					$this->send_slack_from_scrums( $scrums, 'scrum_slack_notification_news_podcast', $replace );

					// アーカイブ podcast の通知
					$scrums = $this->get_scrums_by_series_id( $series->term_id, 'updates_archive_podcast' );
					$this->send_slack_from_scrums( $scrums, 'scrum_slack_notification_archive_podcast', $replace );
				}
			}
		}
	}

	function get_scrums_by_series_id( $series_id, $name ) {
		$scrums = get_terms(
			array(
				'taxonomy'   => 'scrum',
				'hide_empty' => false,
				'meta_key'   => $name,
				'meta_value' => $series_id,
			)
		);

		return $scrums;
	}

	function send_slack_from_scrums( $scrums, $name, $replace ) {

		foreach ( $scrums as $scrum ) {
			$webhook_url = get_field( 'scrum_slack_webhook', $scrum );
			$keys        = array_keys( $replace );
			$search      = array_map(
				function ( $str ) {
					return '%' . strtoupper( $str ) . '%';
				},
				$keys
			);

			if ( $webhook_url != null ) {
				$body = get_field( $name, $scrum );
				$body = str_replace(
					$search,
					$replace,
					$body
				);

				   $this->send_slack( $body, $webhook_url );
			}
		}
	}

	function send_slack( $body, $webhook_url ) {
		$message = array( 'text' => $body );

		$options  = array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Content-Type: application/json',
				'content' => json_encode( $message ),
			),
		);
		$response = file_get_contents( $webhook_url, false, stream_context_create( $options ) );
		return $response === 'ok';
	}
}
