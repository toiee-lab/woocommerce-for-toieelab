<?php

/**
 * Scrum 機能を提供する
 */
class Toiee_Magazine_Post {

	public $tabs;

	/**
	 * コンストラクタ
	 *
	 * Toiee_Magazine_Post constructor.
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'cptui_register_my_cpts_post' ) );
		add_action( 'init', array( $this, 'cptui_register_my_taxes' ) );
		$this->add_acf();

	}

	/**
	 * カスタム投稿タイプの登録
	 */
	public function cptui_register_my_cpts_post() {

		/**
		 * Post Type: スクラム投稿.
		 */

	}

	/**
	 * カスタムたくそのみーの登録
	 */
	public function cptui_register_my_taxes() {

		/**
		 * Taxonomy: マガジン.
		 */

	}

	/**
	 * カスタムフィールドの追加
	 */
	protected function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

		endif;
	}


	function get_scrums_by_series_id( $series_id, $name ) {
		$scrums = get_terms(
			array(
				'taxonomy'   => 'magazine',
				'hide_empty' => false,
				'meta_key'   => $name,
				'meta_value' => $series_id,
			)
		);

		return $scrums;
	}


}
