<?php
/**
 *
 * WooCommerce の Subscription を拡張して、installment (分割支払い) を実現する。
 *
 * Created by PhpStorm.
 * User: takame
 * Date: 2018-12-28
 * Time: 10:00
 */
class ToieeLab_Installment {

	public function __construct() {

		add_action( 'woocommerce_product_options_advanced', array( $this, 'add_options_advanced' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_add_fields' ) );

		add_filter( 'wcs_user_has_subscription', array($this, 'installment_check'), 99, 4);

	}

	/**
	 * 高度設定に「Installment」を追加する
	 */
	public function add_options_advanced(){
		global $woocommerce, $post;

		$product = wc_get_product( $post->ID );
		$type = $product->get_type();
		//
		if($type == 'variable-subscription' || $type == 'subscription') { // $post が、variable-subscription なら
			?>
			<div class="options_group installment">
			<?php
			woocommerce_wp_checkbox(
				array(
					'id'            => '_installment_subscription',
					'wrapper_class' => 'show',
					'label'         => __( '分割支払い用', 'woocommerce' ),
					'description'   => __( '分割支払い用にチェックを入れると、expire しても「有効」とみなします', 'woocommerce' )
				)
			);
			?>
			</div>
			<?php
		}
	}

	public function save_add_fields( $post_id ){
		$installment = $_POST['_installment_subscription'];
		update_post_meta( $post_id, '_installment_subscription', esc_attr( $installment ) );
	}

	public function installment_check($has_subscription, $user_id, $product_id, $status){

		if( $has_subscription ){
			return $has_subscription;
		}

		$installment = get_post_meta($product_id, '_installment_subscription', true);
		if( $installment == 'yes' ){
			$has_subscription = wcs_user_has_subscription( $user_id, $product_id, 'expired' );
		}

		return $has_subscription;
	}
}