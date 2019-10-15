<?php

/*
 * Post を Podcast、教材化するための仕組み
 */
class Toiee_Postcast {

	private $plugin_dir;

	public $top_categories;

	public function __construct( $file ) {
		$plugin_dir       = plugin_dir_path( $file );
		$this->plugin_dir = $plugin_dir;

		register_activation_hook( $plugin_dir, array( $this, 'activate' ) );
		register_deactivation_hook( $plugin_dir, array( $this, 'deactivate' ) );

		$this->add_acf();

		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_action( 'template_redirect', array( $this, 'postcast_template_redirect' ) );

		add_filter( 'pre_get_posts', array( $this, 'pre_get_posts_filter' ) );

		$this->top_categories = [
			'workshop-archive', /* ワークショップ・アーカイブのカテゴリスラッグを workshop-archive と想定 */
			'lft',              /* ワークショップ・レジュメのカテゴリスラッグを lft と想定 */
			'mimidemy',         /* 耳デミーのカテゴリスラッグを mimidemy と想定 */
			'kamedemy',         /* かめデミーのカテゴリスラッグを kamedemy と想定 */
			'it-support',       /* ITサポート */
			'pocketera',        /* ポケテら */
		];

		/* 教材カテゴリの場合、カテゴリとつけないようにする */
		add_filter( 'get_the_archive_title', array( $this, 'archive_title' ) );

		/* 教材カテゴリの場合、ワークショップ・アーカイブのテンプレートを読み込む　*/
		add_filter( 'template_include', array( $this, 'workshop_archive_template' ), 99 );

	}

