<?php

/**
 * Kamedemy のためのもの
 */
class Toiee_Kdy_Post {

	private $plugin_dir;
	/**
	 * コンストラクタ
	 */
	public function __construct( $file ) {

		add_action( 'init', array( $this, 'cptui_register_my_cpts_post' ) );
		add_action( 'init', array( $this, 'cptui_register_my_taxes' ) );
		$this->add_acf();
		add_action( 'pre_get_posts', array( $this, 'pre_get_post' ), 1 );

		$plugin_dir       = plugin_dir_path( $file );
		$this->plugin_dir = $plugin_dir;

		register_activation_hook( $plugin_dir, array( $this, 'activate' ) );
		register_deactivation_hook( $plugin_dir, array( $this, 'deactivate' ) );
	}

	/**
	 * 表示の順序を変更する
	 *
	 * @param $query
	 * @return null
	 */
	public function pre_get_post( $query ) {

		if ( is_admin() || ! $query->is_main_query() ) {
			return null;
		}

		if ( is_tax( 'kdy' ) ) {
			$term = $query->queried_object;
			if ( null === $term ) {
				return null;
			}

			$query->set( 'posts_per_page', -1 );
		}
	}

	/**
	 * カスタム投稿タイプの登録
	 */
	public function cptui_register_my_cpts_post() {
		/**
		 * Post Type: インプット教材.
		 */

		$labels = array(
			'name'          => __( 'Kamedemy Item', 'kanso general child' ),
			'singular_name' => __( 'Kamedemy Item', 'kanso general child' ),
			'menu_name'     => __( 'Kamedemy', 'kanso general child' ),
			'all_items'     => __( 'Kamedemy Item一覧', 'kanso general child' ),
			'add_new'       => __( 'Kamedemy Itemの追加', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'Kamedemy Item', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'KamedemyのItemです（ビデオ or 音源）',
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
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'kdy_item',
				'with_front' => true,
			),
			'query_var'             => true,
			'menu_icon'             => 'dashicons-welcome-learn-more',
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'kdy' ),
		);

		register_post_type( 'kdy_item', $args );
	}

	/**
	 * カスタムtaxの登録
	 */
	public function cptui_register_my_taxes() {
		/**
		 * Taxonomy: スクラム教材.
		 */

		$labels = array(
			'name'          => __( 'Kamedemy', 'kanso general child' ),
			'singular_name' => __( 'Kamedemy', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'Kamedemy', 'kanso general child' ),
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'hierarchical'          => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'kdy',
				'with_front' => true,
			),
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'rest_base'             => 'kdy',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'show_in_quick_edit'    => true,
		);
		register_taxonomy( 'kdy', array( 'kdy_item' ), $args );
	}

	/**
	 * カスタムフィールドの追加
	 */
	protected function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

		endif;
	}

	public function activate() {
		$this->cptui_register_my_cpts_post();
		$this->cptui_register_my_taxes();

		flush_rewrite_rules( true );
	}

	public function deactivate() {
		flush_rewrite_rules();
	}
}
