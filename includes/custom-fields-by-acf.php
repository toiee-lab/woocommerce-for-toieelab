<?php
/* This feilds made by ACF	
 *
 */
if( function_exists('acf_add_local_field_group') ):


// wcrestrict post type に付属するもの
acf_add_local_field_group(array(
	'key' => 'group_5be17d7a9a9d7',
	'title' => '商品登録',
	'fields' => array(
		array(
			'key' => 'field_5be17da0c750c',
			'label' => '商品、購読、会員',
			'name' => 'wcr_product_ids',
			'type' => 'post_object',
			'instructions' => 'ここで登録した商品、購読、会員をまとめて「閲覧許可」を与えることができます',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'post_type' => array(
				0 => 'product',
				1 => 'product_variation',
				2 => 'wc_membership_plan',
			),
			'taxonomy' => '',
			'allow_null' => 1,
			'multiple' => 1,
			'return_format' => 'id',
			'ui' => 1,
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'wcrestrict',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'acf_after_title',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => 1,
	'description' => 'WC Restrict に商品登録するためのもの',
));

endif;


if( function_exists('acf_add_local_field_group') ):

// WooCommerceの商品画面に貼り付けるもの
acf_add_local_field_group(array(
	'key' => 'group_5be20140832f0',
	'title' => 'マイライブラリ設定',
	'fields' => array(
		array(
			'key' => 'field_5be201568379a',
			'label' => 'コンテンツURL',
			'name' => 'wcmylib_url',
			'type' => 'url',
			'instructions' => 'マイライブラリに表示する場合は、指定してください。商品ページにも表示（購入している場合）します。',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => 'https://',
		),
		array(
			'key' => 'field_5be202018379b',
			'label' => 'アクセス可能商品',
			'name' => 'wcmylib_products',
			'type' => 'post_object',
			'instructions' => '以下の商品を持つユーザーを購入済みとして扱い、ライブラリURLを表示します。',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'post_type' => array(
				0 => 'wcrestrict',
				1 => 'product',
				2 => 'product_variation',
				3 => 'wc_membership_plan',
			),
			'taxonomy' => '',
			'allow_null' => 1,
			'multiple' => 1,
			'return_format' => 'id',
			'ui' => 1,
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'product',
			),
		),
	),
	'menu_order' => 10,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => 1,
	'description' => 'マイライブラリ設定を行います',
));

endif;



if( function_exists('acf_add_local_field_group') ):

//シリーズに追加するもの
acf_add_local_field_group(array(
	'key' => 'group_5be205bca4c98',
	'title' => 'シリーズ詳細設定',
	'fields' => array(
		array(
			'key' => 'field_5be2400e03764',
			'label' => '共通テキスト',
			'name' => 'series_material',
			'type' => 'wysiwyg',
			'instructions' => '全てのエピソードの末尾に追加される共通パーツです。ここに講座資料などを貼り付けておくと便利です',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5be206fffdd89',
			'label' => '閲覧制限',
			'name' => 'series_limit',
			'type' => 'true_false',
			'instructions' => '閲覧制限をする、しない',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'message' => '',
			'default_value' => 0,
			'ui' => 0,
			'ui_on_text' => '',
			'ui_off_text' => '',
		),
		array(
			'key' => 'field_5be206053a550',
			'label' => 'アクセス許可商品',
			'name' => 'series_products',
			'type' => 'post_object',
			'instructions' => 'ここで指定した商品(購読、会員)や、商品まとめ(WC Restrict)を購入しているユーザーに閲覧許可します',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'post_type' => array(
				0 => 'wcrestrict',
				1 => 'product',
				2 => 'product_variation',
				3 => 'wc_membership_plan',
			),
			'taxonomy' => '',
			'allow_null' => 1,
			'multiple' => 1,
			'return_format' => 'id',
			'ui' => 1,
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'taxonomy',
				'operator' => '==',
				'value' => 'series',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => 1,
	'description' => 'Seriously Simple Podcast のシリーズの閲覧許可設定、資料集設定',
));

endif;


// カスタムタブ用

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_5be6a5cdbc2c3',
	'title' => 'カスタムタブ',
	'fields' => array(
		array(
			'key' => 'field_5be6a70e986d3',
			'label' => 'タブ名',
			'name' => 'ctab-label',
			'type' => 'text',
			'instructions' => 'タブの表示テキストを入力してください',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '使い方',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5be6a5d9e2b49',
			'label' => 'カテゴリ',
			'name' => 'ctab-category',
			'type' => 'taxonomy',
			'instructions' => 'どの商品のカテゴリに、このタブを表示するかを選びます',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'taxonomy' => 'product_cat',
			'field_type' => 'multi_select',
			'allow_null' => 1,
			'add_term' => 0,
			'save_terms' => 0,
			'load_terms' => 0,
			'return_format' => 'id',
			'multiple' => 0,
		),
		array(
			'key' => 'field_5be6a66be2b4a',
			'label' => 'すべてに表示',
			'name' => 'ctab-category-all',
			'type' => 'true_false',
			'instructions' => 'チェックを入れると「すべての商品」に表示されます',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'message' => '',
			'default_value' => 0,
			'ui' => 0,
			'ui_on_text' => '',
			'ui_off_text' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'wcr-customtab',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => 1,
	'description' => '',
));

endif;

//! イベント管理システム

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_5bed095de7705',
	'title' => 'toiee イベント情報',
	'fields' => array(
		array(
			'key' => 'field_5bed0c04a9bd7',
			'label' => '簡単な説明',
			'name' => 'ts_event_description',
			'type' => 'text',
			'instructions' => '内容、ターゲット、期待できる結果を端的に書いてください',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => 'WordPress初心者向けに、中身や仕組みを深く探求します',
			'prepend' => '',
			'append' => '',
			'maxlength' => 256,
		),
		array(
			'key' => 'field_5bed0973971ec',
			'label' => '日程',
			'name' => 'ts_event_date',
			'type' => 'date_time_picker',
			'instructions' => '開始日を入力してください',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'display_format' => 'Y/m/d',
			'return_format' => 'Y/m/d',
			'first_day' => 1,
		),
		array(
			'key' => 'field_5bed0a05971ed',
			'label' => '日程追加',
			'name' => 'ts_event_date_add',
			'type' => 'text',
			'instructions' => '15:00 - 18:00 や、3日間集中ですなど追加情報を入力してください',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '18:00 - 21:00',
			'prepend' => '',
			'append' => '',
			'maxlength' => 32,
		),
		array(
			'key' => 'field_5bed0b3b971ef',
			'label' => 'イベントページ',
			'name' => 'ts_event_url',
			'type' => 'url',
			'instructions' => 'イベントページのURLを入れてください(Peatixなど)',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => 'https://',
		),
		array(
			'key' => 'field_5bed1007bd387',
			'label' => '開催場所',
			'name' => 'ts_event_location',
			'type' => 'select',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				'オンライン' => 'オンライン',
				'北海道' => '北海道',
				'青森県' => '青森県',
				'岩手県' => '岩手県',
				'宮城県' => '宮城県',
				'秋田県' => '秋田県',
				'山形県' => '山形県',
				'福島県' => '福島県',
				'茨城県' => '茨城県',
				'栃木県' => '栃木県',
				'群馬県' => '群馬県',
				'埼玉県' => '埼玉県',
				'千葉県' => '千葉県',
				'東京都' => '東京都',
				'神奈川県' => '神奈川県',
				'新潟県' => '新潟県',
				'富山県' => '富山県',
				'石川県' => '石川県',
				'福井県' => '福井県',
				'山梨県' => '山梨県',
				'長野県' => '長野県',
				'岐阜県' => '岐阜県',
				'静岡県' => '静岡県',
				'愛知県' => '愛知県',
				'三重県' => '三重県',
				'滋賀県' => '滋賀県',
				'京都府' => '京都府',
				'大阪府' => '大阪府',
				'神戸' => '神戸',
				'兵庫県' => '兵庫県',
				'奈良県' => '奈良県',
				'和歌山県' => '和歌山県',
				'鳥取県' => '鳥取県',
				'島根県' => '島根県',
				'岡山県' => '岡山県',
				'広島県' => '広島県',
				'山口県' => '山口県',
				'徳島県' => '徳島県',
				'香川県' => '香川県',
				'愛媛県' => '愛媛県',
				'高知県' => '高知県',
				'福岡県' => '福岡県',
				'佐賀県' => '佐賀県',
				'長崎県' => '長崎県',
				'熊本県' => '熊本県',
				'大分県' => '大分県',
				'宮崎県' => '宮崎県',
				'鹿児島県' => '鹿児島県',
				'沖縄県' => '沖縄県',
			),
			'default_value' => array(
			),
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 1,
			'ajax' => 0,
			'return_format' => 'value',
			'placeholder' => '',
		),
		array(
			'key' => 'field_5bed0acd971ee',
			'label' => 'タグ',
			'name' => 'ts_event_tag',
			'type' => 'taxonomy',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'taxonomy' => 'post_tag',
			'field_type' => 'checkbox',
			'add_term' => 1,
			'save_terms' => 1,
			'load_terms' => 1,
			'return_format' => 'id',
			'multiple' => 0,
			'allow_null' => 0,
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'toiee-event',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => 1,
	'description' => '',
));

endif;