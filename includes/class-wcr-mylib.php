<?php
/**
 * WooCommerce に マイライブラリ機能を実装する
 */

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class toiee_woocommerce_mylibrary {

	public function __construct() {
		// woocommerce add product meta
		// add_action( 'woocommerce_product_options_advanced', array($this, 'create_custom_field') );
		// add_action( 'woocommerce_process_product_meta', array($this, 'save_custom_field') );
		// woocommerce add new tab on user's my-account page
		add_action(
			'init',
			function () {
				add_rewrite_endpoint( 'my-library', EP_ROOT | EP_PAGES );
			}
		);
		add_filter(
			'query_vars',
			function ( $vars ) {
				$vars[] = 'my-library';
				return $vars;
			},
			0
		);
		add_filter(
			'woocommerce_account_menu_items',
			function ( $items ) {
				$items = array_slice( $items, 0, 1, true )
				+ array( 'my-library' => __( 'マイライブラリ', 'twmylib' ) )
				+ array_slice( $items, 1, count( $items ) - 1, true );
				return $items;
			}
		);
		add_action( 'woocommerce_account_my-library_endpoint', array( $this, 'mylibrary_content' ) );

		// display purchased info
		add_action( 'woocommerce_product_meta_start', array( $this, 'display_purchased_info' ), 5 );

		$this->add_acf();
	}

	public function add_acf() {
		if ( function_exists( 'acf_add_local_field_group' ) ) :

			// WooCommerceの商品画面に貼り付けるもの
			acf_add_local_field_group(
				array(
					'key'                   => 'group_5be20140832f0',
					'title'                 => 'マイライブラリ設定',
					'fields'                => array(
						array(
							'key'               => 'field_5be201568379a',
							'label'             => 'コンテンツURL',
							'name'              => 'wcmylib_url',
							'type'              => 'url',
							'instructions'      => 'マイライブラリに表示する場合は、指定してください。商品ページにも表示（購入している場合）します。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => 'https://',
						),
						array(
							'key'               => 'field_5be202018379b',
							'label'             => 'アクセス可能商品',
							'name'              => 'wcmylib_products',
							'type'              => 'post_object',
							'instructions'      => '以下の商品を持つユーザーを購入済みとして扱い、ライブラリURLを表示します。',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'post_type'         => array(
								0 => 'wcrestrict',
								1 => 'product',
								2 => 'product_variation',
								3 => 'wc_membership_plan',
							),
							'taxonomy'          => '',
							'allow_null'        => 1,
							'multiple'          => 1,
							'return_format'     => 'id',
							'ui'                => 1,
						),
					),
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'product',
							),
						),
					),
					'menu_order'            => 10,
					'position'              => 'normal',
					'style'                 => 'default',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'hide_on_screen'        => '',
					'active'                => 1,
					'description'           => 'マイライブラリ設定を行います',
				)
			);

		endif;
	}

	public function mylibrary_content() {
		$title         = '<h3>' . __( 'マイライブラリ', 'twmylib' ) . '</h3>';
		$tr_text       = '
		<tr>
			<td>%IMG%</td>
			<td>%NAME%</td>
			<td>%VIEW%</td>
			<td>%ORDER%</td>
		</tr>		
';
		$table_content = '';

		/* クーポンコードでチェック */
		$args = array(
			'post_type'  => 'wc4t_trialcpn_order',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'   => 'wc4t_user',
					'value' => get_current_user_id(),
				),
				array(
					'key'     => 'wc4t_expire',
					'value'   => date( 'Y-m-d H:i:s' ),
					'compare' => '>',
				),
			),
		);
		$trials = get_posts( $args );

		foreach ( $trials as $t ) {
			$product_id = get_field( 'wc4t_product', $t->ID );
			$expire     = date( 'Y年m月d日', strtotime( get_field( 'wc4t_expire', $t->ID ) ) );
			$mylib_url  = get_field( 'wcmylib_url', $product_id );
			if ( '' !== $mylib_url ) {
				$product = wc_get_product( $product_id );
				$p_img   = get_the_post_thumbnail_url( $product->get_id(), 'full' );
				$p_name  = $product->get_name();
				$p_url   = get_permalink( $product->get_id() );

				$table_content .= str_replace(
					array( '%IMG%', '%NAME%', '%VIEW%', '%ORDER%' ),
					array(
						'<img src="' . $p_img . '" style="height:3.5em;">',
						$p_name,
						'<a href="' . $mylib_url . '"><span uk-icon="play"></span> 視聴する</a>',
						'' . $expire . 'まで<br><a href="' . $p_url . '">商品情報</a>',
					),
					$tr_text
				);
			}
		}



		/* 注文を捜査して、表示 */
		$customer_orders = wc_get_orders(
			array(
				'meta_key'    => '_customer_user',
				'meta_value'  => get_current_user_id(),
				'post_status' => 'wc-completed',
				'numberposts' => -1,
			)
		);

		$index         = array();
		foreach ( $customer_orders as $order ) {

			// Order ID (added WooCommerce 3+ compatibility)
			$order_id  = $order->get_id();
			$order_url = $order->get_view_order_url();

			// Iterating through current orders items
			foreach ( $order->get_items() as $item_id => $item ) {

				// The corresponding product ID (Added Compatibility with WC 3+)
				$product_id = method_exists( $item, 'get_product_id' ) ? $item->get_product_id() : $item['product_id'];
				$product    = wc_get_product( $product_id );
				$mylib_url  = get_field( 'wcmylib_url', $product_id ); // ACF様様、足を向けて寝れない・・・

				if ( $mylib_url != '' && ( ! isset( $index[ $product_id ] ) ) ) {

					$product->get_image_id();
					$p_img = get_the_post_thumbnail_url( $product->get_id(), 'full' );

					$p_name = $product->get_name();
					$p_url  = get_permalink( $product->get_id() );

					$table_content .= str_replace(
						array( '%IMG%', '%NAME%', '%VIEW%', '%ORDER%' ),
						array(
							'<img src="' . $p_img . '" style="height:3.5em;">',
							$p_name,
							'<a href="' . $mylib_url . '"><span uk-icon="play"></span> 視聴する</a>',
							'<a href="' . $order_url . '">注文詳細</a><br><a href="' . $p_url . '">商品情報</a>',
						),
						$tr_text
					);

						  // for avoiding duplication of my lib
						  $index[ $product_id ] = 'registered';
				}
			}
		}

		if ( $table_content == '' ) {
			echo $title;
			echo '<p>ご利用できるコンテンツはありません。</p>';
		} else {
			echo $title;
			echo <<<EOD
<table class="uk-table uk-table-responsive uk-table-divider">
    <thead>
        <tr>
            <th>アイコン</th>
            <th>名前</th>
            <th>視聴リンク</th>
            <th>注文履歴</th>
        </tr>
    </thead>
	<tbody>		
		{$table_content}
	</tbody>
</table>
EOD;
		}
	}

	// マイライブラリを保存するためのフィールドを追加
	public function create_custom_field() {
		global $woocommerce, $post;

		woocommerce_wp_text_input(
			array(
				'id'          => 'toiee_woocommerce_mylibrary',
				'label'       => __( 'マイライブラリ', 'twmylib' ),
				'class'       => 'toiee-woocommerce-mylibrary',
				'desc_tip'    => true,
				'description' => __( '商品ページのURL(https://...)を入力してください。不要な場合は空白にしてください。', 'twmylib' ),
			)
		);
	}

	public function save_custom_field( $post_id ) {
		$product = wc_get_product( $post_id );
		$url     = isset( $_POST['toiee_woocommerce_mylibrary'] ) ? $_POST['toiee_woocommerce_mylibrary'] : '';
		$product->update_meta_data( 'toiee_woocommerce_mylibrary', sanitize_text_field( $url ) );

		$product->update_meta_data( 'include_ids', implode( ',', $_POST['include_ids'] ) );
		$product->save();

	}

	public function display_purchased_info() {
		// ログインチェック
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return '';
		}

		// my library チェック
		global $post;

		$mylib_url = get_field( 'wcmylib_url' ); // ACF様様、足を向けて寝れない・・・

		if ( ! preg_match( '/^http/', $mylib_url ) ) {
			return '';
		}

		// 購入チェック
		global $wcr_content;

		$product_ids = get_field( 'wcmylib_products', $post->ID );
		if ( is_array( $product_ids ) ) {
			array_unshift( $product_ids, $post->ID );
		} else {
			$product_ids = array( $post->ID );
		}

		$is_access = $wcr_content->check_access( $product_ids, '', false );
		if ( $is_access ) {
			echo '<div class="woocommerce-info">
			<strong>この教材は購入済みです</strong><br>
			教材へのアクセスは、<a href="' . $mylib_url . '">こちらをクリック</a>してください。
			</div>';
			return;
		}

		return '';
	}
}
