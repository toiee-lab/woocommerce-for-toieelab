<?php

/**
 * Scrum 機能を提供する
 */
class Toiee_Scrum_Post
{
	public $tabs;
	
	function __construct()
	{
		//scrum_post custom post type
		add_action( 'init', array( $this, 'cptui_register_my_cpts_scrum_post' ) );

		// scrum taxonomy
		add_action( 'init', array( $this,'cptui_register_my_taxes_scrum' ) );

		// acf
		$this->add_acf();
	}

	function cptui_register_my_cpts_scrum_post() {

		/**
		 * Post Type: スクラム投稿.
		 */

		$labels = array(
			"name" => __( "スクラム投稿", "kanso general child" ),
			"singular_name" => __( "スクラム投稿", "kanso general child" ),
		);

		$args = array(
			"label" => __( "スクラム投稿", "kanso general child" ),
			"labels" => $labels,
			"description" => "スクラムを実施する際に投稿するデータ",
			"public" => true,
			"publicly_queryable" => true,
			"show_ui" => true,
			"delete_with_user" => false,
			"show_in_rest" => true,
			"rest_base" => "",
			"rest_controller_class" => "WP_REST_Posts_Controller",
			"has_archive" => true,
			"show_in_menu" => true,
			"show_in_nav_menus" => true,
			"exclude_from_search" => false,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			"rewrite" => array( "slug" => "scrum_post", "with_front" => true ),
			"query_var" => true,
			"menu_icon" => "dashicons-groups",
			"supports" => array( "title", "editor", "thumbnail" ),
			"taxonomies" => array( "scrum" ),
		);

		register_post_type( "scrum_post", $args );
	}

	function cptui_register_my_taxes_scrum() {

		/**
		 * Taxonomy: スクラム.
		 */

		$labels = array(
			"name" => __( "スクラム", "kanso general child" ),
			"singular_name" => __( "スクラム", "kanso general child" ),
		);

		$args = array(
			"label" => __( "スクラム", "kanso general child" ),
			"labels" => $labels,
			"public" => true,
			"publicly_queryable" => true,
			"hierarchical" => true,
			"show_ui" => true,
			"show_in_menu" => true,
			"show_in_nav_menus" => true,
			"query_var" => true,
			"rewrite" => array( 'slug' => 'scrum', 'with_front' => true, ),
			"show_admin_column" => true,
			"show_in_rest" => true,
			"rest_base" => "scrum",
			"rest_controller_class" => "WP_REST_Terms_Controller",
			"show_in_quick_edit" => false,
		);
		register_taxonomy( "scrum", array( "scrum_post" ), $args );
	}

