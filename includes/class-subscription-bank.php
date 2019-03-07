<?php
/**
 * Created by PhpStorm.
 * User: takame
 * Date: 2019-03-07
 * Time: 10:23
 */

class ToieeLab_Subscription_Bank {

	public function __construct() {

		add_filter( 'wcs_user_has_subscription', array($this, 'expand_bank_user'), 99, 4);

	}

	public function expand_bank_user($has_subscription, $user_id, $product_id, $status){

		// true なら、true を返す
		if( $has_subscription ) {
			return $has_subscription;
		}
		// status が 'active' を指定されていなければ、以下調べる必要はない
		if( $status != 'active' ) {
			return $has_subscription;
		}

		$subscriptions = wcs_get_users_subscriptions( $user_id );

		foreach ( $subscriptions as $subscription ) {
			// 状態が on-hold で、マニュアルペイメント
			if ( $subscription->has_product( $product_id )
			     && $subscription->has_status( 'on-hold' )
			     && $subscription->is_manual()
				 && ( count( $subscription->get_related_orders( 'all', 'renewal' ) ) > 0)  //リニューアルのオーダーの場合だけ
			) {

				$next_date  = $subscription->get_date( 'next_payment' );
				$next_time  = strtotime( $next_date );

				$now_time   = time();

				$diff = $now_time - $next_time;

				if( $diff < 60 * 60 * 24 * 10 ) { //10日間
					$has_subscription = true;
					break;
				}
			}
		}

		return $has_subscription;
	}
}
