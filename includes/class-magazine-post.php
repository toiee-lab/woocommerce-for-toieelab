<?php

/**
 * Scrum 機能を提供する
 */
class Toiee_Magazine_Post {

	/**
	 * コンストラクタ
	 *
	 * Toiee_Magazine_Post constructor.
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'cptui_register_my_cpts_post' ) );
		add_action( 'init', array( $this, 'cptui_register_my_taxes' ) );
		$this->add_acf();
		add_action( 'wp_head', array( $this, 'add_style' ), 99, 0 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_post' ), 1 );
	}

	/**
	 * マガジンのためのcssを出力する
	 */
	public function add_style() {
		if ( is_tax( 'magazine' ) || 'mag_post' === get_post_type() ) {

			if ( is_tax() ) {
				$magazine = get_queried_object();
			} else {
				$terms = wp_get_post_terms( get_the_ID(), 'magazine' );
				if ( isset( $terms[0] ) ) {
					$magazine = $terms[0];
				} else {
					wp_die( '必ず、所属する雑誌（magazine taxonomy)を指定してください。' );
				}
			}

			$magazine_fields = get_fields( $magazine );

			$mag = array(
				'title'     => $magazine->name,
				'subtitle'  => $magazine_fields['mag_subtitle'],
				'color'     => esc_attr( $magazine_fields['mag_title_color'] ),
				'bgcolor'   => esc_attr( $magazine_fields['mag_bgcolor'] ),
				'bgimg'     => $magazine_fields['mag_bgimg'],
				'header'    => $magazine_fields['mag_header_notice'],
				'footer'    => $magazine_fields['mag_footer_notice'],
				'order'     => $magazine_fields['mag_order'],
				'status'    => $magazine_fields['mag_status'],
				'close_msg' => $magazine_fields['mag_close_notice'],
				'css'       => $magazine_fields['mag_css'],
				'css_off'   => $magazine_fields['mag_css_off'],
				'overlay'   => $magazine_fields['mag_overlay_height'],
			);

			$overlap = $mag['overlay'];
			?>
	<style id="magazine">
			<?php if ( ! $mag['css_off'] ) : ?>
		.mag-header {
				<?php if ( $mag['bgimg'] ) : ?>
			background: linear-gradient(rgba(255,255,255,0), rgba(255,255,255,0), rgba(255,255,255,1)), url(<?php echo $mag['bgimg']; ?>);background-size: cover;padding-bottom: <?php echo $overlap; ?>px;
				<?php else : ?>
			background: linear-gradient(<?php echo $mag['bgcolor']; ?>, <?php echo $mag['bgcolor']; ?>, white);padding-bottom:<?php echo $overlap; ?>px;
				<?php endif; ?>
		}
		.mag-header,
		.mag-h1,
		.mag-h1 a,
		.mag-h1 a:hover,
		.mag-lead,
		.mag-lead a,
		.mag-lead a:hover {
			color: <?php echo $mag['color']; ?>;
			text-decoration: none;
		}

		.mag-overlap {
			margin-top: -<?php echo $overlap; ?>px;
		}
			<?php endif; ?>
			<?php echo $mag['css']; ?>
	</style>
			<?php
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
			return;
		}

		if ( is_tax( 'magazine' ) ) {
			$mag = $query->queried_object;
			if ( null === $mag ) {
				return '';
			}

			$mag_order = get_term_meta( $mag->term_id, 'mag_order', true );

			$order = $this->get_order( $mag_order );

			$query->set( 'order', $order['order'] );
			$query->set( 'orderby', $order['orderby'] );
			return;
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
		 * Post Type: マガジン(記事).
		 */
		$labels = array(
			'name'          => __( 'マガジン(記事)', 'kanso general child' ),
			'singular_name' => __( 'マガジン(記事)', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'マガジン(記事)', 'kanso general child' ),
			'labels'                => $labels,
			'description'           => '雑誌としての投稿まとめ機能を提供',
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
				'slug'       => 'mag_post',
				'with_front' => true,
			),
			'query_var'             => true,
			'menu_icon'             => 'dashicons-book',
			'supports'              => array( 'title', 'editor', 'thumbnail' ),
			'taxonomies'            => array( 'magazine' ),
			'show_in_admin_bar'     => false,
		);

		register_post_type( 'mag_post', $args );
	}



	/**
	 * カスタムたくそのみーの登録
	 */
	public function cptui_register_my_taxes() {

		/**
		 * Taxonomy: マガジン.
		 */

		$labels = array(
			'name'          => __( 'マガジン', 'kanso general child' ),
			'singular_name' => __( 'マガジン', 'kanso general child' ),
		);

		$args = array(
			'label'                 => __( 'マガジン', 'kanso general child' ),
			'labels'                => $labels,
			'public'                => true,
			'publicly_queryable'    => true,
			'hierarchical'          => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => true,
			'query_var'             => true,
			'rewrite'               => array(
				'slug'       => 'magazine',
				'with_front' => true,
			),
			'show_admin_column'     => true,
			'show_in_rest'          => true,
			'rest_base'             => 'magazine',
			'rest_controller_class' => 'WP_REST_Terms_Controller',
			'show_in_quick_edit'    => false,
		);
		register_taxonomy( 'magazine', array( 'mag_post' ), $args );
	}

