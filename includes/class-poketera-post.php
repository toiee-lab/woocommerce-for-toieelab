<?php

/**
 * Scrum 機能を提供する
 */
class Toiee_Pocketera_Post {

	/**
	 * コンストラクタ
	 *
	 * Toiee_Magazine_Post constructor.
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'cptui_register_my_cpts_post' ) );
		add_action( 'init', array( $this, 'cptui_register_my_taxes' ) );
		$this->add_acf();

		add_action( 'pre_get_posts', array( $this, 'pre_get_post' ), 1 );
		add_action( 'save_post_pkt_feedback', array( $this, 'sum_feedback_num' ), 2, 10 );
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

		if ( is_tax( 'pkt_channel' ) ) {
			$pkt = $query->queried_object;
			if ( null === $pkt ) {
				return null;
			}

			$query->set( 'posts_per_page', -1 );
			$pkt_order = get_term_meta( $pkt->term_id, 'episode_type', true );
			if ( 'episodic' !== $pkt_order ) {
				$query->set( 'order', 'ASC' );
			}
			return;
		}
	}

	/**
	 * フィードバック数を表示するために、 pkt_report にフィードバック数を計算して格納する
	 *
	 * @param $post_id
	 * @param $post
	 */
	public function sum_feedback_num( $post_id, $post ) {

		$report_id = get_field( 'pkt_report', $post_id );
		if ( $report_id ) {
			$tmp_posts = get_posts(
				array(
					'post_type'      => 'pkt_feedback',
					'posts_per_page' => 20,
					'meta_query'     => array(
						array(
							'key'   => 'pkt_report',
							'value' => $report_id,
						),
					),
				)
			);

			update_post_meta( $report_id, 'feedback_num', count( $tmp_posts ) );
		}
	}

	/**
	 * オーダー情報を返す
	 *
	 * @param $mag_order string
	 * @return array
	 */
	public static function get_order( $mag_order ) {
		switch ( $mag_order ) {
			case 'name':
				$order   = 'ASC';
				$orderby = 'title';
				break;
			case 'old':
				$order   = 'ASC';
				$orderby = 'date';
				break;
			case 'new':
			default:
				$order   = 'DESC';
				$orderby = 'date';
		}

		return array(
			'order'   => $order,
			'orderby' => $orderby,
		);
	}

	/**
	 * カスタム投稿タイプの登録
	 */
	public function cptui_register_my_cpts_post() {
		/**
		 * Post Type: ポケてらアイテム.
		 */

		$labels = array(
			'name'          => __( 'ポケてらアイテム', 'kanso general child' ),
			'singular_name' => __( 'ポケてらアイテム', 'kanso general child' ),
			'menu_name'     => __( 'ポケてら', 'kanso general child' ),
			'all_items'     => __( 'すべてのアイテム', 'kanso general child' ),
			'add_new'       => __( 'アイテム新規追加', 'kanso general child' ),
			'add_new_item'  => __( 'アイテムの新規追加', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'ポケてらアイテム', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'ポケてらを構成するビデオ1つ1つ。Podcastの item に相当します',
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
				'slug'       => 'pkt_episode',
				'with_front' => true,
			),
			'query_var'             => true,
			'menu_icon'             => 'dashicons-location',
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'pkt_channel' ),
		);

		register_post_type( 'pkt_episode', $args );

		/**
		 * Post Type: レジュメ.
		 */

		$labels = array(
			'name'          => __( 'レジュメ', 'kanso general child' ),
			'singular_name' => __( 'レジュメ', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'レジュメ', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'ポケてらの進行用のレジュメを表します',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=pkt_episode',
			'show_in_nav_menus'     => false,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'pkt_resume',
				'with_front' => true,
			),
			'query_var'             => true,
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
		);

		register_post_type( 'pkt_resume', $args );

		/**
		 * Post Type: LFTノート.
		 */

		$labels = array(
			'name'          => __( 'LFTノート', 'kanso general child' ),
			'singular_name' => __( 'LFTノート', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'LFTノート', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'ポケてらのLFT専用のLFT contributeノート',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=pkt_episode',
			'show_in_nav_menus'     => false,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'pkt_lftnote',
				'with_front' => true,
			),
			'query_var'             => true,
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
		);

		register_post_type( 'pkt_lftnote', $args );

		/**
		 * Post Type: 開催レポート.
		 */

