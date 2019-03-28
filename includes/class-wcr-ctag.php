<?php

/**
 * WooCommerce の 認証を行うクラス
 */
class Woocommerce_CustomTabs {

	public $tabs;

	function __construct() {
		// カスタム投稿タイプの設定
		add_action( 'init', array( $this, 'create_post_type' ) );

		add_filter( 'woocommerce_product_tabs', array( $this, 'register_tab' ) );
		add_filter( 'woocommerce_product_tabs', array( $this, 'remove_tabs' ), 98 );

		$this->tabs = null;
	}


	function get_tabs() {
		if ( is_null( $this->tabs ) ) {
			$this->load_tabs();
		}

		return $this->tabs;
	}

	function load_tabs() {

		$posts = get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => 'wcr-customtab',
			)
		);

		$tabs = array();
		foreach ( $posts as $key => $post ) {
			$v = get_fields( $post->ID );

			$tabs[ $key ]['ID']           = $post->ID;
			$tabs[ $key ]['post_content'] = $post->post_content;

			$tabs[ $key ] = array_merge( $tabs[ $key ], $v );
		}

		$this->tabs = $tabs;
	}

	public function create_post_type() {
		register_post_type(
			'wcr-customtab',
			array(
				'label'               => 'カスタムタブ',
				'public'              => false,
				'exclude_from_search' => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-tag',
				'menu_position'       => 5,
				'hierarchical'        => false,
				'has_archive'         => false,
				'supports'            => array(
					'title',
					'editor',
				),
				'capability_type'     => 'product',
			)
		);
	}

	public function remove_tabs( $tabs ) {
		unset( $tabs['additional_information'] );
		return $tabs;
	}

	public function register_tab( $ret_tabs ) {

		// 現在の商品を取得
		global $product;
		$cat_ids = $product->get_category_ids();
		$tabs    = $this->get_tabs();

		foreach ( $tabs as $tab ) {

			$add_tab = false;
			if ( $tab['ctab-category-all'] == 1 ) { // カテゴリ追加
				$add_tab = true;
			} else {
				foreach ( $tab['ctab-category'] as $cat_id ) {
					if ( array_search( $cat_id, $cat_ids ) !== false ) {
						$add_tab = true;
						break;
					}
				}
			}

			if ( $add_tab ) {
				$ret_tabs[ 'toiee_custom_tab_' . $tab['ID'] ] = array(
					'title'    => __( $tab['ctab-label'], 'textdomain' ),
					'callback' => array( $this, 'output_tab_content' ),
					'priority' => 50,
				);
			}
		}

		return $ret_tabs;
	}

	/*
	* $key : tabs の key
	* $tab['title'] は、上記のタイトル
	*/
	public function output_tab_content( $key, $tab ) {

		preg_match( '/^toiee_custom_tab_([0-9]+)$/', $key, $matches );

		$post = get_post( $matches[1] );

		global $wp_filter;

		if ( $post ) {
			// echo toiee_simple_the_content( $post->post_content );
			echo apply_filters( 'the_content', $post->post_content );
			// echo '<pre style="display:none">'. print_r( $wp_filter['the_content'] , true).'</pre>';
		} else {
			echo '';
		}
	}
}
