<?php

/**
 * toiee Learning Material (toiee Lab 教材）のためのもの
 */
class Toiee_Tlm_Post {

	private $plugin_dir;
	/**
	 * コンストラクタ
	 *
	 * Toiee_Magazine_Post constructor.
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

		add_action( 'created_term', array( $this, 'create_related_post' ), 3, 10 );
	}

	/**
	 * ターム作成時に関連する投稿タイプを作成する（授業資料、レジュメ、ノート）
	 *
	 * @param integer $term_id タームID.
	 * @param integer $tt_id term_taxonomu_id.
	 * @param string  $taxonomy タクソノミー名.
	 */
	function create_related_post( $term_id, $tt_id, $taxonomy ) {

		if ( 'tlm' === $taxonomy ) {
			$term_obj = get_term_by( 'id', $term_id, $taxonomy );

			foreach ( array( 'tlm_ws_aid', 'tlm_ws_lft', 'tlm_add' ) as $post_type ) {
				$post_id = wp_insert_post(
					array(
						'post_type'   => $post_type,
						'post_title'  => $term_obj->name,
						'post_name'   => $term_obj->slug,
						'post_status' => 'publish',
						'tax_input'   => array( $taxonomy => $term_id ),
					)
				);
			}

			/* feed で使うテンプレートを変更する */

		}
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

		if ( is_tax( 'tlm' ) ) {
			$term = $query->queried_object;
			if ( null === $term ) {
				return null;
			}

			$query->set( 'posts_per_page', -1 );

			/* テンプレートの切り替え */
			if ( is_feed() ) {
				$query->set( 'post_type', 'tlm_in' );
				add_filter( 'toiee_pcast_feed_template_file', array( $this, 'return_feed_template' ) );
				return;
			}
		}
	}

	public function return_feed_template() {
		return $this->plugin_dir . 'templates/feed-pcast-tlm.php';
	}

	/**
	 * カスタム投稿タイプの登録
	 */
	public function cptui_register_my_cpts_post() {
		/**
		 * Post Type: インプット教材.
		 */

		$labels = array(
			'name'          => __( 'インプット教材', 'kanso general child' ),
			'singular_name' => __( 'インプット教材', 'kanso general child' ),
			'menu_name'     => __( 'スクラム教材', 'kanso general child' ),
			'all_items'     => __( 'インプット教材一覧', 'kanso general child' ),
			'add_new'       => __( 'インプット教材の追加', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'インプット教材', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'toiee教材のインプット用です（旧耳デミー）',
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
				'slug'       => 'tlm_in',
				'with_front' => true,
			),
			'query_var'             => true,
			'menu_icon'             => 'dashicons-welcome-learn-more',
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'tlm' ),
		);

		register_post_type( 'tlm_in', $args );

		/**
		 * Post Type: WS教材.
		 */

		$labels = array(
			'name'          => __( 'WS教材', 'kanso general child' ),
			'singular_name' => __( 'WS教材', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'WS教材', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'アウトプット（ワークショップ）教材',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=tlm_in',
			'show_in_nav_menus'     => true,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'tlm_ws',
				'with_front' => true,
			),
			'query_var'             => true,
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'tlm' ),
		);

		register_post_type( 'tlm_ws', $args );

		/**
		 * Post Type: WS資料.
		 */

		$labels = array(
			'name'          => __( 'WS資料', 'kanso general child' ),
			'singular_name' => __( 'WS資料', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'WS資料', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => '',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=tlm_in',
			'show_in_nav_menus'     => true,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'tlm_ws_aid',
				'with_front' => true,
			),
			'query_var'             => true,
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'tlm' ),
		);

		register_post_type( 'tlm_ws_aid', $args );

		/**
		 * Post Type: WSレジュメ.
		 */

		$labels = array(
			'name'          => __( 'WSレジュメ', 'kanso general child' ),
			'singular_name' => __( 'WSレジュメ', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'WSレジュメ', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => '',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=tlm_in',
			'show_in_nav_menus'     => true,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'tlm_ws_lft',
				'with_front' => true,
			),
			'query_var'             => true,
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'tlm' ),
		);

		register_post_type( 'tlm_ws_lft', $args );

		/**
		 * Post Type: WSアーカイブ.
		 */

		$labels = array(
			'name'          => __( 'WSアーカイブ', 'kanso general child' ),
			'singular_name' => __( 'WSアーカイブ', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'WSアーカイブ', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'ワークショップのアーカイブ',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=tlm_in',
			'show_in_nav_menus'     => true,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'tlm_archive',
				'with_front' => true,
			),
			'query_var'             => true,
			'menu_position'         => 5,
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'tlm' ),
		);

		register_post_type( 'tlm_archive', $args );

		/**
		 * Post Type: 教材追加情報.
		 */

		$labels = array(
			'name'          => __( '教材追加情報', 'kanso general child' ),
			'singular_name' => __( '教材追加情報', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( '教材追加情報', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => '',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=tlm_in',
			'show_in_nav_menus'     => true,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'tlm_add',
				'with_front' => true,
			),
			'query_var'             => true,
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'tlm' ),
		);

		register_post_type( 'tlm_add', $args );
	}

	/**
	 * カスタムtaxの登録
	 */
	public function cptui_register_my_taxes() {
		/**
		 * Taxonomy: スクラム教材.
		 */

		$labels = array(
			'name'          => __( 'スクラム教材', 'kanso general child' ),
			'singular_name' => __( 'スクラム教材', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'スクラム教材', 'kanso general child' ),
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'hierarchical'          => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'tlm',
				'with_front' => true,
			),
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'rest_base'             => 'tlm',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'show_in_quick_edit'    => true,
		);
		register_taxonomy( 'tlm', array( 'tlm_in', 'tlm_add', 'tlm_archive', 'tlm_ws', 'tlm_ws_aid', 'tlm_ws_lft' ), $args );
	}

	/**
	 * カスタムフィールドの追加
	 */
	protected function add_acf() {
		if( function_exists('acf_add_local_field_group') ):



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
