<?php

add_shortcode(
	'toiee_pcast_grid',
	function ( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'ids' => '',
				'tax' => 'tlm',
			),
			$atts,
			'toiee_pcast_grid'
		);

		if ( ! isset( $atts['ids'] ) ) {
			return;
		}

		$ids = explode( ',', $atts['ids'] );
		if ( 0 === count( $ids ) ) {
			return;
		}

		return w4t_podcast_grid_display( $ids, $atts['tax'] );
	}
);


add_shortcode(
	'toiee_pcast_preview',
	function ( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'tax'  => '',
				'term' => '',

			),
			$atts,
			'toiee_pcast_preview'
		);

		global $toiee_pcast;
		$relations = $toiee_pcast->toiee_pcast_relations();

		if ( ! isset( $relations[ $atts['tax'] ] ) ) {
			return '<p>invalid tax</p>';
		}

		$tax   = $atts['tax'];
		$term  = $atts['term'];
		$ptype = $relations[ $tax ];

		if ( is_numeric( $term ) ) {
			$term_obj = get_term_by( 'id', $term, $tax, ARRAY_A );
		} else {
			$term_obj = get_term_by( 'slug', $term, $tax, ARRAY_A );
			if ( is_wp_error( $term ) ) {
				$term_obj = get_term_by( 'name', $term, $tax, ARRAY_A );
			}
		}

		if ( is_wp_error( $term ) ) {
			return '<p>not found term</p>';
		}


		// get posts form term-tax.
		$posts = get_posts(
			array(
				'post_type'      => $ptype,
				'posts_per_page' => -1,
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'tax_query'      => array(
					array(
						'taxonomy' => $tax,
						'field'    => 'term_id',
						'terms'    => $term_obj['term_id'],
					),
				),
			)
		);

		// display
		$user_logged_in              = is_user_logged_in();
		$the_episode_player_plyr_ext = 'scrum_episode';
		
		ob_start();

		global $post;
		foreach ( $posts as $post ) {
			setup_postdata( $post );

			the_title( '<h2 class="uk-h3">', '</h2>' );

			$src   = get_field( 'enclosure' );
			$media = get_field( 'media' );

			$restrict = get_field( 'restrict' );
			if ( $restrict === true ) {
				$restrict = 'restrict';
			} else if ( $restrict === false ) {
				$restrict = 'open';
			}

			switch ( $restrict ) {
				case 'open':
					the_episode_player_plyr( $src, $media, $the_episode_player_plyr_ext );
					break;
				case 'free':
					if ( $user_logged_in ) {
						the_episode_player_plyr( $src, $media, $the_episode_player_plyr_ext );
						break;
					}
				default: /* restrict */
					the_episode_player_dummy( $media );
					break;
			}
?>
			<hr>
<?php
		}
		wp_reset_postdata();

		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}
);



 /*
  *  商品プレビューを出すためのショートコード（二回め！）
  *  これは古い（Seriously Simple Podcast）のためのもの
  *
  */
