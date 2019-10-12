<?php

/*
 * 独自のトライアルクーポンの仕組み
 */

class Toiee_Trialcoupon {

	private $plugin_dir;

	public function __construct( $file ) {
		$plugin_dir       = plugin_dir_path( $file );
		$this->plugin_dir = $plugin_dir;

		add_action( 'init', array( $this, 'cptui_register_my_cpts_post' ) );
		$this->add_acf();

		register_activation_hook( $plugin_dir, array( $this, 'activate' ) );
		register_deactivation_hook( $plugin_dir, array( $this, 'deactivate' ) );

	}

	function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5da03849f3351',
					'title'                 => 'トライアルクーポン注文設定',
					'fields'                => array(
						array(
							'key'               => 'field_5da03852c7aa0',
							'label'             => 'ユーザー',
							'name'              => 'wc4t_user',
							'type'              => 'user',
							'instructions'      => 'クーポンを利用したユーザーID',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'role'              => '',
							'allow_null'        => 0,
							'multiple'          => 0,
							'return_format'     => 'id',
						),
						array(
							'key'               => 'field_5da03890c7aa1',
							'label'             => '商品',
							'name'              => 'wc4t_product',
							'type'              => 'post_object',
							'instructions'      => '',
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
							'key'               => 'field_5da038dac7aa2',
							'label'             => '有効期限',
							'name'              => 'wc4t_expire',
							'type'              => 'date_time_picker',
							'instructions'      => '有効期限',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'display_format'    => 'Y-m-d H:i:s',
							'return_format'     => 'Y-m-d H:i:s',
							'first_day'         => 1,
						),
						array(
							'key'               => 'field_5da0391ec7aa3',
							'label'             => 'マスター',
							'name'              => 'wc4t_coupon',
							'type'              => 'post_object',
							'instructions'      => '',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'post_type'         => array(
								0 => 'wc4t_trialcoupon',
							),
							'taxonomy'          => '',
							'allow_null'        => 0,
							'multiple'          => 0,
							'return_format'     => 'id',
							'ui'                => 1,
						),
					),
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'wc4t_trialcpn_order',
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
					'key'                   => 'group_5da0367cafa03',
					'title'                 => 'トライアルクーポン設定',
					'fields'                => array(
						array(
							'key'               => 'field_5da036a640aaa',
							'label'             => 'トライアル日数',
							'name'              => 'wc4t_trial_days',
							'type'              => 'number',
							'instructions'      => 'トライアルできる日数を入れます。',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => 14,
							'placeholder'       => '',
							'prepend'           => '',
							'append'            => '',
							'min'               => '',
							'max'               => '',
							'step'              => '',
						),
						array(
							'key'               => 'field_5da036ee40aab',
							'label'             => '商品',
							'name'              => 'wc4t_product',
							'type'              => 'post_object',
							'instructions'      => '',
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
					),
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'wc4t_trialcoupon',
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

	/**
	 * カスタム投稿タイプの登録
	 */
	public function cptui_register_my_cpts_post() {

		/**
		 * Post Type: トライアルクーポン.
		 */

		$labels = array(
			'name'          => __( 'トライアルクーポン', 'kanso general child' ),
			'singular_name' => __( 'トライアルクーポン', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'トライアルクーポン', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => '商品を購入したこととして、トライアルさせることができるクーポンコードを発行。',
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
			'exclude_from_search'   => true,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'trialcoupon',
				'with_front' => true,
			),
			'query_var'             => true,
			'menu_icon'             => 'dashicons-tickets-alt',
			'supports'              => array( 'title', 'editor' ),
		);
		register_post_type( 'wc4t_trialcoupon', $args );

		/**
		 * Post Type: トライアル注文.
		 */

		$labels = array(
			'name'          => __( 'トライアル注文', 'kanso general child' ),
			'singular_name' => __( 'トライアル注文', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'トライアル注文', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'トライアルクーポンの注文履歴',
			'public'                => false,
			'publicly_queryable'    => false,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=wc4t_trialcoupon',
			'show_in_nav_menus'     => false,
			'exclude_from_search'   => true,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => false,
			'query_var'             => true,
			'supports'              => array( 'title' ),
		);

		register_post_type( 'wc4t_trialcpn_order', $args );


	}

	public function activate() {
		$this->cptui_register_my_cpts_post();
		flush_rewrite_rules( true );
	}

	public function deactivate() {
		flush_rewrite_rules();
	}


}