	public function pre_get_posts_filter( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->is_main_query() && is_category( $this->top_categories ) ) {
			if ( is_category( 'it-support' ) ) {
				$query->set( 'posts_per_page', 20 );
				return;
			} else {
				$query->set( 'posts_per_page', - 1 );
				return;
			}
		}

	}

	public function archive_title( $title ) {
		if ( is_category( $this->top_categories ) ) {
			$title = single_cat_title( '', false );
		}
		return $title;
	}

	public function workshop_archive_template( $template ) {
		if ( is_category( $this->top_categories ) ) {
			if ( is_category( 'it-support' ) ) {
				$new_template = locate_template( array( 'category-it-support-archive.php' ) );
				if ( ! empty( $new_template ) ) {
					return $new_template;
				}
			}

			$new_template = locate_template( array( 'category-workshop-archive.php' ) );
			if ( ! empty( $new_template ) ) {
				return $new_template;
			}
		}

		return $template;
	}

	function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5d7884bb59ac3',
					'title'                 => 'toiee Lab 教材情報',
					'fields'                => array(
						array(
							'key'               => 'field_5d7884dc3a415',
							'label'             => '教材情報を設定する',
							'name'              => 'tlm_enable',
							'type'              => 'true_false',
							'instructions'      => 'このブログ投稿を教材型にする場合、チェックを入れてください',
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
							'ui_on_text'        => '教材にする',
							'ui_off_text'       => '通常の投稿',
						),
						array(
							'key'               => 'field_5d7885683a416',
							'label'             => '全体設定',
							'name'              => 'tlm_channel',
							'type'              => 'group',
							'instructions'      => '教材のサブタイトル、アイコンなどを設定します',
							'required'          => 0,
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_5d7884dc3a415',
										'operator' => '==',
										'value'    => '1',
									),
								),
							),
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'layout'            => 'block',
							'sub_fields'        => array(
								array(
									'key'               => 'field_5d7885b43a417',
									'label'             => 'complete',
									'name'              => 'complete',
									'type'              => 'true_false',
									'instructions'      => '教材追加の完成、未完成を設定します',
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
									'ui_on_text'        => '完成',
									'ui_off_text'       => '継続追加中',
								),
								array(
									'key'               => 'field_5d7887b43a41d',
									'label'             => 'サブタイトル',
									'name'              => 'subtitle',
									'type'              => 'text',
									'instructions'      => '',
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
									'key'               => 'field_5d78888f3a421',
									'label'             => 'アートワーク',
									'name'              => 'artwork',
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
									'preview_size'      => 'medium',
									'library'           => 'all',
									'min_width'         => '',
									'min_height'        => '',
									'min_size'          => '',
									'max_width'         => '',
									'max_height'        => '',
									'max_size'          => '',
									'mime_types'        => 'png,jpeg,jpg',
								),
								array(
									'key'               => 'field_5d7886153a418',
									'label'             => 'アイテム共通テキスト',
									'name'              => 'item_notice',
									'type'              => 'wysiwyg',
									'instructions'      => 'Podcastのアイテム全てに表示します',
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
									'key'               => 'field_5d7886683a419',
									'label'             => '閲覧制限',
									'name'              => 'restrict',
									'type'              => 'true_false',
									'instructions'      => '商品などで閲覧制限するかを設定します',
									'required'          => 0,
									'conditional_logic' => 0,
									'wrapper'           => array(
										'width' => '',
										'class' => '',
										'id'    => '',
									),
									'message'           => '',
									'default_value'     => 1,
									'ui'                => 0,
									'ui_on_text'        => '',
									'ui_off_text'       => '',
								),
								array(
									'key'               => 'field_5d788c901d62f',
									'label'             => 'オファー商品',
									'name'              => 'offer_product',
									'type'              => 'post_object',
									'instructions'      => '購入を案内する商品を選択します（一つだけ）',
									'required'          => 1,
									'conditional_logic' => 0,
									'wrapper'           => array(
										'width' => '',
										'class' => '',
										'id'    => '',
									),
									'post_type'         => array(
										0 => 'product',
									),
									'taxonomy'          => '',
									'allow_null'        => 0,
									'multiple'          => 0,
									'return_format'     => 'id',
									'ui'                => 1,
								),
								array(
									'key'               => 'field_5d7886a73a41a',
									'label'             => 'アクセス許可商品',
									'name'              => 'restrict_product',
									'type'              => 'post_object',
									'instructions'      => 'アクセスを許可するまとめ商品、WCプロダクトを指定します',
									'required'          => 0,
									'conditional_logic' => 0,
									'wrapper'           => array(
										'width' => '',
										'class' => '',
										'id'    => '',
									),
									'post_type'         => array(
										0 => 'product',
										1 => 'wcrestrict',
										2 => 'shop_subscription',
										3 => 'product_variation',
									),
									'taxonomy'          => '',
									'allow_null'        => 0,
									'multiple'          => 1,
									'return_format'     => 'id',
									'ui'                => 1,
								),
								array(
									'key'               => 'field_5d7887693a41b',
									'label'             => '言語',
									'name'              => 'lang',
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
									'key'               => 'field_5d7887903a41c',
									'label'             => 'コピーライト',
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
									'placeholder'       => 'toiee Lab',
									'prepend'           => '',
									'append'            => '',
									'maxlength'         => '',
								),
								array(
									'key'               => 'field_5d7887d93a41e',
									'label'             => '作者',
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
									'placeholder'       => 'toiee Lab',
									'prepend'           => '',
									'append'            => '',
									'maxlength'         => '',
								),
								array(
									'key'               => 'field_5d7887f73a41f',
									'label'             => 'オーナー',
									'name'              => 'owner_name',
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
									'placeholder'       => 'toiee Lab',
									'prepend'           => '',
									'append'            => '',
									'maxlength'         => '',
								),
								array(
									'key'               => 'field_5d7888113a420',
									'label'             => 'オーナーアドレス',
									'name'              => 'owner_email',
									'type'              => 'text',
									'instructions'      => '',
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
									'key'               => 'field_5d7888e03a422',
									'label'             => 'カテゴリ',
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
										'Arts'             => 'Arts',
										'Arts > Design'    => 'Arts > Design',
										'Arts > Fashion & Beauty' => 'Arts > Fashion & Beauty',
										'Arts > Food'      => 'Arts > Food',
										'Arts > Literature' => 'Arts > Literature',
										'Arts > Performing Arts' => 'Arts > Performing Arts',
										'Arts > Visual Arts' => 'Arts > Visual Arts',
										'Business'         => 'Business',
										'Business > Business News' => 'Business > Business News',
										'Business > Careers' => 'Business > Careers',
										'Business > Investing' => 'Business > Investing',
										'Business > Management & Marketing' => 'Business > Management & Marketing',
										'Business > Shopping' => 'Business > Shopping',
										'Comedy'           => 'Comedy',
										'Education'        => 'Education',
										'Education > Educational Technology' => 'Education > Educational Technology',
										'Education > Higher Education' => 'Education > Higher Education',
										'Education > K-12' => 'Education > K-12',
										'Education > Language Courses' => 'Education > Language Courses',
										'Education > Training' => 'Education > Training',
										'Games & Hobbies'  => 'Games & Hobbies',
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
										'Health'           => 'Health',
										'Health > Alternative Health' => 'Health > Alternative Health',
										'Health > Fitness & Nutrition' => 'Health > Fitness & Nutrition',
										'Health > Self-Help' => 'Health > Self-Help',
										'Health > Sexuality' => 'Health > Sexuality',
										'Health > Kids & Family' => 'Health > Kids & Family',
										'Music'            => 'Music',
										'News & Politics'  => 'News & Politics',
										'News & Politics > Religion & Spirituality' => 'News & Politics > Religion & Spirituality',
										'News & Politics > Religion & Spirituality > Buddhism' => 'News & Politics > Religion & Spirituality > Buddhism',
										'News & Politics > Religion & Spirituality > Christianity' => 'News & Politics > Religion & Spirituality > Christianity',
										'News & Politics > Religion & Spirituality > Hinduism' => 'News & Politics > Religion & Spirituality > Hinduism',
										'News & Politics > Religion & Spirituality > Islam' => 'News & Politics > Religion & Spirituality > Islam',
										'News & Politics > Religion & Spirituality > Judaism' => 'News & Politics > Religion & Spirituality > Judaism',
										'News & Politics > Religion & Spirituality > Other' => 'News & Politics > Religion & Spirituality > Other',
										'News & Politics > Religion & Spirituality > Spirituality' => 'News & Politics > Religion & Spirituality > Spirituality',
										'Science & Medicine' => 'Science & Medicine',
										'Science & Medicine > Medicine' => 'Science & Medicine > Medicine',
										'Science & Medicine > Natural Sciences' => 'Science & Medicine > Natural Sciences',
										'Science & Medicine > Social Sciences' => 'Science & Medicine > Social Sciences',
										'Society & Culture' => 'Society & Culture',
										'Society & Culture > History' => 'Society & Culture > History',
										'Society & Culture > Personal Journals' => 'Society & Culture > Personal Journals',
										'Society & Culture > Philosophy' => 'Society & Culture > Philosophy',
										'Society & Culture > Places & Travel' => 'Society & Culture > Places & Travel',
										'Sports & Recreation' => 'Sports & Recreation',
										'Sports & Recreation > Amateur' => 'Sports & Recreation > Amateur',
										'Sports & Recreation > College & High School' => 'Sports & Recreation > College & High School',
										'Sports & Recreation > Outdoor' => 'Sports & Recreation > Outdoor',
										'Sports & Recreation > Professional' => 'Sports & Recreation > Professional',
										'Sports & Recreation > TV & Film' => 'Sports & Recreation > TV & Film',
										'Technology'       => 'Technology',
										'Technology > Gadgets' => 'Technology > Gadgets',
										'Technology > Podcasting' => 'Technology > Podcasting',
										'Technology > Software How-To' => 'Technology > Software How-To',
										'Technology > Tech News' => 'Technology > Tech News',
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
									'key'               => 'field_5d7889723a423',
									'label'             => '閲覧注意',
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
									'ui'                => 0,
									'ui_on_text'        => '',
									'ui_off_text'       => '',
								),
								array(
									'key'               => 'field_5d7889983a424',
									'label'             => 'iTunes非表示',
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
									'ui'                => 0,
									'ui_on_text'        => '',
									'ui_off_text'       => '',
								),
								array(
									'key'               => 'field_5d7889ca3a425',
									'label'             => 'エピソードタイプ',
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
									'ui'                => 0,
									'return_format'     => 'value',
									'ajax'              => 0,
									'placeholder'       => '',
								),
								array(
									'key'               => 'field_5d788a9f1d626',
									'label'             => 'オーディオブック',
									'name'              => 'audiobook',
									'type'              => 'file',
									'instructions'      => '',
									'required'          => 0,
									'conditional_logic' => 0,
									'wrapper'           => array(
										'width' => '',
										'class' => '',
										'id'    => '',
									),
									'return_format'     => 'url',
									'library'           => 'all',
									'min_size'          => '',
									'max_size'          => '',
									'mime_types'        => 'm4a,m4b,mp3',
								),
							),
						),
						array(
							'key'               => 'field_5d788acc1d627',
							'label'             => 'アイテム',
							'name'              => 'tlm_items',
							'type'              => 'repeater',
							'instructions'      => 'ビデオや、オーディオを設定する場所です',
							'required'          => 0,
							'conditional_logic' => array(
								array(
									array(
										'field'    => 'field_5d7884dc3a415',
										'operator' => '==',
										'value'    => '1',
									),
								),
							),
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'collapsed'         => 'field_5d788e334aa43',
							'min'               => 0,
							'max'               => 0,
							'layout'            => 'row',
							'button_label'      => 'ビデオ(オーディオ)を追加',
							'sub_fields'        => array(
								array(
									'key'               => 'field_5d788e334aa43',
									'label'             => 'タイトル',
									'name'              => 'title',
									'type'              => 'text',
									'instructions'      => '',
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
									'key'               => 'field_5d788b821d629',
									'label'             => 'サブタイトル',
									'name'              => 'subtitle',
									'type'              => 'text',
									'instructions'      => '',
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
									'key'               => 'field_5d788b321d628',
									'label'             => '閲覧制限する',
									'name'              => 'restrict',
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
										'restrict' => '購読者会員のみ',
										'free'     => '無料登録会員',
										'open'     => '公開',
									),
									'default_value'     => array(
										0 => 'restrict',
									),
									'allow_null'        => 0,
									'multiple'          => 0,
									'ui'                => 0,
									'return_format'     => 'value',
									'ajax'              => 0,
									'placeholder'       => '',
								),
								array(
									'key'               => 'field_5d788b9e1d62a',
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
									'placeholder'       => '',
								),
								array(
									'key'               => 'field_5d788bc31d62b',
									'label'             => 'メディアの種類',
									'name'              => 'media',
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
									'key'               => 'field_5d788c371d62c',
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
									'key'               => 'field_5d788c5e1d62d',
									'label'             => 'サイズ',
									'name'              => 'length',
									'type'              => 'number',
									'instructions'      => 'バイトで指定します',
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
									'key'               => 'field_5d788c901d62e',
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
									'ui'                => 1,
									'ui_on_text'        => '',
									'ui_off_text'       => '',
								),
								array(
									'key'               => 'field_5d788d0e1d630',
									'label'             => '受講資料',
									'name'              => 'note',
									'type'              => 'wysiwyg',
									'instructions'      => 'ワークショップの資料や、追加説明、リンク',
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
							),
						),
					),
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'post',
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
					'key'                   => 'group_5d8465ecbba39',
					'title'                 => 'カテゴリーヘッダー設定',
					'fields'                => array(
						array(
							'key'               => 'field_5d8467ea7bc18',
							'label'             => '背景画像',
							'name'              => 'bg_image',
							'type'              => 'image',
							'instructions'      => 'バックグランド画像を指定できます',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'return_format'     => 'url',
							'preview_size'      => 'medium',
							'library'           => 'all',
							'min_width'         => 1000,
							'min_height'        => '',
							'min_size'          => '',
							'max_width'         => '',
							'max_height'        => '',
							'max_size'          => '',
							'mime_types'        => 'jpeg,jpg,png,gif',
						),
						array(
							'key'               => 'field_5d8468397bc19',
							'label'             => '文字色',
							'name'              => 'font_color',
							'type'              => 'color_picker',
							'instructions'      => 'タイトルの文字色',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '#ffffff',
						),
						array(
							'key'               => 'field_5d846b61ef897',
							'label'             => 'サブタイトル',
							'name'              => 'cat_subtitle',
							'type'              => 'text',
							'instructions'      => 'ヘッダー文字の直後に入る「サブタイトル」です',
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
					),
					'location'              => array(
						array(
							array(
								'param'    => 'taxonomy',
								'operator' => '==',
								'value'    => 'category',
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

	public function add_endpoint() {
		add_rewrite_endpoint( 'postcast', EP_PERMALINK );
	}

	public function postcast_template_redirect() {
		global $wp_query;

		if ( ! isset( $wp_query->query_vars['postcast'] ) || ! is_singular() ) {
			return;
		}

		$default_template   = $this->plugin_dir . 'templates/feed-postcast.php';
		$user_template_file = apply_filters( 'toiee_postcast_feed_template', $default_template );

		include $user_template_file;
		exit;
	}

	public function activate() {
		add_rewrite_endpoint( 'postcast', EP_PERMALINK );
	}

	public function deactivate() {
	}

}
