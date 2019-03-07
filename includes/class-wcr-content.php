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

		//shortcode の追加
		add_shortcode( 'wcr-content' , array( $this, 'wcr_content_shortdode') );
		add_shortcode( 'wcr-content-free' , array( $this, 'wcr_content_free_shortdode') );
		
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
				),
				'capability_type' => 'product',
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



EOD;


	}

	// -----------------------------------------------------------------------------
	//
	// ! Shortcode
	//
	// -----------------------------------------------------------------------------

    function wcr_content_free_shortdode( $atts, $content ) {
	    $atts = shortcode_atts( array(
	            'title' => '会員限定',
	            'message'  => 'このコンテンツをご覧になるには、会員ログインが必要です。',
        ), $atts);

	    $content = do_shortcode( $content );

	    if( is_user_logged_in()  ){
	        if( is_super_admin() ){
		        return '<div style="border:#f99 dashed 1px"><p style="background-color:#fcc;">このコンテンツは制限付きです</p>'.$content.'</div>';
            }
	        else{
		        return $content;
            }
	    }
	    else {

	        $html = get_popup_login_form();

            //表示するもの
            $html .= <<<EOD
    <div class="uk-inline uk-padding-small uk-width-1-1">
    
        {$content}
    
        <div class="uk-overlay-primary uk-position-cover"></div>
        <div class="uk-overlay uk-position-top uk-light">
            <h3>{$atts['title']}</h3>
            <p>{$atts['message']}</p>
            <p><button class="uk-button uk-button-primary" uk-toggle="target: #modal_login_form">ログインする</button></p>
            <p>会員登録は、無料です</p>
        </div>    
    </div>
EOD;

            return $html;
        }
    }
	
	function wcr_content_shortdode( $atts, $content ) {
		$atts = shortcode_atts( array(
			'ids' => '',
			'offer' => '',
			'message' => '',
			'show_to_not_grantee_mode' => false,
			'show_to_grantee_mode' => false,
		), $atts );

		$ids     = $atts[ 'ids' ];
		$offer   = $atts[ 'offer' ];
		$message = $atts[ 'message' ];
		$show_to_not_grantee_mode = $atts[ 'show_to_not_grantee_mode' ];
		$show_to_grantee_mode = $atts[ 'show_to_grantee_mode' ];
		
		$ids = explode(',', $ids);
		if( $offer == '' ) {
			$offer = $this->get_offer_product_id( $ids );
		}

		// admin の場合は制限せず、表示する。ただし、制限コンテンツの範囲を示す
		if( is_super_admin() ) {
			return '<div style="border:#f99 dashed 1px"><p style="background-color:#fcc;">このコンテンツは制限付きです</p>'.do_shortcode($content).'</div>';
		}
		// アクセスOKの場合、表示する
        $access = $this->check_access( $ids );
		if( $access ) {
			return do_shortcode( $content );
		}
		
		// ----------------------------------------
		// アクセス制限時のメッセージボックスの作成
		// ----------------------------------------

		// message の取得と調整
		$not_access_message = $this->options['message'];

		// データの作成
		$product_url = get_permalink( $offer );
		$product_name = get_the_title( $offer );
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
			array($product_url, $product_name, $message, $login_url, $modal_id, $login_form, $display_none),
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

		return $not_access_message;
	}

	/**
	* 現在のユーザーで、アクセスをチェックします。様々なプロダクトが混ざった状態で動作する設計です
	* wcrestrict、product、
	*/
	public function check_access( $ids , $user_id = '') {

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
					$ret = $this->check_access( $wcr_ids, $user_id);

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
					if( function_exists('wcs_user_has_subscription') &&
                        ($product_type == 'subscription' || $product_type == 'variable-subscription' ) ) {
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
	           
            <form method="post" action="<?php admin_url( 'options.php' ); ?>">
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
//        $new_input = array();
//
//        if( isset( $input['message'] ) )
//            $new_input['message'] = wp_kses_post( $input['message'] );
//
//        return $new_input;
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

<p><b>ショートコード例:</b><br></p>
<pre style="border: 1px solid #999;padding:0.5em;">[wc-restrict id="111,222" message="無料でご利用いただけます"]
ここにコンテンツ
[/wc-restrict]
</pre>		


<?php
	}
}

	