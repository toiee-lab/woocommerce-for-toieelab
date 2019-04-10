<?php

/**
 * Scrum 機能を提供する
 */
class Toiee_Mimidemy_Post {

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

		$plugin_dir = plugin_dir_path( $file );
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

		if ( is_tax( 'mmdmy' ) ) {
			$mdy = $query->queried_object;
			if ( null === $mdy ) {
				return null;
			}

			$query->set( 'posts_per_page', -1 );
			$mdy_order = get_term_meta( $mdy->term_id, 'episode_type', true );
			if ( 'episodic' !== $mdy_order ) {
				$query->set( 'order', 'ASC' );
			}
			return;
		}
	}

	/**
	 * カスタム投稿タイプの登録
	 */
	public function cptui_register_my_cpts_post() {
		/**
		 * Post Type: 耳デミーアイテム.
		 */

		$labels = array(
			'name'          => __( '耳デミーアイテム', 'kanso general child' ),
			'singular_name' => __( '耳デミーアイテム', 'kanso general child' ),
			'menu_name'     => __( '耳デミー', 'kanso general child' ),
			'all_items'     => __( 'すべてのアイテム', 'kanso general child' ),
			'add_new'       => __( 'アイテム新規追加', 'kanso general child' ),
			'add_new_item'  => __( 'アイテムの新規追加', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( '耳デミーアイテム', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => '耳デミーを構成するビデオ・オーディオ1つ1つ。Podcastの item に相当します',
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
				'slug'       => 'mmdmy_episode',
				'with_front' => true,
			),
			'query_var'             => true,
			'menu_icon'             => 'dashicons-welcome-learn-more',
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'mmdmy' ),
		);

		register_post_type( 'mmdmy_episode', $args );

		/**
		 * Post Type: 受講資料.
		 */

		$labels = array(
			'name'          => __( '受講資料', 'kanso general child' ),
			'singular_name' => __( '受講資料', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( '受講資料', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => '耳デミーの受講資料を表します',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=mmdmy_episode',
			'show_in_nav_menus'     => false,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'mdy_material',
				'with_front' => true,
			),
			'query_var'             => true,
			'supports'              => array( 'title', 'editor' ),
		);

		register_post_type( 'mdy_material', $args );
	}



	/**
	 * カスタムtaxの登録
	 */
	public function cptui_register_my_taxes() {

		/**
		 * Taxonomy: 耳デミー.
		 */

		$labels = array(
			'name'          => __( '耳デミー', 'kanso general child' ),
			'singular_name' => __( '耳デミー', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( '耳デミー', 'kanso general child' ),
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'hierarchical'          => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'mmdmy',
				'with_front' => true,
			),
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'rest_base'             => 'mmdmy',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'show_in_quick_edit'    => false,
		);
		register_taxonomy( 'mmdmy', array( 'mmdmy_episode' ), $args );

	}

	/**
	 * カスタムフィールドの追加
	 */
	protected function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5cabd7781d0f2',
					'title'                 => '授業資料 (耳デミー)',
					'fields'                => array(
						array(
							'key'               => 'field_5cabd77824df2',
							'label'             => '耳デミー',
							'name'              => 'mimidemy',
							'type'              => 'taxonomy',
							'instructions'      => '関連するポケてらを選択してください',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'mmdmy',
							'field_type'        => 'select',
							'allow_null'        => 0,
							'add_term'          => 0,
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
								'value'    => 'mdy_material',
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
		$this->cptui_register_my_taxes();

		flush_rewrite_rules( true );
	}

	public function deactivate() {
		flush_rewrite_rules();
	}
}
