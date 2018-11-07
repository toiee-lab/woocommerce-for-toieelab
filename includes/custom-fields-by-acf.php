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