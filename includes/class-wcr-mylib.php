<?php
/**
 * WooCommerce に マイライブラリ機能を実装する
 */
 
defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class toiee_woocommerce_mylibrary
{
	public function __construct()
	{
		register_activation_hook(__FILE__, array($this,'plugin_activate')); //activate hook
		register_deactivation_hook(__FILE__, array($this,'plugin_deactivate')); //deactivate hook
		register_uninstall_hook(__FILE__, array(&$this, 'plugin_uninstall')); //uninstall hook
		
		
		// woocommerce add product meta
		add_action( 'woocommerce_product_options_advanced', array($this, 'create_custom_field') );
		add_action( 'woocommerce_process_product_meta', array($this, 'save_custom_field') );	
		
		// woocommerce add new tab on user's my-account page
		add_action( 'init',
			function () {
				add_rewrite_endpoint( 'my-library', EP_ROOT | EP_PAGES );
			} );
		add_filter( 'query_vars',
			function ( $vars ) {
				$vars[] = 'my-library';
				return $vars;
			},
			0 );
		add_filter( 'woocommerce_account_menu_items',
			function ( $items ) {
				$items = array_slice($items, 0, 1, true)
							+ array("my-library" => __( 'マイライブラリ', 'twmylib' ))
							+ array_slice($items, 1, count($items)-1, true);
				return $items;
			} );
		add_action( 'woocommerce_account_my-library_endpoint', array($this, 'mylibrary_content') );
		
		
		// display purchased info
		add_action( 'woocommerce_product_meta_start', array($this, 'display_purchased_info'), 5 );
	}
	
	public function mylibrary_content()
	{
		$tr_text = '
		<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-completed order">
			<td class="woocommerce-orders-table__cell" data-title="アイコン">%IMG%</td>
			<td class="woocommerce-orders-table__cell" data-title="名前">%NAME%</td>
			<td class="woocommerce-orders-table__cell" data-title="視聴する">%VIEW%</td>
			<td class="woocommerce-orders-table__cell" data-title="オーダー">%ORDER%</td>
		</tr>		
';		
		
		$title = '<h3>'. __( 'マイライブラリ', 'twmylib' ) . '</h3>';

		$customer_orders = wc_get_orders( array(
						    'meta_key' => '_customer_user',
						    'meta_value' => get_current_user_id(),
						    'post_status' => 'wc-completed',
						    'numberposts' => -1
						) );
		
		$table_content = '';
		foreach($customer_orders as $order ){
		
		    // Order ID (added WooCommerce 3+ compatibility)
		    $order_id = method_exists( $order, 'get_id' ) ? $order->get_id() : $order->id;
		    $order_url = $order->get_view_order_url();
		    		
		    // Iterating through current orders items
		    foreach($order->get_items() as $item_id => $item){
		
		        // The corresponding product ID (Added Compatibility with WC 3+) 
		        $product_id = method_exists( $item, 'get_product_id' ) ? $item->get_product_id() : $item['product_id'];
		        $product = wc_get_product( $product_id );
		        $mylib_url = get_field( 'wcmylib_url' , $product_id); //ACF様様、足を向けて寝れない・・・
		        				
				if( $mylib_url != ''){
			        $product->get_image_id();
					$p_img = get_the_post_thumbnail_url( $product->get_id(), 'full' );
					
					$p_name = $product->get_name();
					$p_url = get_permalink( $product->get_id() );
		        
				//echo "mylib: {$mylib_url}, img:{$p_img}, title:{$p_name}, order:{$order_url}, product page:$p_url<br>";

					$table_content .= str_replace(
						array('%IMG%', '%NAME%', '%VIEW%', '%ORDER%'), 
						array(
							'<img src="'.$p_img.'" style="height:3.5em;">',
							$p_name, 
							'<a href="'.$mylib_url.'">視聴する</a>',
							'<a href="'.$order_url.'">注文詳細</a><br><a href="'.$p_url.'">商品情報</a>'
						), 
						$tr_text);
				}
		    }
		}
		
		
		if( $table_content == ''){
			echo $title;
			echo '<p>ご利用できるコンテンツはありません。</p>';
		}
		else{
			echo $title;
			echo <<<EOD
<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
	<tbody>		
		{$table_content}
	</tbody>
</table>
EOD;
		}
	}
	
	// マイライブラリを保存するためのフィールドを追加
	public function create_custom_field()
	{
		global $woocommerce, $post;
		
		woocommerce_wp_text_input( 
			array(
				'id' => 'toiee_woocommerce_mylibrary',
				'label' => __( 'マイライブラリ', 'twmylib' ),
				'class' => 'toiee-woocommerce-mylibrary',
				'desc_tip' => true,
				'description' => __( '商品ページのURL(https://...)を入力してください。不要な場合は空白にしてください。', 'twmylib' ),
		) );
	}

	public function save_custom_field( $post_id )
	{
		$product = wc_get_product( $post_id );
		$url = isset( $_POST['toiee_woocommerce_mylibrary'] ) ? $_POST['toiee_woocommerce_mylibrary'] : '';
		$product->update_meta_data( 'toiee_woocommerce_mylibrary', sanitize_text_field( $url ) );
		
		$product->update_meta_data( 'include_ids' , implode(',', $_POST['include_ids']) );
		$product->save();
		
	}
	
	public function display_purchased_info()
	{
		//ログインチェック
		$user = wp_get_current_user();
		if( !  $user->exists() ){
			return '';
		}
		
		//my library チェック
		global $post;
		
        $mylib_url = get_field( 'wcmylib_url' ); //ACF様様、足を向けて寝れない・・・

		if( ! preg_match('/^http/', $mylib_url ) ) {
			return '';
		}
		
		//購入チェック
		global $wcr_content;

		$product_ids = get_field( 'wcmylib_products', $post->ID );
		if( is_array( $product_ids )) {
			array_unshift($product_ids, $post->ID);
		}
		else {
			$product_ids = array( $post->ID );
		}

		
		$is_access = $wcr_content->check_access($product_ids);
		if( $is_access ) {
			echo '<div class="woocommerce-info">
			<strong>この教材は購入済みです</strong><br>
			教材へのアクセスは、<a href="'.$mylib_url.'">こちらをクリック</a>してください。
			</div>';
			return;
		}
		
		return '';
	}

	public function plugin_activate()
	{
		// 何かあれば実装する
	}
	
	public function plugin_deactivate()
	{
		// 何かあれば実装する
	}
	
	public function plugin_uninstall()
	{
		// 使ったオプションを削除する（将来的に・・・）
	}
}
