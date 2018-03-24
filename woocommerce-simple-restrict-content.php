<?php

/*
 * Plugin Name: WooCommerce Simple Restrict Content
 * Plugin URI: http://toiee.jp
 * Description: WooCommerceの商品と連動して、コンテンツの閲覧制限を設定できます
 * Author: toiee Lab
 * Version: 0.2
 * Author URI: http://toiee.jp
 */
 
 
/*  Copyright 2017 toiee Lab (email : desk@toiee.jp)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Use version 2.0 of the update checker.
require 'plugin-update-checker/plugin-update-checker.php';
$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/toiee-lab/wc-restrict/raw/master/update-metadata.json',
	__FILE__,
	'wc-restrict'
);

$wc_sr = new Woocommerce_SimpleRestrictContent();


class Woocommerce_SimpleRestrictContent
{
	private $options;

	function __construct()
	{
		// 投稿にカスタムメタボックスを設置
		add_action( 'add_meta_boxes', array($this, 'register_meta_boxes') );
		add_action( 'save_post', array($this, 'save_meta_boxes') );
		
		//shortcode の追加
		add_shortcode('wc-restrict', array($this, 'wc_restrict_shortcode'));
		add_shortcode('wc-restrict-list', array($this, 'wc_restrict_list_shortcode'));
		
		//管理画面設定
		if( is_admin() ){
	        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
			add_action( 'admin_init', array( $this, 'page_init' ) );			
		}
		
		$this->options = get_option( 'wc_src_options' );
	}
	
	
	// -----------------------------------------------------------------------------
	//
	// ! Shortcode
	//
	// -----------------------------------------------------------------------------
	function wc_restrict_shortcode( $atts, $content )
	{
		$atts = shortcode_atts( array(
			'id' => '',
			'message' => '',
		), $atts );
		extract( $atts );
		
		// 複数のidが指定されていることを想定
		$ids = explode(',', $id);

		
		//! [todo] message の取得と調整
		$not_access_message = $this->options['message'];

		// データの作成
		$product_url = get_permalink( $ids[0] );
		$prodcut_name = get_the_title( $ids[0] );
		$login_url = get_permalink( get_option('woocommerce_myaccount_page_id') );
		$modal_id = 'modal-'.$ids[0];
		
		ob_start();
		woocommerce_login_form( array('redirect'=> get_permalink()) );
		$login_form = ob_get_contents();
		ob_end_clean();
		
		// ログイン・ログアウトをチェック
		$current_user = wp_get_current_user();
		$display_none = ( $current_user->ID != 0) ? 'style="display:none;"' : '';
		
		// 表示の作成
		$not_access_message = str_replace(
			array('{{product_url}}', '{{product_name}}', '{{message}}', '{{login_url}}', '{{modal_id}}', '{{login_form}}', '{{display_none}}'),
			array($product_url, $prodcut_name, $message, $login_url, $modal_id, $login_form, $display_none),
			$not_access_message
		);
		$not_access_message = do_shortcode($not_access_message);
		
		
		// login していなければ、error
		if( $current_user->ID == 0){
			return $not_access_message;
		}
		
		// admin なら
		if( is_super_admin() )
		{
			return '<div style="border:#f99 dashed 1px"><p style="background-color:#fcc;">このコンテンツは制限付きです</p>'.do_shortcode($content).'</div>';
		}
		
		// 購入しているかチェック
		$access = false;
		foreach($ids as $i)
		{
			$access = wc_customer_bought_product( $current_user->user_email, $current_user->ID, $i );
			if($access) break;
		}

		if( $access != true ){
			return $not_access_message;
		}
		
		return do_shortcode($content);	
	}
	
	
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
		$screens = array('post', 'page');
		foreach ($screens as $screen)
		{
			add_meta_box('wc_src_product_id', 'WooCommerce Simple Restrict Content', array($this, 'display_meta_box'), $screen, 'normal' );
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
