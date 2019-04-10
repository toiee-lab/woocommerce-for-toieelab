<?php

/**
 * 関連ナレッジ機能を提供する
 */
class Toiee_Tkb_Post {

	/**
	 * コンストラクタ
	 *
	 * Toiee_Tkb_Post constructor.
	 */
	public function __construct( $file ) {

		add_action( 'init', array( $this, 'cptui_register_my_cpts_post' ) );
		$this->add_acf();

		$plugin_dir = plugin_dir_path( $file );

		register_activation_hook( $plugin_dir, array( $this, 'activate' ) );
		register_deactivation_hook( $plugin_dir, array( $this, 'deactivate' ) );
	}

	/**
	 * カスタム投稿タイプの登録
	 */
	public function cptui_register_my_cpts_post() {
		/**
		 * Post Type: 関連ナレッジ.
		 */

		$labels = array(
			'name'          => __( '関連ナレッジ', 'kanso general child' ),
			'singular_name' => __( '関連ナレッジ', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( '関連ナレッジ', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'ポケてらの User Contributed Note を表します',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => false,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'toiee_knowledge',
				'with_front' => true,
			),
			'query_var'             => true,
			'menu_icon'             => 'dashicons-book-alt',
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'post_tag' ),
		);

		register_post_type( 'toiee_knowledge', $args );
	}

	/**
	 * カスタムフィールドの追加
	 */
	protected function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5ca16d14dfca1',
					'title'                 => '関連ナレッジ',
					'fields'                => array(
						array(
							'key'               => 'field_5ca16d14eaf43',
							'label'             => 'いいね',
							'name'              => 'like',
							'type'              => 'number',
							'instructions'      => 'いいねの数',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => 0,
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'min'               => '',
							'max'               => '',
							'step'              => '',
						),
						array(
							'key'               => 'field_5ca30ba37e542',
							'label'             => 'ポケてら',
							'name'              => 'pocketera',
							'type'              => 'taxonomy',
							'instructions'      => '関連するポケてらチャンネルを選んでください',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'pkt_channel',
							'field_type'        => 'multi_select',
							'allow_null'        => 0,
							'add_term'          => 0,
							'save_terms'        => 0,
							'load_terms'        => 0,
							'return_format'     => 'id',
							'multiple'          => 0,
						),
						array(
							'key'               => 'field_5cabd03fe65c9',
							'label'             => '耳デミー',
							'name'              => 'mimidemy',
							'type'              => 'taxonomy',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'mmdmy',
							'field_type'        => 'multi_select',
							'allow_null'        => 0,
							'add_term'          => 1,
							'save_terms'        => 0,
							'load_terms'        => 0,
							'return_format'     => 'id',
							'multiple'          => 0,
						),
					),
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'toiee_knowledge',
							),
						),
					),
					'menu_order'            => 0,
					'position'              => 'side',
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

	public function activate() {
		$this->cptui_register_my_cpts_post();

		flush_rewrite_rules( true );
	}

	public function deactivate() {
		flush_rewrite_rules();
	}
}
