<?php
/*
 * WooCommerceの動作変更
 *
 */


//登録フォームに「名前」と「苗字」を挿入
add_action( 'woocommerce_register_form_start', function () {?>
		<p class="form-row form-row-first">
			<label for="reg_billing_last_name"><?php _e( 'Last name', 'woocommerce' ); ?><span class="required">*</span></label>
			<input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) esc_attr_e( $_POST['billing_last_name'] ); ?>" />
		</p>
		<p class="form-row form-row-last">
			<label for="reg_billing_first_name"><?php _e( 'First name', 'woocommerce' ); ?></label>
			<input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) esc_attr_e( $_POST['billing_first_name'] ); ?>" />
		</p>		
		<div class="clear"></div>
       <?php
 } );

// 登録フォームのバリデーション 
add_action( 'woocommerce_register_post', function ( $username, $email, $validation_errors ) {
	if ( isset( $_POST['billing_last_name'] ) && empty( $_POST['billing_last_name'] ) ) {
		$validation_errors->add( 'billing_last_name_error', __( '<strong>Error</strong>: Last name is required!.', 'woocommerce' ) );
	
	}
	return $validation_errors;
}, 10, 3 );

// 登録フォームに追加したデータの保存
add_action( 'woocommerce_created_customer', function ( $customer_id ) {
      if ( isset( $_POST['billing_first_name'] ) ) {
             //First name field which is by default
             update_user_meta( $customer_id, 'first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
             // First name field which is used in WooCommerce
             update_user_meta( $customer_id, 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
      }
      if ( isset( $_POST['billing_last_name'] ) ) {
             // Last name field which is by default
             update_user_meta( $customer_id, 'last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
             // Last name field which is used in WooCommerce
             update_user_meta( $customer_id, 'billing_last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
      }

} );
