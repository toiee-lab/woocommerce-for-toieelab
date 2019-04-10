<?php

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
	 * 閲覧制限時のオーディオファイルを指定
	 *
	 * @var string $dummy_audio
	 */
	private $dummy_audio;
	/**
	 * 閲覧制限時のビデオを指定
	 *
	 * @var string $dummy_video
	 */
	private $dummy_video;

	/**
	 * Toiee_Pcast constructor.
	 *
	 * @param string $file プラグインのルートファイルのパス
	 */
	public function __construct() {

		$this->file            = $file;
		$this->feed_slug       = 'pcast';
		$this->plugin_dir_path = plugin_dir_path( $file );
		$this->dummy_audio     = $this->plugin_dir_path . 'images/not-available.m4a';

		$this->add_acf();

		add_action( 'init', array( $this, 'add_feed' ), 1 );
		add_filter( 'query_vars', array( $this, 'custom_query_vars_filter' ) );

		register_activation_hook( WOOCOMMERCE_FOR_TOIEELAB_PLUGIN_DIR, array( $this, 'activate' ) );
		register_deactivation_hook( WOOCOMMERCE_FOR_TOIEELAB_PLUGIN_DIR, array( $this, 'deactivate' ) );

	}

	public function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

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
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'mmdmy_episode',
							),
						),
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'pkt_episode',
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
							'instructions'      => 'itunes:type を指定します。通常は、 serial	を指定します。episodic',
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
					'location'              => array(
						array(
							array(
								'param'    => 'taxonomy',
								'operator' => '==',
								'value'    => 'mmdmy',
							),
						),
						array(
							array(
								'param'    => 'taxonomy',
								'operator' => '==',
								'value'    => 'pkt_channel',
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
}
