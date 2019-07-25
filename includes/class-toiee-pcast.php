<?php

/**
 * Class Toiee_Pcast.
 * Podcast機能を付属させるための機能を持ったクラス.
 */
class Toiee_Pcast {
	/**
	 * プラグインのルートファイル名を保存
	 *
	 * @var string $file
	 */
	private $file;

	/**
	 * この変数は、 https://.../feed/{feed_slug} を指定
	 *
	 * @var string $feed_slug
	 */
	private $feed_slug;
	/**
	 * プラグインのディレクトリパスを格納
	 *
	 * @var string $plugin_dir_path
	 */
	private $plugin_dir_path;
	/**
	 * プラグインのURLを格納(with trailing slash)
	 * @var string $plugin_url
	 */
	private $plugin_url;
	/**
	 * 閲覧制限時のオーディオファイルを指定
	 *
	 * @var string $dummy_audio
	 */
	private $dummy_audio;

	/**
	 * Vimeoへアクセスできるオブジェクトを格納.
	 *
	 * @var $vimeo object Vimeoオブジェクト.
	 */
	private $vimeo;

	/**
	 * Toiee_Pcast constructor.
	 *
	 * @param string $file プラグインのルートファイルのパス
	 */
	public function __construct( $file ) {

		$this->file            = $file;
		$this->feed_slug       = 'pcast';
		$this->plugin_dir_path = plugin_dir_path( $file );
		$this->plugin_url      = plugin_dir_url( $file );
		$this->dummy_audio     = $this->plugin_dir_path . 'images/not-available.m4a';

		$this->add_acf();

		add_action( 'init', array( $this, 'add_feed' ), 1 );
		add_filter( 'query_vars', array( $this, 'custom_query_vars_filter' ) );

		register_activation_hook( $file, array( $this, 'activate' ) );
		register_deactivation_hook( $file, array( $this, 'deactivate' ) );

		add_action( 'acf/save_post', array( $this, 'store_parameters' ), 20 );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'toiee_podcast_add_plugin_page' ) );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_plyer_dot_io_style_and_script' ) );

	}

	public function store_parameters( $post_id, $try_http = false ) {

		$do_store = true;
		$do_store = apply_filters( 'pcast_store_parameter', $do_store );

		if ( true !== $do_store ) {
			return;
		}

		$post = get_post( $post_id );
		if ( array_search( $post->post_type, $this->toiee_pcast_post_types() ) ) {
			$fields = get_fields();

			if ( ( '' === $fields['duration'] ) || ( '' === $fields['length'] ) ) {

				/* attachment にあれば */
				$att_id = attachment_url_to_postid( $fields['enclosure'] );
				if ( 0 !== $att_id ) { // duration, length
					$att_meta           = get_post_meta( $att_id, '_wp_attachment_metadata', true );
					$fields['duration'] = isset( $att_meta['length_formatted'] ) ? $att_meta['length_formatted'] : '';
					$fields['length']   = isset( $att_meta['filesize'] ) ? $att_meta['filesize'] : '';
				}
			}

			/* この処理は時間がかかる */
			if ( $try_http ) {

				$location = $fields['enclosure'];
				$headers  = array();
				for ( $i = 0; $i < 5; $i++ ) { // 5回までのリダイレクトを処理する
					$headers = get_headers( $location, 1 );
					if ( isset( $headers['Location'] ) ) {
						$location = $headers['Location'];
					} else {
						break;
					}
				}

				$fields['length'] = isset( $headers['Content-Length'] ) ? $headers['Content-Length'] : '';
			}

			foreach ( array( 'duration', 'length' ) as $f ) {
				update_field( $f, $fields[ $f ], $post_id );
			}
		}
	}

	public function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

			$post_types          = $this->toiee_pcast_post_types();
			$pcast_post_location = array();
			foreach ( $post_types as $type ) {
				$pcast_post_location[] = array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => $type,
					),
				);
			}

			$taxes              = $this->toiee_pcast_taxonomy();
			$pcast_tax_location = array();
			foreach ( $taxes as $tax ) {
				$pcast_tax_location[] = array(
					array(
						'param'    => 'taxonomy',
						'operator' => '==',
						'value'    => $tax,
					),
				);
			}

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5cabc2921ca53',
					'title'                 => 'Podcastアイテム設定',
					'fields'                => array(
						array(
							'key'               => 'field_5cabc2922632b',
							'label'             => '閲覧制限する',
							'name'              => 'restrict',
							'type'              => 'select',
							'instructions'      => '閲覧制限をする、しないを指定します',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'restrict' => '購読者会員のみ',
								'free'     => '無料登録会員',
								'open'     => '公開',
							),
							'default_value'     => array(
								0 => 'restrict',
							),
							'allow_null'        => 0,
							'multiple'          => 0,
							'ui'                => 1,
							'ajax'              => 0,
							'return_format'     => 'value',
							'placeholder'       => '',
						),
						array(
							'key'               => 'field_5cabc2922635d',
							'label'             => 'サブタイトル',
							'name'              => 'subtitle',
							'type'              => 'text',
							'instructions'      => 'itunes:subtitle タグに使います',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5cabc2922638b',
							'label'             => 'メディアリンク',
							'name'              => 'enclosure',
							'type'              => 'url',
							'instructions'      => 'enclosure タグに使います。ビデオ、オーディオのURLを指定します',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => 'https://',
						),
						array(
							'key'               => 'field_5cabc292263b8',
							'label'             => 'メディアの種類',
							'name'              => 'media',
							'type'              => 'select',
							'instructions'      => 'メディアの種類を設定します',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'audio' => '音声',
								'video' => 'ビデオ',
								'pdf'   => 'PDF',
							),
							'default_value'     => array(
								0 => 'video',
							),
							'allow_null'        => 0,
							'multiple'          => 0,
							'ui'                => 0,
							'return_format'     => 'value',
							'ajax'              => 0,
							'placeholder'       => '',
						),
						array(
							'key'               => 'field_5cabc292263f8',
							'label'             => '再生時間',
							'name'              => 'duration',
							'type'              => 'text',
							'instructions'      => 'HH:MM:SS (Hour, Minutes, Second) で指定します',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '00:15:23',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5cabc29226426',
							'label'             => 'サイズ',
							'name'              => 'length',
							'type'              => 'number',
							'instructions'      => 'メディアのファイルサイズを指定します',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'min'               => '',
							'max'               => '',
							'step'              => '',
						),
						array(
							'key'               => 'field_5cabc29226453',
							'label'             => '閲覧注意',
							'name'              => 'explicit',
							'type'              => 'true_false',
							'instructions'      => '閲覧注意タグ（ itunes:explicit ) をTrue、falseにする',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'message'           => '',
							'default_value'     => 0,
							'ui'                => 0,
							'ui_on_text'        => '',
							'ui_off_text'       => '',
						),
					),
					'location'              => $pcast_post_location,
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

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5cabbf9053f21',
					'title'                 => 'Podcastチャンネル設定',
					'fields'                => array(
						array(
							'key'               => 'field_5cabbf905fc4b',
							'label'             => '公開・非公開',
							'name'              => 'published',
							'type'              => 'true_false',
							'instructions'      => 'ポケてら一覧に表示する、しない。登録作業が一通り終わるまでは、非公開にしてください。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'message'           => '',
							'default_value'     => 0,
							'ui'                => 1,
							'ui_on_text'        => '',
							'ui_off_text'       => '',
						),
						array(
							'key'               => 'field_5cabbf905fc85',
							'label'             => 'アイテム共通テキスト',
							'name'              => 'items_notification',
							'type'              => 'wysiwyg',
							'instructions'      => '所属するアイテムすべてに挿入するテキストです',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'tabs'              => 'all',
							'toolbar'           => 'basic',
							'media_upload'      => 1,
							'delay'             => 0,
						),
						array(
							'key'               => 'field_5cabbf905fcb7',
							'label'             => 'タグ',
							'name'              => 'タグ',
							'type'              => 'text',
							'instructions'      => 'カンマ区切りで指定してください。ポケてらの Filter や Sort に利用します',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => 'WordPress,入門,タグ1,タグ2,...',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5cabbf905fce9',
							'label'             => '閲覧制限',
							'name'              => 'restrict',
							'type'              => 'true_false',
							'instructions'      => '閲覧制限対象か、そうでないか',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'message'           => '',
							'default_value'     => 0,
							'ui'                => 1,
							'ui_on_text'        => '',
							'ui_off_text'       => '',
						),
						array(
							'key'               => 'field_5cabbf905fd30',
							'label'             => 'アクセス許可商品',
							'name'              => 'restrict_product',
							'type'              => 'post_object',
							'instructions'      => '閲覧制限を許可する商品を選択してください',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'post_type'         => array(
								0 => 'wcrestrict',
								1 => 'shop_subscription',
								2 => 'product',
								3 => 'product_variation',
							),
							'taxonomy'          => '',
							'allow_null'        => 0,
							'multiple'          => 1,
							'return_format'     => 'id',
							'ui'                => 1,
						),
						array(
							'key'               => 'field_5cabbf905fd62',
							'label'             => '[Podcast] 言語',
							'name'              => 'language',
							'type'              => 'text',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => 'ja',
							'placeholder'       => 'ja',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5cabbf905fd94',
							'label'             => '[Podcast] コピーライト',
							'name'              => 'copyright',
							'type'              => 'text',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => 'toiee Lab',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5cabbf905fdc5',
							'label'             => '[Podcast] サブタイトル',
							'name'              => 'subtitle',
							'type'              => 'text',
							'instructions'      => 'サブタイトルです',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5cabbf905fdf7',
							'label'             => '[Podcast] Author',
							'name'              => 'author',
							'type'              => 'text',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => 'toiee Lab',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5cabbf905fe29',
							'label'             => '[Podcast] オーナー名',
							'name'              => 'owner_name',
							'type'              => 'text',
							'instructions'      => 'ituens:owner > itunes:name に使われます',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => 'toiee Lab',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5cabbf905fe67',
							'label'             => '[Podcast] オーナーアドレス',
							'name'              => 'owner_email',
							'type'              => 'text',
							'instructions'      => 'itunes:owner > itunes:email に使われます。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => 'desk@toiee.jp',
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => '',
						),
						array(
							'key'               => 'field_5cabbf905fea5',
							'label'             => '[Podcast] アートワーク',
							'name'              => 'image',
							'type'              => 'image',
							'instructions'      => '1400 x 1400 を推奨（正方形）',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'return_format'     => 'url',
							'preview_size'      => 'thumbnail',
							'library'           => 'all',
							'min_width'         => '',
							'min_height'        => '',
							'min_size'          => '',
							'max_width'         => '',
							'max_height'        => '',
							'max_size'          => '',
							'mime_types'        => 'jpg,png',
						),
						array(
							'key'               => 'field_5cabbf905fed5',
							'label'             => '[Podcast] カテゴリ',
							'name'              => 'category',
							'type'              => 'select',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'Arts'                     => 'Arts',
								'Arts > Design'            => 'Arts > Design',
								'Arts > Fashion & Beauty'  => 'Arts > Fashion & Beauty',
								'Arts > Food'              => 'Arts > Food',
								'Arts > Literature'        => 'Arts > Literature',
								'Arts > Performing Arts'   => 'Arts > Performing Arts',
								'Arts > Visual Arts'       => 'Arts > Visual Arts',
								'Business'                 => 'Business',
								'Business > Business News' => 'Business > Business News',
								'Business > Careers'       => 'Business > Careers',
								'Business > Investing'     => 'Business > Investing',
								'Business > Management & Marketing' => 'Business > Management & Marketing',
								'Business > Shopping'      => 'Business > Shopping',
								'Comedy'                   => 'Comedy',
								'Education'                => 'Education',
								'Education > Educational Technology' => 'Education > Educational Technology',
								'Education > Higher Education' => 'Education > Higher Education',
								'Education > K-12'         => 'Education > K-12',
								'Education > Language Courses' => 'Education > Language Courses',
								'Education > Training'     => 'Education > Training',
								'Games & Hobbies'          => 'Games & Hobbies',
								'Games & Hobbies > Automotive' => 'Games & Hobbies > Automotive',
								'Games & Hobbies > Aviation' => 'Games & Hobbies > Aviation',
								'Games & Hobbies > Hobbies' => 'Games & Hobbies > Hobbies',
								'Games & Hobbies > Other Games' => 'Games & Hobbies > Other Games',
								'Games & Hobbies > Video Games' => 'Games & Hobbies > Video Games',
								'Government & Organizations' => 'Government & Organizations',
								'Government & Organizations > Local' => 'Government & Organizations > Local',
								'Government & Organizations > National' => 'Government & Organizations > National',
								'Government & Organizations > Non-Profit' => 'Government & Organizations > Non-Profit',
								'Government & Organizations > Regional –' => 'Government & Organizations > Regional –',
								'Health'                   => 'Health',
								'Health > Alternative Health' => 'Health > Alternative Health',
								'Health > Fitness & Nutrition' => 'Health > Fitness & Nutrition',
								'Health > Self-Help'       => 'Health > Self-Help',
								'Health > Sexuality'       => 'Health > Sexuality',
								'Health > Kids & Family'   => 'Health > Kids & Family',
								'Music'                    => 'Music',
								'News & Politics'          => 'News & Politics',
								'News & Politics > Religion & Spirituality' => 'News & Politics > Religion & Spirituality',
								'News & Politics > Religion & Spirituality > Buddhism' => 'News & Politics > Religion & Spirituality > Buddhism',
								'News & Politics > Religion & Spirituality > Christianity' => 'News & Politics > Religion & Spirituality > Christianity',
								'News & Politics > Religion & Spirituality > Hinduism' => 'News & Politics > Religion & Spirituality > Hinduism',
								'News & Politics > Religion & Spirituality > Islam' => 'News & Politics > Religion & Spirituality > Islam',
								'News & Politics > Religion & Spirituality > Judaism' => 'News & Politics > Religion & Spirituality > Judaism',
								'News & Politics > Religion & Spirituality > Other' => 'News & Politics > Religion & Spirituality > Other',
								'News & Politics > Religion & Spirituality > Spirituality' => 'News & Politics > Religion & Spirituality > Spirituality',
								'Science & Medicine'       => 'Science & Medicine',
								'Science & Medicine > Medicine' => 'Science & Medicine > Medicine',
								'Science & Medicine > Natural Sciences' => 'Science & Medicine > Natural Sciences',
								'Science & Medicine > Social Sciences' => 'Science & Medicine > Social Sciences',
								'Society & Culture'        => 'Society & Culture',
								'Society & Culture > History' => 'Society & Culture > History',
								'Society & Culture > Personal Journals' => 'Society & Culture > Personal Journals',
								'Society & Culture > Philosophy' => 'Society & Culture > Philosophy',
								'Society & Culture > Places & Travel' => 'Society & Culture > Places & Travel',
								'Sports & Recreation'      => 'Sports & Recreation',
								'Sports & Recreation > Amateur' => 'Sports & Recreation > Amateur',
								'Sports & Recreation > College & High School' => 'Sports & Recreation > College & High School',
								'Sports & Recreation > Outdoor' => 'Sports & Recreation > Outdoor',
								'Sports & Recreation > Professional' => 'Sports & Recreation > Professional',
								'Sports & Recreation > TV & Film' => 'Sports & Recreation > TV & Film',
								'Technology'               => 'Technology',
								'Technology > Gadgets'     => 'Technology > Gadgets',
								'Technology > Podcasting'  => 'Technology > Podcasting',
								'Technology > Software How-To' => 'Technology > Software How-To',
								'Technology > Tech News'   => 'Technology > Tech News',
							),
							'default_value'     => array(),
							'allow_null'        => 0,
							'multiple'          => 0,
							'ui'                => 1,
							'ajax'              => 0,
							'return_format'     => 'value',
							'placeholder'       => '',
						),
						array(
							'key'               => 'field_5cabbf905ff04',
							'label'             => '[Podcast] 閲覧注意',
							'name'              => 'explicit',
							'type'              => 'true_false',
							'instructions'      => '閲覧注意（itunes:explicit）を追加する',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'message'           => '',
							'default_value'     => 0,
							'ui'                => 1,
							'ui_on_text'        => '',
							'ui_off_text'       => '',
						),
						array(
							'key'               => 'field_5cabbf905ff7d',
							'label'             => '[Podcast] itunes非表示',
							'name'              => 'block',
							'type'              => 'true_false',
							'instructions'      => 'itunesストアに表示されないように、ブロックする',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'message'           => '',
							'default_value'     => 1,
							'ui'                => 1,
							'ui_on_text'        => '',
							'ui_off_text'       => '',
						),
						array(
							'key'               => 'field_5cabbf905ffb6',
							'label'             => '[Podcast] エピソードタイプ',
							'name'              => 'episode_type',
							'type'              => 'select',
							'instructions'      => 'itunes:type を指定します。セミナーは、 serial を指定します。随時更新するものは、 episodic を指定します',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'serial'   => 'serial',
								'episodic' => 'episodic',
							),
							'default_value'     => array(
								0 => 'serial',
							),
							'allow_null'        => 0,
							'multiple'          => 0,
							'ui'                => 1,
							'ajax'              => 0,
							'return_format'     => 'value',
							'placeholder'       => '',
						),
					),
					'location'              => $pcast_tax_location,
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

			acf_add_local_field_group(array(
				'key' => 'group_5d1ead902d5a6',
				'title' => 'オーディオブック',
				'fields' => array(
					array(
						'key' => 'field_5d1ead995cc23',
						'label' => 'オーディオブック',
						'name' => 'audiobook',
						'type' => 'file',
						'instructions' => 'オーディオブックを指定してください（m4b）',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'return_format' => 'url',
						'library' => 'all',
						'min_size' => '',
						'max_size' => '',
						'mime_types' => 'm4b',
						'show_column' => 0,
						'show_column_weight' => 1000,
						'allow_quickedit' => 0,
						'allow_bulkedit' => 0,
					),
				),
				'location' => $pcast_tax_location,
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
				'active' => true,
				'description' => '',
			));

		endif;
	}

	public function toiee_pcast_post_types() {
		$post_types = array( 'mdy_episode', 'pkt_episode', 'scrum_episode', 'tlm_in', 'tlm_ws', 'tlm_archive', 'kdy_item' );
		$post_types = apply_filters( 'toiee_pcast_post_types', $post_types );

		return $post_types;
	}

	public function toiee_pcast_taxonomy() {
		$tax = array( 'mdy_channel', 'pkt_channel', 'scrum_channel', 'tlm', 'kdy' );
		$tax = apply_filters( 'toiee_pcast_taxonomy', $tax );

		return $tax;
	}

	public function toiee_pcast_relations() {
		$relations = array(
			'mdy_channel'   => 'mdy_episode',
			'pkt_channel'   => 'pkt_episode',
			'scrum_channel' => 'scrum_episode',
			'tlm'           => array( 'tlm_in', 'tlm_ws', 'tlm_archive' ),
			'kdy'           => 'kdy_item',
		);
		$relations = apply_filters( 'toiee_pcast_relations', $relations );

		return $relations;
	}

	public function custom_query_vars_filter( $vars ) {
		$vars[] .= 'wcrtoken';
		return $vars;
	}

	public function add_feed() {
		add_feed( $this->feed_slug, array( $this, 'feed_template' ) );
	}

	public function feed_template() {
		global $wp_query;

		/* Prevent 404 on feed */
		$wp_query->is_404 = false;
		status_header( 200 );

		$default_template = $this->plugin_dir_path . 'templates/feed-pcast.php';

		$user_template_file = apply_filters( 'toiee_pcast_feed_template_file', $default_template );

		if ( file_exists( $user_template_file ) ) {
			require $user_template_file;
		} else {
			require $default_template;
		}

		exit;
	}

	public function activate() {
		$this->add_feed();

		flush_rewrite_rules( true );
	}

	public function deactivate() {
		flush_rewrite_rules();
	}

	public function get_plugin_path() {
		return $this->plugin_dir_path;
	}

	public function get_dummy_audio() {
		return $this->dummy_audio;
	}

	public function get_restrcit_message() {
		$message = '【会員限定】';
		return apply_filters( 'toiee_get_restrict_message', $message );
	}


	/* podcast設定 */

	public function toiee_podcast_add_plugin_page() {
		add_options_page(
			'toiee podcast', // page_title.
			'toiee podcast', // menu_title.
			'manage_options', // capability.
			'toiee-podcast', // menu_slug.
			array( $this, 'toiee_podcast_create_admin_page' ) // function.
		);
	}

	public function toiee_podcast_create_admin_page() {
		?>
		<!-- Create a header in the default WordPress 'wrap' container -->
		<div class="wrap">

			<div id="icon-themes" class="icon32"></div>
			<h2>toiee Podcast設定</h2>
			<?php settings_errors(); ?>
			<?php
			if ( isset( $_GET['tab'] ) ) {
				$active_tab = $_GET['tab'];
			} else {
				$active_tab = 'general';
			}
			?>
			<h2 class="nav-tab-wrapper">
				<a href="?page=toiee-podcast&tab=general" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">概要</a>
				<a href="?page=toiee-podcast&tab=store-param" class="nav-tab <?php echo 'store-param' === $active_tab ? 'nav-tab-active' : ''; ?>">エピソードの更新</a>
				<a href="?page=toiee-podcast&tab=vimeo-import" class="nav-tab <?php echo 'vimeo-import' === $active_tab ? 'nav-tab-active' : ''; ?>">Vimeoインポート</a>
				<a href="?page=toiee-podcast&tab=media-import" class="nav-tab <?php echo 'media-import' === $active_tab ? 'nav-tab-active' : ''; ?>">メディアインポート</a>
				<a href="?page=toiee-podcast&tab=pktmdy-import" class="nav-tab <?php echo 'pktmdy-import' === $active_tab ? 'nav-tab-active' : ''; ?>">ポケ・耳インポート</a>
				<a href="?page=toiee-podcast&tab=ssp-import" class="nav-tab <?php echo 'ssp-import' === $active_tab ? 'nav-tab-active' : ''; ?>">SSPインポート</a>
				<a href="?page=toiee-podcast&tab=vimeo-api" class="nav-tab <?php echo 'vimeo-api' === $active_tab ? 'nav-tab-active' : ''; ?>">vimeo api</a>
			</h2>
			<?php
			switch ( $active_tab ) {
				case 'store-param':
					$this->store_param();
					break;
				case 'vimeo-import':
					$this->import_from_vimeo();
					break;
				case 'media-import':
					$this->import_from_media();
					break;
				case 'pktmdy-import':
					$this->import_pktmdy();
					break;
				case 'ssp-import':
					$this->import_from_series();
					break;
				case 'vimeo-api':
					$this->update_vimeo_api();
					break;
				default:
					$this->display_general();

			}
			?>
		</div><!-- /.wrap -->
		<?php
	}

	private function display_general() {
		?>
		<div style="max-width:600px">
			<h2>toiee Podcastについて</h2>
			<p>toiee Podcast は、ACFプラグインを活用して、任意のカスタム投稿タイプ、タクソノミーに、podcastのためのフィールドを追加し、Podcast機能を利用できるようにしています。</p>
			<p>デフォルトでは、耳デミー(mdy_channel, mdy_episode)、ポケてら(pkt_channel, pkt_episode）、スクラム(scrum_channel, scrum_episode)です。</p>
			<p>ここでは、vimeo、メディア、Seriously Simple Podcast からデータをインポートする機能も提供します。</p>

			<h3>Seriously Simple Podcast について</h3>
			<p>toiee Podcastの仕組みを作る前は、Seriously Simple Podcast を活用していました。過去のデータが SSPにありますが、移動させたものは「移動済み」とカテゴリ一覧で表示されます。</p>
		</div>
		<?php
	}

	private function store_param() {

		if ( isset( $_POST['cmd'] ) && 'store-param' === $_POST['cmd'] ) {
			check_admin_referer( 'toiee_podcast' );

			$dat     = explode( ',', esc_attr( $_POST['target_channel'] ) );
			$tax     = $dat[0];
			$term_id = $dat[1];

			// episode を取得する
			$relations = $this->toiee_pcast_relations();
			$post_type = $relations[ $tax ];

			$posts = get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => -1,
					'order'          => 'ASC',
					'post_status'    => 'publish',
					'tax_query'      => array(
						array(
							'field'    => 'term_id',
							'terms'    => $term_id,
							'taxonomy' => $tax,
						),
					),
				)
			);

			foreach ( $posts as $p ) {
				$this->store_parameters( $p->ID, true );
			}
		}

		$select = $this->get_pcast_taxes_select();
		?>
		<p>エピソードの duration と length を設定します。</p>
		<form method="post" action="<?php admin_url( 'options-general.php?page=toiee-podcast&tab=store-param' ); ?>">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="my-text-field">更新するチャンネル</label>
					</th>
					<td>
						<select name="target_channel">
							<?php foreach ( $select as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_attr( $option['disp'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<br>
						<span class="description">チャンネルに所属するエピソードを更新します</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-text-field">オプション</label>
					</th>
					<td>
						<label><input type="checkbox" name="skip" value="" /> 設定済みをスキップ</label>
						<br>
						<span class="description">チェックすると、既に設定されているエピソードをスキップします。更新速度が早くなります。</span>
					</td>
				</tr>
				</tbody>
			</table>
			<?php wp_nonce_field( 'toiee_podcast' ); ?>
			<input type="hidden" name="cmd" value="store-param" />
			<?php submit_button( '実行' ); ?>
		</form>
		<?php
	}

	private function import_from_vimeo() {
		if ( isset( $_POST['cmd'] ) && $_POST['cmd'] === 'vimeo-import' ) {
			check_admin_referer( 'toiee_podcast' );

			if ( isset( $_POST['mode'] ) && $_POST['mode'] === 'update-vimeo-list' ) {
				$this->get_vimeo_list();
			} else {

				/* インポートプログラム */
				$lib = $this->get_vimeo_object();

				/* データの取得し、名前で並び替える */
				$ret    = $lib->request( $_POST['source_channel'] . '/videos', [ 'per_page' => 100 ], 'GET' );
				$videos = $ret['body']['data'];
				usort(
					$videos,
					function ( $a, $b ) {
						return strnatcmp( $a['name'], $b['name'] );
					}
				);

				/* 登録作業 */
				$cnt   = 0;
				$total = count( $videos );
				$time  = time() - 60 * ( $total + 2 );

				/* 登録先情報 */
				$tt        = $this->get_pcast_tax( $_POST['target_channel'] );
				$term      = get_term_by( 'id', $tt['term_id'], $tt['tax'] );
				$post_type = $tt['post_type'];

				/* 登録開始 */
				foreach ( $videos as $i => $v ) {
					$cnt++;
					$time += 60;
					$att   = array();

					$post_title      = $v['name'];
					$att['media']    = 'video';
					$att['restrict'] = 'restrict';
					$att['duration'] = sprintf( '%02d:%02d:%02d', floor( $v['duration'] / 3600 ), floor( ( $v['duration'] / 60 ) % 60 ), $v['duration'] % 60 );
					foreach ( $v['files'] as $d ) {
						if ( 'hd' === $d['quality'] ) {
							$link             = preg_replace( '/&oauth2_token_id=([0-9]+)/', '', $d['link'] ) . '&download=1';
							$att['enclosure'] = $link;
							$att['length']    = $d['size'];
							break;
						}
					}

					$arg     = array(
						'ID'           => null,
						'post_content' => '',
						'post_name'    => $term->slug . '-' . $cnt,
						'post_title'   => $post_title,
						'post_status'  => 'publish',
						'post_type'    => $post_type,
						'post_date'    => date( 'Y-m-d H:i:s', $time ),
						'tax_input'    => array( $tt['tax'] => $term->term_id ),
					);
					$post_id = wp_insert_post( $arg );

					if ( $post_id ) {
						foreach ( $att as $k => $v ) {
							update_field( $k, $v, $post_id );
						}
					}
				}

				$edit_url = admin_url( 'edit.php?' . $tt['tax'] . '=' . $term->slug . '&post_type=' . $post_type );
				?>
				<div class="notice notice-success is-dismissible">
					<p><strong>登録作業が完了しました。</strong> <a href="<?php echo esc_url( $edit_url ); ?>">結果はこちら</a></p>
				</div>
				<?php
			}
		}

		$select_from = get_option( 'toiee_vimeo_list', false );

		if ( false === $select_from ) {
			$error       = '<div class="notice notice-warning is-dismissible"><p><strong>vimeo にアクセスできませんでした。api key などを確認して、再度お試しください。</strong></p></div>';
			$select_from = array();
		} else {
			$error = '';
		}

		$select_to = $this->get_pcast_taxes_select();

		?>
		<p>vimeoのプロジェクトや、アルバムからインポートします。まずは、vimeoのプレイリストの更新から行ってください。<br>
		<form method="post" action="<?php admin_url( 'options-general.php?page=toiee-podcast&tab=vimeo-import' ); ?>">
			<?php wp_nonce_field( 'toiee_podcast' ); ?>
			<input type="hidden" name="cmd" value="vimeo-import" />
			<input type="hidden" name="mode" value="update-vimeo-list" />
			<?php submit_button( 'vimeoに接続して、リストをアップデートする' ); ?>
		</form>
		</p>
		<?php echo $error; ?>
		<form method="post" action="<?php admin_url( 'options-general.php?page=toiee-podcast&tab=vimeo-import' ); ?>">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="my-text-field">インポート元</label>
					</th>
					<td>
						<select name="source_channel">
							<?php foreach ( $select_from as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_attr( $option['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<br>
						<span class="description">チャンネルに所属するエピソードを更新します</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-text-field">インポート先</label>
					</th>
					<td>
						<select name="target_channel">
							<?php foreach ( $select_to as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_attr( $option['disp'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<br>
						<span class="description"></span>
					</td>
				</tr>
				</tbody>
			</table>
			<?php wp_nonce_field( 'toiee_podcast' ); ?>
			<input type="hidden" name="cmd" value="vimeo-import" />
			<?php submit_button( '実行' ); ?>
		</form>
		<?php
	}

	private function import_from_series() {
		if ( isset( $_POST['cmd'] ) && 'ssp-import' === $_POST['cmd'] ) {
			check_admin_referer( 'toiee_podcast' );

			$seres_id = $_POST['from_channel'];
			$taxonomy = $_POST['to_channel'];
			$relation = $this->toiee_pcast_relations();

			if ( isset( $relation[ $taxonomy ] ) ) {
				$post_type = $relation[ $taxonomy ];

				$ret = $this->import_series_to_pcast( $seres_id, $taxonomy, $post_type );
				if ( is_numeric( $ret ) ) {
					$url = get_term_link( $ret, $taxonomy );
					?>
					<div class="notice notice-success is-dismissible">
						<p><strong>インポート完了しました。<a href="<?php echo esc_url( $url ); ?>">(インポート先へ移動する)</a></strong></p>
					</div>
					<?php
				} else {
					?>
					<div class="notice notice-error is-dismissible">
						<p><strong>指定されたパラメータに間違いがあります</strong></p>
					</div>
					<?php
				}
			} else {
				?>
				<div class="notice notice-error is-dismissible">
					<p><strong>指定されたパラメータに間違いがあります</strong></p>
				</div>
				<?php
			}
		}

		$series_s    = get_terms( 'series' );
		$select_from = array();
		foreach ( $series_s as $series ) {
			$moving = get_term_meta( $series->term_id, 'pcast_moving', true );
			if ( '1' !== $moving ) {
				$select_from[] = array(
					'disp'  => $series->name,
					'value' => $series->term_id,
				);
			}
		}
		?>
		<p>Seriously Simple Podcast のシリーズ（Podcastチャンネル）を指定して、pcastのチャンネルにデータをインポートします。</p>
		<form method="post" action="<?php admin_url( 'options-general.php?page=toiee-podcast&tab=ssp-import' ); ?>">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="my-text-field">取り込むSeries (SSP)</label>
					</th>
					<td>
						<select name="from_channel">
							<?php foreach ( $select_from as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_attr( $option['disp'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<br>
						<span class="description">移行済みのものは表示しません</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-text-field">取り込み先</label>
					</th>
					<td>
						<select name="to_channel">
							<option value="pkt_channel">ポケてら</option>
							<option value="mdy_channel">耳デミー</option>
							<option value="scrum_channel">スクラム</option>
						</select>
						<br>
						<span class="description">移行済みのものは表示しません</span>
					</td>
				</tr>
				</tbody>
			</table>
			<?php wp_nonce_field( 'toiee_podcast' ); ?>
			<input type="hidden" name="cmd" value="ssp-import" />
			<?php submit_button( '実行' ); ?>
		</form>
		<?php
	}

	private function import_from_media() {
		if ( isset( $_POST['cmd'] ) && $_POST['cmd'] === 'media-import' ) {
			check_admin_referer( 'toiee_podcast' );

			if ( in_array( $_POST['restrict'] , array( 'restrict', 'free', 'open' ) ) ) {
				$restrict_field = $_POST['restrict'];
			} else {
				$restrict_field = 'restrict';
			}

			if ( count( $_POST['media'] ) ) {

				/* 登録先のtaxonomy, term, post_type を取得 */
				$tt        = $this->get_pcast_tax( $_POST['target_channel'] );
				$term      = get_term_by( 'id', $tt['term_id'], $tt['tax'] );
				$post_type = $tt['post_type'];

				/* 投稿データを用意し、並び替えをする */
				$pcast = array();
				foreach ( $_POST['media'] as $id ) {
					$attach = get_post( $id );
					$url    = wp_get_attachment_url( $id );
					$meta   = wp_get_attachment_metadata( $id );
					$media  = preg_match( '/^audio/', $meta['mime_type'] ) ? 'audio' : 'video';

					$pcast[ $id ] = array(
						'id'           => $id,
						'post_content' => $attach->post_content,
						'post_title'   => $attach->post_title,
						'post_status'  => 'publish',
						'mime_type'    => $meta['mime_type'],
						'restrict'     => $restrict_field,
						'enclosure'    => $url,
						'media'        => $media,
						'duration'     => $meta['length_formatted'],
						'length'       => $meta['filesize'],
					);
				}

				usort(
					$pcast,
					function ( $a, $b ) {
						return strnatcmp( $a['post_title'], $b['post_title'] );
					}
				);

				/* pcast に投稿する */
				$cnt   = 0;
				$total = count( $pcast );
				$time  = time() - 60 * ( $total + 2 );

				foreach ( $pcast as $p ) {
					$cnt++;
					$time += 60;
					$att   = array();

					$media = get_post( $id );

					/* post を投稿 */
					$args    = array(
						'ID'           => null,
						'post_content' => $p['post_content'],
						'post_name'    => $term->slug . '-' . $cnt,
						'post_title'   => $p['post_title'],
						'post_status'  => 'publish',
						'post_date'    => date( 'Y-m-d H:i:s', $time ),
						'post_type'    => $post_type,
						'tax_input'    => array( $tt['tax'] => $term->term_id ),
					);
					$post_id = wp_insert_post( $args );

					if ( $post_id ) {
						foreach ( array( 'restrict', 'enclosure', 'media', 'duration', 'length' ) as $k ) {
							update_field( $k, $p[ $k ], $post_id );
						}
					}
				}

				$edit_url = admin_url( 'edit.php?' . $tt['tax'] . '=' . $term->slug . '&post_type=' . $post_type );
			}
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php echo esc_html( $cnt ); ?>件の登録作業が完了しました。</strong> <a href="<?php echo $edit_url; ?>">結果はこちら</a></p>
			</div>
			<?php
		}

		$args     = array(
			'posts_per_page' => 50,
			'post_type'      => 'attachment',
			'post_status'    => [ 'publish', 'inherit' ],
			'orderby'        => 'modified',
		);
		$attaches = get_posts( $args );

		// 上位50個から、audio と video のみを残す
		$attaches = array_filter(
			$attaches,
			function( $v ) {
				if ( preg_match( '/^(audio)|(video).*/', $v->post_mime_type ) ) {
					return true;
				} else {
					return false;
				}
			}
		);

		$select_to = $this->get_pcast_taxes_select();

		?>
		<p>メディアライブラリの音声、ビデオを特定のPcastにインポートします。<br>
		投稿日時は、インポートした時点（今）を起点に設定されます。また、順番は「名前順」となります。<br>
		したがって、番号などを付与した名前にしておいてください。</p>

		<form method="post" action="<?php admin_url( 'options-general.php?page=toiee-podcast&tab=import-media' ); ?>">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="my-text-field">メディアの一覧（動画とオーディオのみ）</label>
					</th>
					<td>
						<select multiple name="media[]" size="15" style="width:600px;">
							<?php foreach ( $attaches as $att ) : ?>
								<option value="<?php echo esc_attr( $att->ID ); ?>"><?php echo esc_html( $att->post_title . ' (' . $att->post_mime_type . ')' ); ?></option>
							<?php endforeach; ?>
						</select>

						<br>
						<span class="description">複数を選択できます</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-text-field">インポート先</label>
					</th>
					<td>
						<select name="target_channel">
							<?php foreach ( $select_to as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_attr( $option['disp'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<br>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-text-field">閲覧制限</label>
					</th>
					<td>
						<select name="restrict">
							<option value="open">公開</option>
							<option value="free" selected="selected">会員無料</option>
							<option value="restrict">購入者限定</option>
						</select>
						<br>
						<span class="description">スクラム・インプットは「会員無料」です</span>
					</td>
				</tr>
				</tbody>
			</table>
			<?php wp_nonce_field( 'toiee_podcast' ); ?>
			<input type="hidden" name="cmd" value="media-import" />
			<?php submit_button( '実行' ); ?>
		</form>
		<?php
	}

	private function import_pktmdy() {
		if ( isset( $_POST['cmd'] ) && $_POST['cmd'] === 'pktmdy-import' ) {
			check_admin_referer( 'toiee_podcast' );

			$from_term = $this->get_pcast_tax( $_POST['from_channel'] );
			$to_term   = $this->get_pcast_tax( $_POST['to_channel'] );

			$args       = array(
				'posts_per_page' => -1,
				'post_type'      => $from_term['post_type'],
				'tax_query'      => array(
					array(
						'taxonomy' => $from_term['tax'],
						'field'    => 'id',
						'terms'    => $from_term['term_id'],
					),
				),
			);
			$from_posts = get_posts( $args );

			$cnt  = 0;
			$keys = array( 'post_content', 'post_name', 'post_title', 'post_status', 'post_author', 'post_date', 'post_date_gmt' );
			foreach ( $from_posts as $p ) {
				$cnt++;

				$args = array();
				foreach ( $keys as $key ) {
					$args[ $key ] = $p->$key;
				}
				$args['post_type'] = $to_term['post_type'];
				$args['tax_input'] = array( $to_term['tax'] => $to_term['term_id'] );

				$pid = wp_insert_post( $args );

				$fields = get_fields( $p->ID );
				foreach ( $fields as $name => $value ) {
					update_field( $name, $value, $pid );
				}
			}

			$term     = get_term_by( 'id', $to_term['term_id'], $to_term['tax'] );
			$edit_url = admin_url( 'edit.php?' . $to_term['tax'] . '=' . $term->slug . '&post_type=' . $to_term['post_type'] );
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php echo esc_html( $cnt ); ?>件の登録作業が完了しました。</strong> <a href="<?php echo $edit_url; ?>">結果はこちら</a></p>
			</div>
			<?php
		}

		$select = $this->get_pcast_taxes_select();

		$select_to   = array();
		$select_from = array();

		foreach ( $select as $s ) {
			if ( preg_match( '/^mdy|pkt/', $s['value'] ) ) {
				$select_from[] = $s;
			}
			if ( preg_match( '/^tlm/', $s['value'] ) ) {
				$select_to[] = $s;
			}
		}

		?>
		<p>ポケてら、耳デミーをtoiee教材へインポートします<br>
			インポートは「データをそのまま変更を加えず」コピーして作成します。</p>
		<form method="post" action="<?php admin_url( 'options-general.php?page=toiee-podcast&tab=import-pktmdy' ); ?>">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="my-text-field">ポケてら、耳デミー</label>
					</th>
					<td>
						<select name="from_channel">
							<?php foreach ( $select_from as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_attr( $option['disp'] ); ?></option>
							<?php endforeach; ?>
						</select>

						<br>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-text-field">インポート先</label>
					</th>
					<td>
						<select name="to_channel">
							<?php foreach ( $select_to as $option ) : ?>
								<option value="<?php echo esc_attr( $option['value'] ); ?>"><?php echo esc_attr( $option['disp'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<br>
						<span class="description">toiee教材の「インプット」「ワークショップ」「アーカイブ」のいずれかを選んでください</span>
					</td>
				</tr>
				</tbody>
			</table>
			<?php wp_nonce_field( 'toiee_podcast' ); ?>
			<input type="hidden" name="cmd" value="pktmdy-import" />
			<?php submit_button( '実行' ); ?>
		</form>
		<?php
	}

	private function update_vimeo_api() {
		/* ----------- Vimeo APIキーの保存 --------------------- */
		if ( isset( $_POST['cmd'] ) && $_POST['cmd'] === 'vimeo-api' ) {
			check_admin_referer( 'toiee_podcast' );

			$values = array();
			foreach ( array( 'cid', 'access_token', 'csr' ) as $key ) {
				$values[ $key ] = isset( $_POST['vimeo_api'][ $key ] ) ? esc_attr( $_POST['vimeo_api'][ $key ] ) : '';
			}
			update_option( 'toiee_vimeo_api_keys', $values, false );
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong>saved Vimeo API settings.</strong></p>
			</div>
			<?php
		}
		$vimeo_api = get_option( 'toiee_vimeo_api_keys', '' );
		?>
		<p>vimeoのプロジェクト、アルバムを読み込むためには、以下のAPI全てを設定してください。</p>

		<form method="post" action="<?php admin_url( 'options-general.php?page=toiee-podcast&tab=vimeo-api' ); ?>">
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="my-text-field">Client identifier</label>
					</th>
					<td>
						<input type="text" name="vimeo_api[cid]" value="<?php echo isset( $vimeo_api['cid'] ) ? $vimeo_api['cid'] : ''; ?>">
						<br>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-text-field">Access token</label>
					</th>
					<td>
						<input type="text" name="vimeo_api[access_token]" value="<?php echo isset( $vimeo_api['access_token'] ) ? $vimeo_api['access_token'] : ''; ?>">
						<br>
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="my-text-field">Client secrets</label>
					</th>
					<td>
						<input type="text" name="vimeo_api[csr]" value="<?php echo isset( $vimeo_api['csr'] ) ? $vimeo_api['csr'] : ''; ?>">
						<br>
						<span class="description"></span>
					</td>
				</tr>
				</tbody>
			</table>
			<?php wp_nonce_field( 'toiee_podcast' ); ?>
			<input type="hidden" name="cmd" value="vimeo-api" />
			<?php submit_button( '保存' ); ?>
		</form>
		<?php

	}

	private function get_vimeo_object() {
		if ( null === $this->vimeo ) {

			$api = get_option( 'toiee_vimeo_api_keys', '' );
			if ( '' === $api ) {
				$this->vimeo = false;
				return false;
			}

			foreach ( array( 'cid', 'access_token', 'csr' ) as $k ) {
				if ( ! isset( $api[ $k ] ) || '' === $api[ $k ] ) {
					$this->vimeo = false;
					return false;
				}
			}

			$lib = new \Vimeo\Vimeo( $api['cid'], $api['csr'] );
			$lib->setToken( $api['access_token'] );

			if ( null === $lib ) {
				$this->vimeo = false;
				return false;
			}

			$this->vimeo = $lib;
		}

		return $this->vimeo;
	}

	private function get_vimeo_list() {

		$lib = $this->get_vimeo_object();
		if ( false === $lib ) {
			return false;
		}

		$vimeo_list = array();
		$response   = $lib->request(
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
			update_option( 'toiee_vimeo_list', $vimeo_list, false );
		}

		return $vimeo_list;
	}

	private function get_pcast_taxes_select() {
		$tax      = $this->toiee_pcast_taxonomy();
		$relation = $this->toiee_pcast_relations();

		$select = array();
		foreach ( $tax as $tax_name ) {
			$terms = get_terms(
				$tax_name,
				array(
					'orderby'    => 'id',
					'order'      => 'DESC',
					'hide_empty' => 0,
				)
			);

			$tax_obj   = get_taxonomy( $tax_name );
			$post_type = $relation[ $tax_name ];

			foreach ( $terms as $t ) {

				if ( is_array( $post_type ) ) {
					foreach ( $post_type as $ptype ) {
						$obj                                  = get_post_type_object( $ptype );
						$select[ $t->term_id . '-' . $ptype ] = array(
							'disp'  => '【' . $tax_obj->label . '-' . $obj->label . '】' . $t->name,
							'value' => $tax_name . ',' . $t->term_id . ',' . $ptype,
						);
					}
				} else {
					$select[ $t->term_id ] = array(
						'disp'  => '【' . $tax_obj->label . '】' . $t->name,
						'value' => $tax_name . ',' . $t->term_id . ',' . $post_type,
					);
				}
			}
		}

		ksort( $select );
		$select = array_reverse( $select );

		return $select;
	}

	private function get_pcast_tax( $value ) {
		$arr = explode( ',', $value );

		return array(
			'tax'       => $arr[0],
			'term_id'   => $arr[1],
			'post_type' => $arr[2],
		);
	}

	private function import_series_to_pcast( $series_id, $taxonomy, $post_type ) {

		// series のデータを取得
		$series = get_term_by( 'id', $series_id, 'series' );

		if ( 1 == get_term_meta( $series_id, 'pcast_moving', true ) ) {
			return 'series[' . esc_html( $series_id ) . '] is moved.';
		}

		/* pcast の term を作成 */
		$ret     = wp_insert_term(
			$series->name,
			$taxonomy,
			array(
				'description' => $series->description,
				'slug'        => $series->slug,
			)
		);
		$term_id = null;
		if ( is_wp_error( $ret ) ) {
			return 'can not create pcast term by series (' . $series->name . ', ' . $series_id . ')';
		} else {
			$term_id = $ret['term_id'];
		}

		$pcast = get_term_by( 'id', $term_id, $taxonomy );

		$type   = get_option( 'ss_podcasting_consume_order_' . $series_id, '' );
		$type   = $type === '' ? 'episodic' : $type;
		$fields = array(
			'published'          => 1, // 公開が基本
			'items_notification' => get_field( 'series_material', $series ),
			'restrict'           => 1,
			'restrict_product'   => get_field( 'series_products', $series ), // array
			'language'           => 'ja',
			'copyright'          => 'toiee Lab',
			'subtitle'           => get_option( 'ss_podcasting_data_subtitle_' . $series_id, '' ),
			'author'             => 'toiee Lab',
			'owner_name'         => 'toiee Lab',
			'owner_email'        => 'desk@toiee.jp',
			'image'              => attachment_url_to_postid( get_option( 'ss_podcasting_data_image_' . $series_id, 'no-image' ) ),
			'category'           => 'Education > Educational Technology',
			'explicit'           => 0,
			'block'              => 1,
			'episode_type'       => $type,
		);

		foreach ( $fields as $selector => $value ) {
			update_field( $selector, $value, $pcast );
		}

		/* 属しているpostをインポート */
		$args     = array(
			'posts_per_page' => -1,
			'post_type'      => 'podcast',
			'tax_query'      => array(
				array(
					'taxonomy' => 'series',
					'field'    => 'id',
					'terms'    => $series_id,
				),
			),
		);
		$podcasts = get_posts( $args );

		$keys = array( 'post_content', 'post_name', 'post_title', 'post_status', 'post_author', 'post_date', 'post_date_gmt' );

		foreach ( $podcasts as $podcast ) {

			if ( get_post_meta( $podcast->ID, 'pcast_moving_to', true ) ) {
				// echo 'skip ' . $podcast->post_name . '<br>';
			} else {
				// echo $podcast->post_name . '<br>';
				$args = array();
				foreach ( $keys as $key ) {
					$args[ $key ] = $podcast->$key;
				}
				$args['post_type'] = $post_type;
				$pid               = wp_insert_post( $args );

				/* term */
				wp_set_object_terms( $pid, array( $term_id ), $taxonomy );

				/* fields */
				$fields = array();

				$fields['restrict']  = get_post_meta( $podcast->ID, 'wcr_ssp_episode_restrict', true ) === 'enable' ? 'restrict' : 'open';
				$fields['enclosure'] = get_post_meta( $podcast->ID, 'enclosure', true );
				$fields['media']     = get_post_meta( $podcast->ID, 'episode_type', true );
				$fields['duration']  = get_post_meta( $podcast->ID, 'duration', true );
				$fields['length']    = get_post_meta( $podcast->ID, 'filesize_raw', true );

				foreach ( $fields as $selector => $value ) {
					update_field( $selector, $value, $pid );
				}

				update_post_meta( $podcast->ID, 'pcast_moving_to', get_permalink( $pid ) );

				// echo 'import post ( id: ' . $podcast->ID . ' ' . $podcast->post_name . ')<br>';
			}
		}

		update_term_meta( $series_id, 'pcast_moving', 1 );
		update_term_meta( $series_id, 'pcast_moving_to', get_term_link( $pcast ) );
		update_term_meta( $series_id, 'pcast_moving_to_web', get_term_link( $pcast ) );
		update_term_meta( $series_id, 'pcast_moving_to_id', $term_id );

		return $term_id;
	}

	public function enqueue_plyer_dot_io_style_and_script() {
		wp_enqueue_style( 'plyrio', $this->plugin_url . 'assets/plyr.io/plyr.css' );
		wp_enqueue_script( 'plyrio', $this->plugin_url . 'assets/plyr.io/plyr.js', array(), '3.5.3', true );
		wp_enqueue_script( 'plyrio-enable', $this->plugin_url . 'assets/plyr-enable.js', array('plyrio'), '1.0', true );
	}
}
