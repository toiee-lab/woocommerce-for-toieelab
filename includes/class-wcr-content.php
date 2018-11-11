<?php

/**
 * WooCommerce の 認証を行うクラス
 */
class Woocommerce_SimpleRestrictContent
{
	private $options;
	public $plugin_url;

	function __construct()
	{
		//カスタム投稿タイプの設定
		add_action('init',  array( $this, 'create_post_type') );
		add_action( 'add_meta_boxes', array($this, 'add_meta_boxes_wcr') );
		add_action( 'save_post', array($this, 'save_post_wcr') );
		
		// 投稿にカスタムメタボックスを設置
//		add_action( 'add_meta_boxes', array($this, 'register_meta_boxes') );
//		add_action( 'save_post', array($this, 'save_meta_boxes') );
		
		//shortcode の追加（以下は非推奨）
		add_shortcode('wc-restrict', array($this, 'wc_restrict_shortcode'));
		add_shortcode('wc-restrict-list', array($this, 'wc_restrict_list_shortcode'));
		
		//shortcode の追加
		add_shortcode( 'wcr-content' , array( $this, 'wcr_content_shortdode') );
		
		//管理画面設定
		if( is_admin() ){
	        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'page_init' ) );			
		}
		
		$this->options = get_option( 'wc_src_options' );
	}
	
	// -----------------------------------------------------------------------------
	//
	// ! Create post type for WC Restriction
	//
	// -----------------------------------------------------------------------------
	function create_post_type()
	{
		register_post_type(
			'wcrestrict',
			array(
				'label' 				=> '商品まとめ',
				'public'				=> false,
				'exclude_from_search'	=> false,
				'show_ui'				=> true,
				'show_in_menu'			=> true,
				'menu_position'			=> 5,
				'menu_icon'             => 'dashicons-portfolio',
				'hierarchical'			=> false,
				'has_archive'			=> false,
				'supports'				=> array(
					'title',
					'editor',
				)
			)
		);
	}
	
	// 登録情報を表示するための meta box の表示
	function add_meta_boxes_wcr(){
		add_meta_box(
			'wc_restrict',
			'ショートコード例', 
			array($this, 'display_wcr_meta_box'),
			'wcrestrict',
			'advanced' 
		);
	}
	function display_wcr_meta_box( $post ){
		$id = get_the_ID();
		
		$wc_param = array('wcr_product_ids'=>'', 'wcr_sub_ids'=>'', 'wcr_mem_ids'=>'');

		$wc_param_data = get_post_meta($id, 'wcr_param', true);
		
		if( $wc_param_data != '' )
		{
			$wc_param_arr = unserialize( $wc_param_data );
			
			foreach($wc_param_arr as $key => $v)
			{
				$wc_param[$key] = implode(',', $v);
			}
		}
		
		wp_nonce_field( 'wcr_meta_box', 'wcr_meta_box_nonce' );
		
		echo <<<EOD
<p>閲覧制限のためのショートコード</p>

<pre id="s_code" style="height:6em;overflow:scroll;background-color:#eee;border:1px solid #999;padding:1em;">
[wcr-content ids="{$id}"]

here is contents

[/wcr-content]
</pre>

<br>
<br>

<hr>

<p><b>※ 以下の設定は、下位互換のためのものです。今後は、利用しないようにお願いします</b></p>		
<p><b>閲覧制限</b></p>
<p>許可するWooCommercプロダクト、メンバーシップ、サブスクリプションIDを設定を、コンマ区切りで複数記入できます。</p>
<table>
	<tr>
		<th><label>Product IDs</label></th>
		<td><input type="text" name="wcr_product_ids" value="{$wc_param['wcr_product_ids']}" /></td>
	</tr>
	<tr>
		<th><label>Subscription IDs</label></th>
		<td><input type="text" name="wcr_sub_ids" value="{$wc_param['wcr_sub_ids']}" /></td>
	</tr>
	<tr>
		<th><label>Membership IDs</label></th>
		<td><input type="text" name="wcr_mem_ids" value="{$wc_param['wcr_mem_ids']}" /></td>
	</tr>
</table>

EOD;


	}
	
	
	function save_post_wcr( $post_id ){
		
    	// Check if our nonce is set.
        if ( ! isset( $_POST['wcr_meta_box_nonce'] ) ) {
            return $post_id;
        }
        $nonce = $_POST['wcr_meta_box_nonce'];
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'wcr_meta_box' ) ) {
            return $post_id;
        }
        
        		$wc_param = array();
		foreach( array('product', 'sub', 'mem') as $name )
		{
			$vname = 'wcr_'.$name.'_ids';
			if( isset( $_POST[$vname]) )
			{
				$wc_param[$vname] = explode(',', $_POST[$vname]);
			}
		}
		update_post_meta( $post_id, 'wcr_param', serialize($wc_param) );
	}
	


	
	
	// -----------------------------------------------------------------------------
	//
	// ! Shortcode
	//
	// -----------------------------------------------------------------------------
	
	
	function wcr_content_shortdode( $atts, $content ) {
		$atts = shortcode_atts( array(
			'ids' => '',
			'offer' => '',
			'message' => '',
			'show_to_not_grantee_mode' => false,
			'show_to_grantee_mode' => false,
		), $atts );
		extract( $atts );
		
		
		$ids = explode(',', $ids);
		if( $offer == '' ) {
			$offer = $this->get_offer_product_id( $ids );
		}
				
		
		// ----------------------------------------
		// アクセス制限時のメッセージボックスの作成
		// ----------------------------------------		
		
		// message の取得と調整
		$not_access_message = $this->options['message'];

		// message の取得と調整
		$not_access_message = $this->options['message'];

		// データの作成
		$product_url = get_permalink( $offer );
		$prodcut_name = get_the_title( $offer );
		$login_url = get_permalink( get_option('woocommerce_myaccount_page_id') );
		$modal_id = 'modal-'.$offer;

		// error message がある場合、モーダルウィンドウを表示する（ための準備）
		ob_start();
		if( function_exists( 'wc_print_notices' ) ) {  // Gutenbergとの兼ね合いで、不意に呼び出される
			wc_print_notices();
		}
		$wc_notices = ob_get_contents();
		ob_end_clean();
		
		if( $wc_notices != ''){
			$js = <<<EOD
<script>			
el = document.getElementById('{$modal_id}');
UIkit.modal(el).show();
</script>
EOD;
		}
		else{
			$js = '';
		}
		
		// ログインフォームの取得
		ob_start();
		echo $wc_notices;
		woocommerce_login_form( array('redirect'=> get_permalink()) );
		echo $js;
		$login_form = ob_get_contents();
		ob_end_clean();		
		
		// アクセス制限時のメッセージを作成
		$current_user = wp_get_current_user();
		$display_none = ( $current_user->ID != 0 ) ? 'style="display:none;"' : '';
		
		$not_access_message = str_replace(
			array('{{product_url}}', '{{product_name}}', '{{message}}', '{{login_url}}', '{{modal_id}}', '{{login_form}}', '{{display_none}}'),
			array($product_url, $prodcut_name, $message, $login_url, $modal_id, $login_form, $display_none),
			$not_access_message
		);
		$not_access_message = do_shortcode($not_access_message);  //ショートコードを適用する
		
		// show_to_not_grantee_mode = true の場合、not_access_message は、コンテンツ部分を使い、$content は null とする
		if( $show_to_not_grantee_mode )
		{
			$not_access_message = do_shortcode($content);
			$content = '';
		}
		
		// show_to_grantee_mode = true の場合、not_access_message は null、$content を表示する
		if( $show_to_grantee_mode )
		{
			$not_access_message = '';
		}

		// --------------------------------------------------------
		// Start Restrict Check
		// --------------------------------------------------------
		
		// ユーザーとして、ログインしているかチェック（ログインしていなければ、$not_access_message を表示）
		if( $current_user->ID == 0){
			return $not_access_message;
		}
		
		// admin の場合は制限せず、表示する。ただし、制限コンテンツの範囲を示す
		if( is_super_admin() )
		{
			return '<div style="border:#f99 dashed 1px"><p style="background-color:#fcc;">このコンテンツは制限付きです</p>'.do_shortcode($content).'</div>';
		}
		
		if( $this->check_access( $ids ) ) {
			return do_shortcode( $content );
		}
		
		return $not_access_message;
	}
	
	
	// 非推奨
	function wc_restrict_shortcode( $atts, $content )
	{
		$atts = shortcode_atts( array(
			'id' => '',
			'sub_id' => '',
			'mem_id' => '',
			'wcr_id' => '',
			'message' => '',
			'show_to_not_grantee_mode' => false,
			'show_to_grantee_mode' => false,
		), $atts );
		extract( $atts );
		
		// ----------------------------------------
		// アクセス制限パラメータの取得 
		// ----------------------------------------
		
		// 複数のidが指定されていることを想定
		$product_ids = explode(',', $id);
		$sub_ids = explode(',', $sub_id);
		$mem_ids = explode(',', $mem_id);

		// WC Restrict Post type からデータを取り出して、$product_ids, $sub_ids, $mem_ids に加える
		if( $wcr_id != '' && is_numeric($wcr_id) ){
			$wcr_dat  = get_post_meta($wcr_id, 'wcr_param', true);
			$wcr_arr = unserialize( $wcr_dat );
			
			$tmp_arr = array( 'product_ids', 'sub_ids', 'mem_ids' );
			foreach($tmp_arr as $v){
				$$v = array_merge($$v, $wcr_arr['wcr_'.$v] );
			}
		}
		
		// ----------------------------------------
		// アクセス制限時のメッセージボックスの作成
		// ----------------------------------------		
		
		// message の取得と調整
		$not_access_message = $this->options['message'];

		// データの作成
		$product_url = get_permalink( $product_ids[0] );
		$prodcut_name = get_the_title( $product_ids[0] );
		$login_url = get_permalink( get_option('woocommerce_myaccount_page_id') );
		$modal_id = 'modal-'.$product_ids[0];

		// error message がある場合、モーダルウィンドウを表示する（ための準備）
		ob_start();
		wc_print_notices();
		$wc_notices = ob_get_contents();
		ob_end_clean();
		
		if( $wc_notices != ''){
			$js = <<<EOD
<script>			
el = document.getElementById('{$modal_id}');
UIkit.modal(el).show();
</script>
EOD;
		}
		else{
			$js = '';
		}
		
		// ログインフォームの取得
		ob_start();
		echo $wc_notices;
		woocommerce_login_form( array('redirect'=> get_permalink()) );
		echo $js;
		$login_form = ob_get_contents();
		ob_end_clean();		
		
		// アクセス制限時のメッセージを作成
		$current_user = wp_get_current_user();
		$display_none = ( $current_user->ID != 0 ) ? 'style="display:none;"' : '';
		
		$not_access_message = str_replace(
			array('{{product_url}}', '{{product_name}}', '{{message}}', '{{login_url}}', '{{modal_id}}', '{{login_form}}', '{{display_none}}'),
			array($product_url, $prodcut_name, $message, $login_url, $modal_id, $login_form, $display_none),
			$not_access_message
		);
		$not_access_message = do_shortcode($not_access_message);  //ショートコードを適用する
		
		// show_to_not_grantee_mode = true の場合、not_access_message は、コンテンツ部分を使い、$content は null とする
		if( $show_to_not_grantee_mode )
		{
			$not_access_message = do_shortcode($content);
			$content = '';
		}
		
		// show_to_grantee_mode = true の場合、not_access_message は null、$content を表示する
		if( $show_to_grantee_mode )
		{
			$not_access_message = '';
		}
		
		// --------------------------------------------------------
		// Start Restrict Check
		// --------------------------------------------------------
		
		// ユーザーとして、ログインしているかチェック（ログインしていなければ、$not_access_message を表示）
		if( $current_user->ID == 0){
			return $not_access_message;
		}
		
		// admin の場合は制限せず、表示する。ただし、制限コンテンツの範囲を示す
		if( is_super_admin() )
		{
			return '<div style="border:#f99 dashed 1px"><p style="background-color:#fcc;">このコンテンツは制限付きです</p>'.do_shortcode($content).'</div>';
		}
		
		if( $this->has_access( $product_ids, $sub_ids, $mem_ids ) ){
			return do_shortcode( $content );
		}
		
		
		return $not_access_message;
	}

	/**
	* 現在のユーザーで、アクセスをチェックします。様々なプロダクトが混ざった状態で動作する設計です
	* wcrestrict、product、
	*/
	function check_access( $ids , $user_id = '') {

		// user check
		if($user_id == '') {
			if( is_user_logged_in() ){
				$user = wp_get_current_user();
			}
			else{
				return false;
			}
		}
		else{
			$user = get_userdata( $user_id );
			if( $user == FALSE ) {
				return false;
			}
		}
		
		$user_id    = $user->ID;
		$user_email = $user->user_email;
		
		$ret = false;
		
		foreach( $ids as $i ) {
			$post_type =  get_post_type( $i );
			
			switch( $post_type ) {
				
				case 'wcrestrict' :    //商品まとめなので、再帰的な呼び出し
					$wcr_ids = get_field( 'wcr_product_ids', $i);
					$ret = $this->check_access( $wcr_ids );

					break;
														
				case 'wc_membership_plan': //WooCommerce メンバーシップ
					if ( function_exists( 'wc_memberships' ) ) {
						$ret = ($i != '') ? wc_memberships_is_user_active_member(  $user_id, $i ) : false;
					}
					
					break;
					
				case 'product':  //その他は商品としてチェックする
				default:
					$product = wc_get_product( $i );
					$product_type = $product->get_type();
					
					// subscription
					if( $product_type == 'subscription' && function_exists('wcs_user_has_subscription') ) {
						$ret = ($i != '') ? wcs_user_has_subscription( $user_id, $i, 'active') : false;
					}
					else {  // 今の所、 product_varidation ぐらいか？						
						$ret = wc_customer_bought_product( $user_email, $user_id, $i );
					}
			}
			
			if( $ret ) return true;
		}
		
		return $ret;
	}
	
	// ごちゃ混ぜのID(wc restrict 含む）から、最初のプロダクトを提供する
	function get_offer_product_id( $ids ) {
		
		foreach( $ids as $i ) {
			$post_type = get_post_type( $i );
			
			if( $post_type == 'product' ) {
				return $i;
			}
			else if( $post_type == 'wcrestrict' ) {
				$wcr_ids = get_field( 'wcr_product_ids', $i);
				$ret = $this->get_offer_product_id( $wcr_ids );
				
				if( $ret != false ) {
					return $ret;
				}
			}
		}
		
		return false;
	}

	
	/**
	* 現在のユーザーで、アクセス権チェック（このメソッドは非推奨です。古いモデルに基づきます）
	*/
	function has_access($product_ids, $sub_ids, $mem_ids, $user_id='' ){
		
			
		if($user_id == '') {
			if( is_user_logged_in() ){
				$user = wp_get_current_user();
			}
			else{
				return false;
			}
		}
		else{
			$user = get_userdata( $user_id );
			if( $user == FALSE ) {
				return false;
			}
		}		
	
		$user_id    = $user->ID;
		$user_email = $user->user_email; 
		
		$access = false;
		foreach($product_ids as $i)
		{
			$access = wc_customer_bought_product( $user_email, $user_id, $i );
			if($access){
				return true;
			}
		}

		// Subscription でチェックをする 
		if ( function_exists('wcs_user_has_subscription') )
		{
			foreach( $sub_ids as $i )
			{
				$access = ($i != '') ? wcs_user_has_subscription( $user_id, $i, 'active') : false;
				if( $access ){
					return true;
				}
			}
		}
		
		// Membership でチェックする
		if ( function_exists( 'wc_memberships' ) ) {

			$access = false;
			foreach( $mem_ids as $i )
			{
				$access = ($i != '') ? wc_memberships_is_user_active_member(  $user_id, $i ) : false;
				if( $access ){
					return true;
				}
			}
		}
		
		return false;

	}
	
	/*
		このメソッドは使わない、廃止予定
	*/
	function wc_restrict_list_shortcode( $atts, $content ){
		$atts = shortcode_atts( array(
			'cat' => '',
		), $atts );
		extract( $atts );
		
		$current_user = wp_get_current_user();
		if( $current_user->ID == 0 ){
			return '<p>ログインしてください</p>';
		}
		
		// 商品一覧を取得（特定のカテゴリで）
		$args = array(
			'limit' => -1,
			'category' => array( $cat ),
		);
		$products = wc_get_products($args);
		
//		return '<pre>'.print_r($products, true).'</pre>';
		
		// ループして、買っているかチェックする
		$bought_list = array();
		foreach($products as $p)
		{
			if( wc_customer_bought_product( $current_user->user_email, $current_user->ID, $p->id ) )
			{
				$page = get_posts(
					array( 'post_type'=>array('post', 'page'), 
					'meta_key'=> 'wc_src_product_id',
					'meta_value'=> $p->id)
				);
				
				if( isset($page[0]) )
				{
					$bought_list[] = array(
						'url' => get_permalink( $page[0]->ID ),
						'title' => $page[0]->post_title,
					);
				}
//				return '<pre>'.print_r($page, true).'</pre>';
			}
		}
		
//		return '<pre>'.print_r($bought_list, true).'</pre>';
				
		// あったら一覧として表示する
		$li = '';
		foreach($bought_list as $v)
		{
			$li .= '<li><a href="'.$v['url'].'">'.$v['title'].'</a></li>'."\n";
		}
		
		if($li == ''){
			$li = '<li>ありません</li>';
		}
		
		return "<ul>\n".$li."</ul>\n";
	}
	
	// -----------------------------------------------------------------------------
	//
	// ! meta box setting
	//
	// -----------------------------------------------------------------------------
	function register_meta_boxes()
	{
		$screens = array('post', 'page', 'podcast');
		foreach ($screens as $screen)
		{
			add_meta_box('wc_src_product_id', 'WooCommerce Simple Restrict Content', array($this, 'display_meta_box'), $screen, 'side' );
		}

	}
	function display_meta_box( $post )
	{	
		//関連する WooCommerce の Product ID を取得
		$id = get_the_ID();
		$wc_src_product_id = get_post_meta($id, 'wc_src_product_id', true);
				
		// woocommerce の product を読み込んで、一覧を作って出力する
		wp_nonce_field( 'wc_src_product_id_meta_box', 'wc_src_product_id_meta_box_nonce' );
		
		$list = $this->get_woocommerce_product_list();
		
		$current_setting = '関連づけなし';

		$html = '<select name="wc_src_product_id" id="wc_src_select" onChange="change_code();">'."\n";
		$html .= '    <option value="">関連づけなし</option>'."\n";
		
		$code = '<div id="code_0">none</div>';
		$current_code = 'none';
		foreach($list as $k=>$v)
		{
			$tmp_code = htmlspecialchars( $this->get_shortcode( $v ), ENT_HTML5 );
			$code .= '<div id="code_'.($k+1).'">'.$tmp_code.'</div>'."\n";

			if( $v['ID'] == $wc_src_product_id )
			{
				$selected = ' selected';
				$current_setting = $v['post_title']." (ID: {$v['ID']})\n";
				$current_code = $tmp_code;
				$add_txt = '【現在の設定】 ';
			}
			else{
				$selected = '';
				$add_txt = '';				
			}
			
			$html .= '    <option value="'.$v['ID'].'"'.$selected.'>'.$add_txt.$v['post_title'].'[ID: '.$v['ID'].']</option>'."\n";
		}
		$html .= '</select>';
		
		
		echo <<<EOD
<p>このコンテンツと関連づける商品を選択して下さい。</span><br>
</p>
{$html}
<div>
<pre id="s_code" style="height:10em;overflow:scroll;background-color:#eee;border:1px solid #999;padding:1em;">
{$current_code}
</pre>
<p style="font-size:small;color:#999">バリエーション商品の場合のルール : 親プロダクトIDを指定すると、子プロダクト(バリエーション)購入者全員が閲覧できます。<br>
複数のIDを指定する: id="xxxx,yyyy" と書くことで「いずれか」の商品を持つユーザーならアクセスできるようになります</p>
</div>
<div style="display:none;">
{$code}
</div>
<script>
function change_code(){
	sel = document.getElementById('wc_src_select');
	index = sel.selectedIndex;
	
	code = document.getElementById('code_'+index);
	txt  = document.getElementById('s_code');
	
	txt.innerHTML = code.innerHTML;

}
</script>
EOD;
		$wcr_posts = get_posts( array('post_type'=>'wcrestrict') );
		$code = '';
		foreach( $wcr_posts as $p ){
			$code .= <<<EOD

[wc-restrict wcr_id="{$p->ID}" ]
{$p->post_title}
[/wc-restrict]
EOD;
			$code .= "\n";
		}
		
		echo <<<EOD
<p>WC Restrict で設定したデータに基づいて制限をかける場合は、以下を使ってください。</p>		
<pre style="height:10em;overflow:scroll;background-color:#eee;border:1px solid #999;padding:1em;">
{$code}
</pre>		
EOD;

	}
	function save_meta_boxes($post_id)
	{
        // Check if our nonce is set.
        if ( ! isset( $_POST['wc_src_product_id_meta_box_nonce'] ) ) {
            return $post_id;
        }
 
        $nonce = $_POST['wc_src_product_id_meta_box_nonce'];
 
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, 'wc_src_product_id_meta_box' ) ) {
            return $post_id;
        }
		
		$exclude_menu = isset($_POST['wc_src_product_id']) ? $_POST['wc_src_product_id'] : null;
		$before = get_post_meta($post_id, 'wc_src_product_id', true);		
		if($exclude_menu)
		{
			update_post_meta($post_id, 'wc_src_product_id', $exclude_menu);
		}
		else
		{
			delete_post_meta($post_id, 'wc_src_product_id', $before);
		}
	}
	
	function get_woocommerce_product_list() {
		$prodct_list = array();

		$loop = new WP_Query( array( 'post_type' => array('product', 'product_variation'), 'posts_per_page' => -1 ) );
		
		while ( $loop->have_posts() ) : $loop->the_post();
			$theid = get_the_ID();
			
			$prodct_list[ $theid ] = array(
				'ID' => $theid,
				'post_title' => get_the_title(),
				'post_type'  => get_post_type(),
				'post_parent' => wp_get_post_parent_id( $theid )
			);
		
	    endwhile; wp_reset_query();
	    
	    // product タイプのみを選び出し、product_variation は、子要素に移動する
	    foreach($prodct_list as $key => $v)
	    {
		    if( $v['post_type'] == 'product_variation' )
		    {
			    $parent = $v['post_parent'];
			    if( !isset( $prodct_list[ $parent ]['child'] ) ){
			    	$prodct_list[ $parent ]['child'] = array();
			    }
			    
			    $prodct_list[ $parent ]['child'][$key] = $v;
			    unset( $prodct_list[$key] );   
		    }
	    }

	    usort($prodct_list, function($a, $b){
		    return strnatcmp($a['post_title'], $b['post_title']);
	    });
	    
	    return $prodct_list;
	}
	function get_shortcode($arr)
	{
		$code = '<!-- xxx -->
[wc-restrict id="yyy" message="zzz"]
here is contents
[/wc-restrict]
';

		$ret = str_replace(array('xxx', 'yyy', 'zzz'), array($arr['post_title'],$arr['ID'], $arr['post_title']), $code);
		$ret .= "\n";
		if( isset( $arr['child'] ) )
		{
			foreach( $arr['child'] as $v )
			{
				$ret .= str_replace(array('xxx', 'yyy', 'zzz'), array($v['post_title'], $v['ID'], $v['post_title']), $code);
				$ret .= "\n";
			}
		}
		return $ret;
	}
	
	// -----------------------------------------------------------------------------
	//
	// ! Admin settings
	//
	// -----------------------------------------------------------------------------
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'WC Simple Restrict設定', 
            'WC Simple Restrict', 
            'manage_options', 
            'wc-src-admin', 
            array( $this, 'create_admin_page' )
        );
    }
    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'wc_src_options' );
        ?>
        <div class="wrap">

            <h2>WooCommerce Simple Restrict Content設定</h2>           
            <p>コンテンツ閲覧制限メッセージを設定します</p>
	           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'wc_src_group' );   
                do_settings_sections( 'wc-src-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }
    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'wc_src_group', // Option group
            'wc_src_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
        add_settings_section(
            'setting_section_id', // ID
            '閲覧制限メッセージ', // Title
            array( $this, 'print_section_info' ), // Callback
            'wc-src-setting-admin' // Page
        );  
        add_settings_field(
            'message', // ID
            'メッセージ', // Title 
            array( $this, 'message_callback' ), // Callback
            'wc-src-setting-admin', // Page
            'setting_section_id' // Section           
        );
    }
    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
	    return $input;
	    // サニタイズしない
        $new_input = array();

        if( isset( $input['message'] ) )
            $new_input['message'] = wp_kses_post( $input['message'] );

        return $new_input;
    }
    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print '以下に設定を指定し、変更を保存をクリックしてください。';
    }
    /** 
     * Get the settings option array and print one of its values
     */
    public function message_callback()
    {
   	    $text = isset( $this->options['message'] ) ? $this->options['message'] : '';
?>

<textarea name="wc_src_options[message]" style="width:100%;height: 20em;">
<?php echo $text; ?>
</textarea>
<p>説明<p>
<ul>
	<li>{{product_url}} は、商品ページのurlに置き換わります(idで先頭に指定されているものを利用)</li>
	<li>{{product_name}} は、商品の名前に置き換わります(idで先頭に指定されている商品の名前)</li>
	<li>{{login_url}} は、アカウントページへのリンクを表示します</li>
	<li>{{modal_id}} は、 modal-(product.ID) に置き換わります</li>
	<li>{{display_none}} は、ログイン済みなら style="display:none;" が挿入されます</li>
	<li>{{login_form}} は、このページにリダイレクトされるログインフォームを表示します( woocommerce_login_form() を利用 )</li>
	<li>{{message}} は、ショートコードで指定したメッセージに置き換えます</li>
</ul>

<p><b>ショートコード例:</b><br>
<pre style="border: 1px solid #999;padding:0.5em;">[wc-restrict id="111,222" message="無料でご利用いただけます"]
ここにコンテンツ
[/wc-restrict]
</pre>		
</p>

<?php
	}
}

	