	function add_acf(){
		if( function_exists('acf_add_local_field_group') ):

			acf_add_local_field_group(array(
				'key' => 'group_5c6f815000dc3',
				'title' => 'スクラム',
				'fields' => array(
					array(
						'key' => 'field_5c6f828407feb',
						'label' => 'ヘッダー背景',
						'name' => 'scrum_headerbg',
						'type' => 'image',
						'instructions' => 'スクラムのページのヘッダー画像です',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'return_format' => 'array',
						'preview_size' => 'medium',
						'library' => 'all',
						'min_width' => '',
						'min_height' => '',
						'min_size' => '',
						'max_width' => '',
						'max_height' => '',
						'max_size' => '',
						'mime_types' => '',
					),
					array(
						'key' => 'field_5c764b5d6171c',
						'label' => 'タイトル色',
						'name' => 'title_color',
						'type' => 'select',
						'instructions' => 'タイトル、サブタイトルの色',
						'required' => 1,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'choices' => array(
							'uk-light' => '白',
							'uk-dark' => '黒',
						),
						'default_value' => array(
							0 => 'uk-dark',
						),
						'allow_null' => 0,
						'multiple' => 0,
						'ui' => 0,
						'return_format' => 'value',
						'ajax' => 0,
						'placeholder' => '',
					),
					array(
						'key' => 'field_5c6f824607fea',
						'label' => 'サブタイトル',
						'name' => 'scrum_subtitle',
						'type' => 'text',
						'instructions' => 'スクラムのトップページに表示されるサブタイトルです',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '目標、ミッションを短く',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
					),
					array(
						'key' => 'field_5c761f223d1da',
						'label' => 'ファーストタグ',
						'name' => 'first_tag_type',
						'type' => 'select',
						'instructions' => 'ページを開いたときに、アクティブにするタグを選んでください',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'choices' => array(
							'getting_start' => 'はじめての方へ',
							'updates' => '更新情報一覧',
							'materials' => '教材一覧',
						),
						'default_value' => array(
							0 => 'getting_start',
						),
						'allow_null' => 0,
						'multiple' => 0,
						'ui' => 0,
						'return_format' => 'value',
						'ajax' => 0,
						'placeholder' => '',
					),
					array(
						'key' => 'field_5c761d7e3d1d6',
						'label' => '初めての方へタブ',
						'name' => 'getting-start-body',
						'type' => 'wysiwyg',
						'instructions' => '初めての方へタブに表示する内容を記載します。',
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
						'key' => 'field_5c761dd43d1d7',
						'label' => '更新情報一覧タブ',
						'name' => 'updates_body',
						'type' => 'wysiwyg',
						'instructions' => '更新情報一覧の上部にメモを挿入することができます。',
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
						'key' => 'field_5c761e1e3d1d8',
						'label' => '更新情報(お知らせPodcast)',
						'name' => 'updates_news_podcast',
						'type' => 'taxonomy',
						'instructions' => '更新情報一覧に表示する「お知らせ用」のPodcast（シリーズ）のIDを入力してください。',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'taxonomy' => 'series',
						'field_type' => 'select',
						'allow_null' => 1,
						'add_term' => 0,
						'save_terms' => 0,
						'load_terms' => 0,
						'return_format' => 'id',
						'multiple' => 0,
					),
					array(
						'key' => 'field_5c761ec13d1d9',
						'label' => '更新情報(アーカイブPodcast)',
						'name' => 'updates_archive_podcast',
						'type' => 'taxonomy',
						'instructions' => '更新情報一覧に表示する「お知らせ用」のPodcast（シリーズ）のIDを入力してください。',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'taxonomy' => 'series',
						'field_type' => 'select',
						'allow_null' => 1,
						'add_term' => 0,
						'save_terms' => 0,
						'load_terms' => 0,
						'return_format' => 'id',
						'multiple' => 0,
					),
					array(
						'key' => 'field_5c761fb13d1db',
						'label' => '教材一覧タブ',
						'name' => 'materials-body',
						'type' => 'wysiwyg',
						'instructions' => '教材一覧の上部にメモを挿入することができます。',
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
						'key' => 'field_5c76205b98d78',
						'label' => '教材一覧（特集）',
						'name' => 'materials_featured',
						'type' => 'taxonomy',
						'instructions' => '特集（トップに固定）する Podcast の教材を選んでください。',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'taxonomy' => 'series',
						'field_type' => 'checkbox',
						'add_term' => 0,
						'save_terms' => 0,
						'load_terms' => 0,
						'return_format' => 'id',
						'multiple' => 0,
						'allow_null' => 0,
					),
					array(
						'key' => 'field_5c7620a498d79',
						'label' => '教材一覧（耳デミー）',
						'name' => 'materials_mimidemy',
						'type' => 'taxonomy',
						'instructions' => '特集（トップに固定）する Podcast の教材を選んでください。',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'taxonomy' => 'series',
						'field_type' => 'checkbox',
						'add_term' => 0,
						'save_terms' => 0,
						'load_terms' => 0,
						'return_format' => 'id',
						'multiple' => 0,
						'allow_null' => 0,
					),
					array(
						'key' => 'field_5c7620b898d7a',
						'label' => '教材一覧（ポケてら）',
						'name' => 'materials_pocketera',
						'type' => 'taxonomy',
						'instructions' => '特集（トップに固定）する Podcast の教材を選んでください。',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'taxonomy' => 'series',
						'field_type' => 'checkbox',
						'add_term' => 0,
						'save_terms' => 0,
						'load_terms' => 0,
						'return_format' => 'id',
						'multiple' => 0,
						'allow_null' => 0,
					),
					array(
						'key' => 'field_5c772f4ed8f07',
						'label' => '管理者タブ',
						'name' => 'admin-body',
						'type' => 'wysiwyg',
						'instructions' => 'スクラムマスターにしか見えないタブです。',
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
				),
				'location' => array(
					array(
						array(
							'param' => 'taxonomy',
							'operator' => '==',
							'value' => 'scrum',
						),
					),
				),
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
}