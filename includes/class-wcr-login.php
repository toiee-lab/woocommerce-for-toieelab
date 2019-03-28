<?php
/**
 * WooCommerceの商品購入を判定して、true, false を返す
 *
 * Created by PhpStorm.
 * User: takame
 * Date: 2018/12/26
 * Time: 15:03
 */

class Toiee_WCLogin {


	public function __construct() {

		/* 問い合わせ用の url を登録する */
		add_action( 'init', array( $this, 'add_route' ) );

		/* 問い合わせ用の url が使えるようにする */
		add_filter( 'query_vars', array( $this, 'routes_query_vars' ) );

		/* rewrite rule を更新する */
		register_activation_hook( __FILE__, array( $this, 'flush_application_rewrite_rules' ) );

		/* テンプレートが選ばれる前に実行 */
		add_action( 'template_redirect', array( $this, 'woocommerce_login_check' ) );
	}

	/**
	 * 問い合わせ先の url を登録する
	 */
	public function add_route() {
		add_rewrite_rule(
			'^woocommerce_login_check/?',
			'index.php?woocommerce_login_check=woocommerce_login_check',
			'top'
		);
	}

	public function routes_query_vars( $query_vars ) {
		$query_vars[] = 'woocommerce_login_check';
		return $query_vars;
	}

	public function flush_application_rewrite_rules() {
		$this->add_route();
		flush_rewrite_rules();
	}

	public function woocommerce_login_check() {
		global $wp_query;

		// woocommerce_login_check が指定されている場合、実行する
		$control_action = isset( $wp_query->query_vars['woocommerce_login_check'] ) ? $wp_query->query_vars['woocommerce_login_check'] : '';
		if ( $control_action == 'woocommerce_login_check'
			&& isset( $_POST['user'] ) && isset( $_POST['password'] ) && isset( $_POST['product'] )
		) {

			$user_name = $_POST['user'];
			$pass      = $_POST['password'];

			// user の取得
			$field = filter_var( $user_name, FILTER_VALIDATE_EMAIL ) ? 'email' : 'login';
			$user  = get_user_by( $field, $user_name ); // 見つからない場合は false

			if ( $user == false ) { // userがいない場合
				$this->output( 'user_not_found' );
				exit;
			}

			// user のパスワードチェック
			if ( wp_check_password( $pass, $user->data->user_pass, $user->ID ) ) {

				// userのデータを作る（何がいるかなー）
				$data['user_email']       = $user->user_email;
				$data['wcrlogin_user_id'] = $user->ID;
				$data['user_login']       = $user->user_login;
				$data['user_nicename']    = $user->user_nicename;
				$data['display_name']     = $user->display_name;

				// 商品ではなく、ユーザーかチェックしているだけなら
				if ( $_POST['product'] == 'WCL_USER_CHECK' ) {
					$this->output( 'success', $data );
					exit;
				}

				global $wcr_content;
				if ( $wcr_content->check_access( explode( ',', $_POST['product'] ), $user->ID ) ) {
					$this->output( 'success', $data );
					exit;
				} else {
					$this->output( 'not_access' );
					exit;
				}
			} else {
				$this->output( 'password_not_match' );
				exit;
			}
		}
	}

	public function output( $type, $data = null ) {

		$res = array();

		switch ( $type ) {
			case 'user_not_found':
				$res['status'] = 'error';
				$res['kind']   = 'user_not_found';
				break;

			case 'password_not_match':
				$res['status'] = 'error';
				$res['kind']   = 'password_not_match';
				break;

			case 'not_access':
				$res['status'] = 'error';
				$res['kind']   = 'not_access';
				break;

			case 'success':
				$res['status'] = 'success';
				$res['data']   = $data;
				break;

			default:
				$res['status'] = 'error';
				$res['kind']   = 'unexpected_error';
		}

		header( 'content-type: application/json; charset=utf-8' );
		echo json_encode( $res );
		exit;
	}

}