	/**
	 * カスタムフィールドの追加
	 */
	protected function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5c99d96470341',
					'title'                 => 'マガジン',
					'fields'                => array(
						array(
							'key'               => 'field_5c99d9817e04f',
							'label'             => 'サブタイトル',
							'name'              => 'mag_subtitle',
							'type'              => 'text',
							'instructions'      => 'マガジンのサブタイトルです',
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
							'key'               => 'field_5c99db367e055',
							'label'             => 'オーダー',
							'name'              => 'mag_order',
							'type'              => 'select',
							'instructions'      => '記事の表示順序です。',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'new'  => '新しい順（最新がトップ）',
								'old'  => '古い順（古い記事がトップ）',
								'name' => '名前順（ナンバー付き記事を想定）',
							),
							'default_value'     => array(
								0 => 'date-asc',
							),
							'allow_null'        => 0,
							'multiple'          => 0,
							'ui'                => 1,
							'ajax'              => 0,
							'return_format'     => 'value',
							'placeholder'       => '',
						),
						array(
							'key'               => 'field_5c9ada8d8db83',
							'label'             => 'タイトルカラー',
							'name'              => 'mag_title_color',
							'type'              => 'color_picker',
							'instructions'      => 'タイトル、サブタイトルの色。背景とコントラストが高くなるように設定してください。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
						),
						array(
							'key'               => 'field_5c99d9bd7e050',
							'label'             => 'ヘッダー背景色',
							'name'              => 'mag_bgcolor',
							'type'              => 'color_picker',
							'instructions'      => 'どちらを使うか選んでください',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
						),
						array(
							'key'               => 'field_5c99da2c7e051',
							'label'             => 'ヘッダー画像',
							'name'              => 'mag_bgimg',
							'type'              => 'image',
							'instructions'      => 'ヘッダーに使う背景画像。カラーは無視します。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'return_format'     => 'url',
							'preview_size'      => 'thumbnail',
							'library'           => 'all',
							'min_width'         => '',
							'min_height'        => '',
							'min_size'          => '',
							'max_width'         => '',
							'max_height'        => '',
							'max_size'          => '',
							'mime_types'        => '',
						),
						array(
							'key'               => 'field_5c99da767e052',
							'label'             => '追加css',
							'name'              => 'mag_css',
							'type'              => 'textarea',
							'instructions'      => '追加CSSが必要な場合に利用。本格的に変更する場合は、テンプレートを変更してください。例えば、 .mag-lead { color : #ccc; } でリード文のフォント色を変更できます。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '',
							'maxlength'         => '',
							'rows'              => '',
							'new_lines'         => '',
						),
						array(
							'key'               => 'field_5c9ae1e00f95b',
							'label'             => 'デフォルトCSSを出力しない',
							'name'              => 'mag_css_off',
							'type'              => 'true_false',
							'instructions'      => 'デフォルトで出力されるCSSを出力しない場合は、true にしてください',
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
							'key'               => 'field_5c9ae5181834f',
							'label'             => 'オーバーレイ(重なり)',
							'name'              => 'mag_overlay_height',
							'type'              => 'number',
							'instructions'      => '',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => 200,
							'placeholder'       => 200,
							'prepend'           => '',
							'append'            => 'px',
							'min'               => '',
							'max'               => '',
							'step'              => '',
						),
						array(
							'key'               => 'field_5c99dabc7e053',
							'label'             => 'ヘッダーお知らせ',
							'name'              => 'mag_header_notice',
							'type'              => 'wysiwyg',
							'instructions'      => 'ヘッダーに挿入するお知らせ',
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
							'key'               => 'field_5c99dafd7e054',
							'label'             => 'フッターお知らせ',
							'name'              => 'mag_footer_notice',
							'type'              => 'wysiwyg',
							'instructions'      => 'フッターに表示するお知らせです',
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
							'key'               => 'field_5c99dc497e056',
							'label'             => '状態',
							'name'              => 'mag_status',
							'type'              => 'select',
							'instructions'      => '公開、非公開を選びます',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'public' => '公開',
								'close'  => '非公開',
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
							'key'               => 'field_5c99dda98baee',
							'label'             => '閉鎖時のお知らせ',
							'name'              => 'mag_close_notice',
							'type'              => 'wysiwyg',
							'instructions'      => '閉鎖時に表示するお知らせです',
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
					),
					'location'              => array(
						array(
							array(
								'param'    => 'taxonomy',
								'operator' => '==',
								'value'    => 'magazine',
							),
						),
					),
					'menu_order'            => 0,
					'position'              => 'acf_after_title',
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