add_shortcode(
	'toiee_preview_list',
	function ( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'series' => '',
				'open'   => '1-4',
				'free'   => '5-7',
			),
			$atts,
			'toiee_preview_list'
		);

		$series    = $atts['series'];
		$open_free = array(
			'open' => $atts['open'],
			'free' => $atts['free'],
		);

		// parameter check
		$no = array();
		foreach ( $open_free as $key => $value ) {
			if ( preg_match( '/^([0-9]+)-([0-9]+)$/', $value, $matches ) ) {
				$no[ $key ]['s'] = $matches[1];
				$no[ $key ]['e'] = $matches[2];
			} else {
				return '<p>invalid number of ' . $value . '. like this (1-5)</p>';
			}
		}

		// return "<pre>" .print_r($no, true). "</pre>";
		// termのチェック
		if ( $series == '' ) {
			return '<p>please set series slug or id</p>';
		}

		if ( is_numeric( $series ) ) {
			$term = get_term_by( 'id', $series, 'series', ARRAY_A );
		} else {
			$term = get_term_by( 'slug', $series, 'series', ARRAY_A );
			if ( is_wp_error( $term ) ) {
				$term = get_term_by( 'name', $series, 'series', ARRAY_A );
			}
		}

		if ( is_wp_error( $term ) ) {
			return '<p>not found series</p>';
		}

		// return "<pre>" .print_r($term, true). "</pre>";
		// post の取得
		$posts = get_posts(
			array(
				'post_type'      => 'podcast',
				'posts_per_page' => -1,
				'order'          => 'ASC',
				'post_status'    => 'publish',
				'tax_query'      => array(
					array(
						'taxonomy' => 'series',
						'field'    => 'term_id',
						'terms'    => $term['term_id'],
					),
				),
			)
		);

		// return "<pre>" .print_r( $posts ,true). "</pre>";
		global $wcr_ssp;
		global $ss_podcasting;

		// user check
		$modal = '';
		if ( is_user_logged_in() ) {
			$user_logined = true;
		} else {
			$user_logined = false;
			$modal        = $wcr_ssp->get_wc_login_form_modal();
		}

		$cnt     = 1;
		$content = '';
		foreach ( $posts as $e ) {

			if ( $no['open']['s'] <= $cnt && $cnt <= $no['open']['e'] ) {
				$status = 'open';
			} elseif ( $no['free']['s'] <= $cnt && $cnt <= $no['free']['e'] ) {
				if ( $user_logined ) {
					$status = 'open';
				} else {
					$status = 'free';
				}
			} else {
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
						 $shortcode = '[audio src="' . $enclosure . '" /]';
					} else {
						$shortcode = '[video src="' . $audio_file . '" /]';
					}

					$content .= "<h4>{$e->post_title}</h4>\n"
					. do_shortcode( $shortcode )
					// . apply_filters( 'the_content', $e->post_content );
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

			$content .= '<hr>';

			$cnt++;
		}

		return $content . $modal;

	}
);


 // ! Podcastの一覧を出力する
 // [toiee_list_series] で、ポケてらを検索して表示
 // [toiee_list_series search="^耳デミー"] で、耳デミーを検索して表示
 // 一応、num="3" とかで適当にします
add_shortcode(
	'toiee_list_series',
	function ( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'tax' => 'mdy_channel',
				'num' => 4,
			),
			$atts,
			'toiee_list_series'
		);

		$tax = $atts['tax'];
		$num = $atts['num'];

		$terms = get_terms( $tax, array( 'hide_empty=0' ) );

		if ( is_wp_error( $terms ) ) {
			return 'this is error : ' . print_r( $terms, true );
		}

		$ids = array();
		foreach ( $terms as $term ) {
			$ids[] = $term->term_id;
		}

		return w4t_podcast_grid_display( $ids, $tax );
	}
);


 // ! 商品一覧画像を出力する
 // [toiee_list_product cat="耳デミー"] で「耳デミー」の一覧を出す
 // [toiee_list_product cat="ポケてら"] で「ポケてら」の一覧を出す
add_shortcode(
	'toiee_list_product',
	function ( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'cat' => '耳デミー',
				'num' => 4,
			),
			$atts,
			'toiee_list_product'
		);

		$cat = $atts['cat'];
		$num = $atts['num'];

		$products = array();

		// TODO 一応テストする
		$term = get_term_by( 'name', $cat, 'product_cat' );

		$args           = array(
			'post_type'      => 'product',
			'orderby'        => 'title',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'id',
					'terms'    => $term->term_id,
				),
			),
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);
		$featured_query = new WP_Query( $args );

		$content = '<div class="uk-grid-small uk-child-width-1-' . $num . '@s uk-flex-left uk-text-center" uk-grid>' . "\n";
		while ( $featured_query->have_posts() ) :
			$featured_query->the_post();
			$product = new WC_Product( $featured_query->post->ID );
			// By doing this, we will be able to fetch all information related to single WooCommerce Product
			$name = $product->get_name();
			$img  = get_the_post_thumbnail_url( $product->get_id(), 'full' );
			$url  = get_permalink( $product->get_id() );

			$products[] = array(
				'name' => $name,
				'img'  => $img,
				'url'  => $url,

			);

			$content .= '<div><a href="' . $url . '" title="' . $name . '"><img src="' . $img . '" alt="' . $name . '" class="uk-display-block uk-box-shadow-small"></a></div>' . "\n";

	endwhile;
		wp_reset_query();
		$content .= '</div>';

		return $content;

	}
);


