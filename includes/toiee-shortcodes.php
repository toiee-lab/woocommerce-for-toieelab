<?php
	
 //! 商品プレビューを出すためのショートコード（二回め！）
 add_shortcode( 'toiee_preview_list' , function ( $atts, $content = null ) {
	 
	extract( 
		shortcode_atts (
			array(
				'series' => '',
				'open' => '1-4',
				'free' => '5-7'
			),
			$atts
		)
	); 
	
	// parameter check
	$no = array();
	foreach( array('open', 'free' ) as $key ) {
		if( preg_match('/^([0-9]+)-([0-9]+)$/', $$key , $matches) ) {
			$no[ $key ]['s'] = $matches[1];
			$no[ $key ]['e'] = $matches[2];
		}
		else{
			return '<p>invalid number of '.$$key.'. like this (1-5)</p>';
		}
	}
	
//	return "<pre>" .print_r($no, true). "</pre>";
	
	
	// termのチェック
	if( $series == '' ){ return "<p>please set series slug or id</p>"; }
	
	if( is_numeric( $series ) ) {
		$term = get_term_by('id', $series, 'series', ARRAY_A);
	}
	else {
		$term = get_term_by('slug', $series, 'series', ARRAY_A);
		if( is_wp_error( $term ) ){
			$term = get_term_by('name', $series, 'series', ARRAY_A);
		}
	}
	
	if( is_wp_error( $term ) ){ return "<p>not found series</p>"; }
	
//	return "<pre>" .print_r($term, true). "</pre>";
	
	// post の取得
	$posts = get_posts( array(
		'post_type' => 'podcast',
		'posts_per_page'   => -1,
		'order'     => 'ASC',
		'post_status' => 'publish',
		'tax_query' => array(
			array(
				'taxonomy' => 'series',
				'field'    => 'term_id',
				'terms'    => $term['term_id'],
			),
		)
	));
	
//	return "<pre>" .print_r( $posts ,true). "</pre>";
	
	global $wcr_ssp;
	global $ss_podcasting;
	
	
	// user check
	if( is_user_logged_in() ) {
		$user_logined = true;
	}
	else {
		$user_logined = false;
		$modal = $wcr_ssp->get_wc_login_form_modal();	
	}
	
	$cnt = 1; $content = '';
	foreach( $posts as $e ) {
		
		if( $no['open']['s'] <= $cnt && $cnt <= $no['open']['e'] ) {
			$status = 'open';
		}
		else if( $no['free']['s'] <= $cnt && $cnt <= $no['free']['e'] ) {
			if( $user_logined ){
				$status = 'open';
			}
			else {
				$status = 'free';
			}
		}
		else{
			$status = 'close';
		}
		
		// get type (audio or video)
        $episode_type = $ss_podcasting->get_episode_type( $e->ID );
                        		
		switch ( $status ) {
			
			// プレイヤーを表示
			case 'open':
								// get audio file
				$audio_file = $ss_podcasting->get_enclosure( $e->ID );
                if ( get_option( 'permalink_structure' ) ) {
                    $enclosure = $ss_podcasting->get_episode_download_link( $e->ID );
                } else {
                    $enclosure = $audio_file;
                }		
                $enclosure = apply_filters( 'ssp_feed_item_enclosure', $enclosure, $e->ID );
            
                if ( $episode_type == 'audio' ) {
					$shortcode = '[audio src="'.$enclosure.'" /]';
				}
				else {
					$shortcode = '[video src="'.$audio_file.'" /]';
				}
				
				$content .= "<h4>{$e->post_title}</h4>\n"
						. do_shortcode( $shortcode )
//						. apply_filters( 'the_content', $e->post_content );
						. $e->post_content;
			
				break;
				
			
			
			
			// ダミーを表示
			case 'free':
				$content .= "<h4>{$e->post_title}</h4>\n"
						. $wcr_ssp->get_dummy_player( $episode_type )
						. '<p style="font-size:0.8rem;">無料登録(あるいはログイン)することで、ご覧いただけます。<br>
						<a href="#" uk-toggle="target: #modal_login_form">無料登録する or ログインする場合は、こちらをクリック</a></p>';
			
				break;
				
			default:
				$content .= "<p>{$e->post_title}</p>\n";
		}
		
		$content .= "<hr>";

		$cnt++;
	}
	
	return $content.$modal;
	
} );
 
 
 
 
 //! Podcastの一覧を出力する
 // [toiee_list_series] で、ポケてらを検索して表示
 // [toiee_list_series search="^耳デミー"] で、耳デミーを検索して表示
 // 一応、num="3" とかで適当にします
 add_shortcode( 'toiee_list_series', function ( $atts, $content = null ) {
	 
	extract( 
		shortcode_atts (
			array(
				'search' => '^ポケてら',
				'num' => 4
			),
			$atts
		)
	);
	 
	$terms = get_terms( 'series', array( 'hide_empty=0' ) );
	
	if( is_wp_error( $terms ) ){
		return 'this is error : '. print_r($terms , true);
	}
	
	//マッチするものだけ残す
	$terms = array_filter( $terms, function( $term ) use( $search ) { return preg_match( "/{$search}/", $term->name ); }  );
	
	$terms_a = array();
	$content = '<div class="uk-grid-small uk-child-width-1-'.$num.'@s uk-flex-left uk-text-center" uk-grid>'."\n";
	foreach( $terms as $k=>$term ){
		
		$name = $term->name;
		$plink = get_term_link( $term->term_id, 'series' );
		$series_image = get_option( 'ss_podcasting_data_image_' . $term->term_id, 'no-image' );
		$terms_a[ ] = array(
			'name' => $name,
			'link' => $plink,
			'img'  => $series_image,
		);
		
		$content .= '<div><a href="'.$plink.'" title="'.$name.'" class="uk-display-block uk-box-shadow-small"><img src="'.$series_image.'" alt="'.$name.'"></a></div>'."\n";
		
	}
	$content .= '</div>';
	
	return  $content;
	 
 } );
 
 
 //! 商品一覧画像を出力する
 // [toiee_list_product cat="耳デミー"] で「耳デミー」の一覧を出す
 // [toiee_list_product cat="ポケてら"] で「ポケてら」の一覧を出す
 add_shortcode( 'toiee_list_product', function ( $atts, $content = null ) {
	extract( 
		shortcode_atts (
			array(
				'cat' => '耳デミー',
				'num' => 4
			),
			$atts
		)
	);	
	
	$products = array();
	
	$$term = get_term_by('name', $cat, 'product_cat' );
	
	$args = array(
		'post_type' => 'product',
		'orderby'   => 'title',
		'tax_query' => array(
			array(
				'taxonomy'  => 'product_cat',
				'field'     => 'id',
				'terms'     => $$term->term_id
			),
		),
		'posts_per_page' => -1,
		'post_status' => 'publish'
	);
	$featured_query = new WP_Query( $args );
	
	$content = '<div class="uk-grid-small uk-child-width-1-'.$num.'@s uk-flex-left uk-text-center" uk-grid>'."\n";	
	while ($featured_query->have_posts()) :
		$featured_query->the_post();
		$product = get_product( $featured_query->post->ID );
		// By doing this, we will be able to fetch all information related to single WooCommerce Product
		
		$name = $product->get_name();
		$img  = get_the_post_thumbnail_url( $product->get_id(), 'full' );
		$url  = get_permalink( $product->get_id() );
		
		$products[] = array(
			'name' => $name,
			'img' => $img,
			'url' => $url,
			
		);
		
		$content .= '<div><a href="'.$url.'" title="'.$name.'"><img src="'.$img.'" alt="'.$name.'" class="uk-display-block uk-box-shadow-small"></a></div>'."\n";
		
	endwhile;
	wp_reset_query();
	$content .= '</div>';
	
	return $content;
	 
 } );	
 
 