		$labels = array(
			'name'          => __( '開催レポート', 'kanso general child' ),
			'singular_name' => __( '開催レポート', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( '開催レポート', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'ポケてらのLFTによる開催レポートです',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=pkt_episode',
			'show_in_nav_menus'     => false,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'pkt_report',
				'with_front' => true,
			),
			'query_var'             => true,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'comments', 'author' ),
		);

		register_post_type( 'pkt_report', $args );

		/**
		 * Post Type: フィードバック.
		 */

		$labels = array(
			'name'          => __( 'フィードバック', 'kanso general child' ),
			'singular_name' => __( 'フィードバック', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'フィードバック', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => 'ポケてら参加者によるフィードバックです',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=pkt_episode',
			'show_in_nav_menus'     => false,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'pkt_feedback',
				'with_front' => true,
			),
			'query_var'             => true,
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
		);

		register_post_type( 'pkt_feedback', $args );

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
			'description'           => 'ポケてらの受講資料を表します',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => true,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_menu'          => 'edit.php?post_type=pkt_episode',
			'show_in_nav_menus'     => false,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array(
				'slug'       => 'pkt_material',
				'with_front' => true,
			),
			'query_var'             => true,
			'supports'              => array( 'title', 'editor' ),
		);

		register_post_type( 'pkt_material', $args );
	}



	/**
	 * カスタムたくそのみーの登録
	 */
	public function cptui_register_my_taxes() {
		/**
		 * Taxonomy: チャンネル.
		 */

		$labels = array(
			'name'          => __( 'ポケてら', 'kanso general child' ),
			'singular_name' => __( 'ポケてら', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'ポケてら', 'kanso general child' ),
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'hierarchical'          => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'pkt_channel',
				'with_front' => true,
			),
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'rest_base'             => 'pkt_channel',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'show_in_quick_edit'    => true,
		);
		register_taxonomy( 'pkt_channel', array( 'pkt_episode' ), $args );

	}

	/**
	 * カスタムフィールドの追加
	 */
	protected function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5ca16c8faf7fd',
					'title'                 => 'LFTノート評価',
					'fields'                => array(
						array(
							'key'               => 'field_5ca16ca9f592b',
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
							'key'               => 'field_5ca30b1275106',
							'label'             => 'ポケてら',
							'name'              => 'pocketera',
							'type'              => 'taxonomy',
							'instructions'      => '関連するポケてらチャンネルを選択してください。',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'pkt_channel',
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
								'value'    => 'pkt_lftnote',
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

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5ca16e1e49aae',
					'title'                 => 'レジュメ更新履歴',
					'fields'                => array(
						array(
							'key'               => 'field_5ca30c0fc7556',
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
							'field_type'        => 'select',
							'allow_null'        => 0,
							'add_term'          => 0,
							'save_terms'        => 0,
							'load_terms'        => 0,
							'return_format'     => 'id',
							'multiple'          => 0,
						),
						array(
							'key'               => 'field_5ca16e26b326c',
							'label'             => '更新履歴',
							'name'              => 'history',
							'type'              => 'wysiwyg',
							'instructions'      => '更新履歴を記載します',
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
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'pkt_resume',
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
					'key'                   => 'group_5ca16a61ee34f',
					'title'                 => '参加者フィードバック',
					'fields'                => array(
						array(
							'key'               => 'field_5ca16aac4846a',
							'label'             => '参加したポケてら',
							'name'              => 'pkt_report',
							'type'              => 'post_object',
							'instructions'      => '参加したポケてら（開催レポート）を選択します',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'post_type'         => array(
								0 => 'pkt_report',
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
								'value'    => 'pkt_feedback',
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

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5ca30f34787eb',
					'title'                 => '授業資料(ポケてら)',
					'fields'                => array(
						array(
							'key'               => 'field_5ca30f4e1834f',
							'label'             => 'ポケてら',
							'name'              => 'pocketera',
							'type'              => 'taxonomy',
							'instructions'      => '関連するポケてらを選択してください',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'pkt_channel',
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
								'value'    => 'pkt_material',
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

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5ca30db54a23a',
					'title'                 => '開催レポート',
					'fields'                => array(
						array(
							'key'               => 'field_5ca30dbb54d7e',
							'label'             => 'ポケてら',
							'name'              => 'pocketera',
							'type'              => 'taxonomy',
							'instructions'      => '関連するポケてらを選択してください',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'pkt_channel',
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
								'value'    => 'pkt_report',
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
}
