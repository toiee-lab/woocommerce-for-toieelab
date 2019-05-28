<?php

/**
 * WooCommerce の 認証を行うクラス
 */
class Toiee_SimpleEvent {

	public $tabs;

	function __construct() {
		// カスタム投稿タイプの設定
		add_action( 'init', array( $this, 'create_post_type' ) );

		// 作成者を出す
		add_action( 'admin_menu', array( $this, 'add_custom_box' ) );
		add_filter( 'manage_toiee-event_posts_columns', array( $this, 'manage_columns' ) );
		add_action( 'manage_toiee-event_posts_custom_column', array( $this, 'add_column' ), 10, 2 );

		add_shortcode( 'toiee_show_event_tabel', array( $this, 'shortcode__toiee_show_event_tabel' ) );

		$this->add_acf();

	}

	public function add_acf() {
		// ! イベント管理システム
		if ( function_exists( 'acf_add_local_field_group' ) ) :

			acf_add_local_field_group(
				array(
					'key'                   => 'group_5bed095de7705',
					'title'                 => 'toiee イベント情報',
					'fields'                => array(
						array(
							'key'               => 'field_5bed0c04a9bd7',
							'label'             => '簡単な説明',
							'name'              => 'ts_event_description',
							'type'              => 'text',
							'instructions'      => '内容、ターゲット、期待できる結果を端的に書いてください',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => 'WordPress初心者向けに、中身や仕組みを深く探求します',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => 256,
						),
						array(
							'key'               => 'field_5bed0973971ec',
							'label'             => '日程',
							'name'              => 'ts_event_date',
							'type'              => 'date_time_picker',
							'instructions'      => '開始日を入力してください',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'display_format'    => 'Y/m/d',
							'return_format'     => 'Y/m/d',
							'first_day'         => 1,
						),
						array(
							'key'               => 'field_5bed0a05971ed',
							'label'             => '日程追加',
							'name'              => 'ts_event_date_add',
							'type'              => 'text',
							'instructions'      => '15:00 - 18:00 や、3日間集中ですなど追加情報を入力してください',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => '18:00 - 21:00',
							'prepend'           => '',
							'append'            => '',
							'maxlength'         => 32,
						),
						array(
							'key'               => 'field_5bed0b3b971ef',
							'label'             => 'イベントページ',
							'name'              => 'ts_event_url',
							'type'              => 'url',
							'instructions'      => 'イベントページのURLを入れてください(Peatixなど)',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'default_value'     => '',
							'placeholder'       => 'https://',
						),
						array(
							'key'               => 'field_5bed1007bd387',
							'label'             => '開催場所',
							'name'              => 'ts_event_location',
							'type'              => 'select',
							'instructions'      => '',
							'required'          => 1,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'choices'           => array(
								'オンライン' => 'オンライン',
								'北海道'   => '北海道',
								'青森県'   => '青森県',
								'岩手県'   => '岩手県',
								'宮城県'   => '宮城県',
								'秋田県'   => '秋田県',
								'山形県'   => '山形県',
								'福島県'   => '福島県',
								'茨城県'   => '茨城県',
								'栃木県'   => '栃木県',
								'群馬県'   => '群馬県',
								'埼玉県'   => '埼玉県',
								'千葉県'   => '千葉県',
								'東京都'   => '東京都',
								'神奈川県'  => '神奈川県',
								'新潟県'   => '新潟県',
								'富山県'   => '富山県',
								'石川県'   => '石川県',
								'福井県'   => '福井県',
								'山梨県'   => '山梨県',
								'長野県'   => '長野県',
								'岐阜県'   => '岐阜県',
								'静岡県'   => '静岡県',
								'愛知県'   => '愛知県',
								'三重県'   => '三重県',
								'滋賀県'   => '滋賀県',
								'京都府'   => '京都府',
								'大阪府'   => '大阪府',
								'神戸'    => '神戸',
								'兵庫県'   => '兵庫県',
								'奈良県'   => '奈良県',
								'和歌山県'  => '和歌山県',
								'鳥取県'   => '鳥取県',
								'島根県'   => '島根県',
								'岡山県'   => '岡山県',
								'広島県'   => '広島県',
								'山口県'   => '山口県',
								'徳島県'   => '徳島県',
								'香川県'   => '香川県',
								'愛媛県'   => '愛媛県',
								'高知県'   => '高知県',
								'福岡県'   => '福岡県',
								'佐賀県'   => '佐賀県',
								'長崎県'   => '長崎県',
								'熊本県'   => '熊本県',
								'大分県'   => '大分県',
								'宮崎県'   => '宮崎県',
								'鹿児島県'  => '鹿児島県',
								'沖縄県'   => '沖縄県',
							),
							'default_value'     => array(),
							'allow_null'        => 0,
							'multiple'          => 0,
							'ui'                => 1,
							'ajax'              => 0,
							'return_format'     => 'value',
							'placeholder'       => '',
						),
						array(
							'key'               => 'field_5bed0acd971ee',
							'label'             => 'タグ',
							'name'              => 'ts_event_tag',
							'type'              => 'taxonomy',
							'instructions'      => '',
							'required'          => 0,
							'conditional_logic' => 0,
							'wrapper'           => array(
								'width' => '',
								'class' => '',
								'id'    => '',
							),
							'taxonomy'          => 'post_tag',
							'field_type'        => 'checkbox',
							'add_term'          => 1,
							'save_terms'        => 1,
							'load_terms'        => 1,
							'return_format'     => 'id',
							'multiple'          => 0,
							'allow_null'        => 0,
						),
					),
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'toiee-event',
							),
						),
					),
					'menu_order'            => 0,
					'position'              => 'normal',
					'style'                 => 'default',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'hide_on_screen'        => '',
					'active'                => 1,
					'description'           => '',
				)
			);

		endif;
	}

	public function create_post_type() {
		register_post_type(
			'toiee-event',
			array(
				'label'               => 'イベント',
				'public'              => true,
				'exclude_from_search' => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-nametag',
				'menu_position'       => 5,
				'hierarchical'        => false,
				'has_archive'         => true,
				'supports'            => array(
					'title',
				),
			)
		);
	}

	public function add_custom_box() {
		if ( function_exists( 'add_meta_box' ) ) {
			add_meta_box( 'tse_sectionid', __( '作成者', 'woocommerce-for-toieelab' ), 'post_author_meta_box', 'toiee-event', 'advanced' );
		}
	}

	public function manage_columns( $columns ) {
		$columns['author']         = '作成者';
		$columns['event_date']     = '開催日時';
		$columns['event_location'] = '場所';
		return $columns;
	}

	public function add_column( $column, $post_id ) {
		if ( 'author' == $column ) {
			$value = get_the_term_list( $post_id, 'author' );
			echo attribute_escape( $value );
		}
		if ( 'event_date' == $column ) {
			$value = get_field( 'ts_event_date', $post_id );
			echo attribute_escape( $value );
		}
		if ( 'event_location' == $column ) {
			$value = get_field( 'ts_event_location', $post_id );
			echo attribute_escape( $value );
		}
	}

	public function shortcode__toiee_show_event_tabel( $atts ) {

		$atts = shortcode_atts(
			array(
				'past_num' => 5,
			),
			$atts
		);
		extract( $atts );

		// キャッシュチェック
		$cache_date = get_option( 'ts_event_cache_date', '2017-01-01' );
		$cache_time = strtotime( $cache_date );

		$lastmod_date = get_lastpostmodified( 'server', 'toiee-event' );
		$lastmod_time = strtotime( $lastmod_date );

		$today      = date( 'Y-m-d' );
		$today_time = strtotime( $today );

		// キャッシュを使う
		$event_tabel = false;
		$is_cache    = false;
		/*
		if( $lastmod_time < $cache_time || $today_time < $cache_time ) {
		$event_tabel = get_option( 'ts_event_cache', false );
		$is_cache = true;
		}
		*/

		// キャッシュを作る
		if ( $event_tabel === false ) {

			$date_now = date( 'Y-m-d 00:00:00' );
			$time_now = strtotime( $date_now );

			// 1ヶ月前のデータ以後を取り出す
			$time_prev_month = strtotime( '-1 month', $time_now );
			$date_prev_month = date( 'Y-m-d H:i:s', $time_prev_month );

			$posts = get_posts(
				array(
					'posts_per_page' => -1,
					'post_type'      => 'toiee-event',
					'meta_query'     => array(
						array(
							'key'     => 'ts_event_date',
							'compare' => '>=',
							'value'   => $date_prev_month,
							'type'    => 'DATETIME',
						),
					),
					'order'          => 'ASC',
					'orderby'        => 'meta_value',
					'meta_key'       => 'ts_event_date',
					'meta_type'      => 'DATETIME',
				)
			);
			// return "<pre>".print_r( $posts , true)."</pre>";
			$events = array_map(
				function ( $post ) {
					$arr = array();

					$arr['author_name'] = get_the_author_meta( 'display_name', $post->post_author );
					$arr['author_url']  = get_the_author_meta( 'user_url', $post->post_author );

					$arr['post_title'] = $post->post_title;
					get_fields( $post->ID );
					$arr = array_merge( $arr, get_fields( $post->ID ) );

					return $arr;

				},
				$posts
			);

			// イベントの確認
			// return "<pre>". print_r( $events, true ) . "</pre>";
			// イベントを都道府県でまとめ直して、ない都道府県は消す
			$event_tabel = $this->get_region();
			foreach ( $events as $e ) {
				if ( ! is_array( $event_tabel[ $e['ts_event_location'] ] ) ) {
					$event_tabel[ $e['ts_event_location'] ] = array();
				}
				   $event_tabel[ $e['ts_event_location'] ][] = $e;
			}

			foreach ( $event_tabel as $key => $e ) {
				if ( ! is_array( $e ) ) {
					unset( $event_tabel[ $key ] );
				}
			}

			update_option( 'ts_event_cache', $event_tabel );
			update_option( 'ts_event_cache_date', date( 'Y-m-d H:i:s' ) );

			// return "<pre>". print_r( $event_tabel , true ). "</pre>";
		}

		$content = '';
		$locs    = array_keys( $event_tabel );

		$content .= implode(
			' / ',
			array_map(
				function ( $a ) {
					return '<a href="#' . $a . '">' . $a . '</a>';
				},
				$locs
			)
		);

		foreach ( $event_tabel as $location => $events ) {
			// テーブルの先頭を作る
			$table =
			'<h2 id="' . $location . '"><span uk-icon="icon: location"></span> ' . $location . '</h2>
<table class="uk-table uk-table-striped">
    <thead>
        <tr>
            <th>日時</th>
            <th>内容</th>
            <th>ファシリテーター</th>
            <th>詳細</th>
        </tr>
    </thead>
    <tbody>
';

			foreach ( $events as $event ) {
				$s_tag  = '';
				$e_tag  = '';
				$expire = false;

				// 日付をチェックし、打ち消し線を設置
				$e_time = strtotime( $event['ts_event_date'] );
				if ( $e_time < ( time() - 24 * 60 * 60 ) ) {
					$s_tag = '<del class="uk-text-muted">';
					$e_tag = '</del>';
					$url   = '終了しました';
				} else {
					$s_tag = '';
					$e_tag = '';
					$url   = '<a href="' . $event['ts_event_url'] . '" class="uk-button uk-button-primary uk-button-small" target="_blank">詳細</a>';
				}

				$week = [ '日', '月', '火', '水', '木', '金', '土' ];
				$w    = $week[ date( 'w', $e_time ) ];
				$date = date( "Y年n月j日($w)", $e_time );

				$table .= "
		        <tr>
		        	<td>{$s_tag}{$date}<br>{$event['ts_event_date_add']}{$e_tag}</td>
		        	<td>{$s_tag}<b>{$event['post_title']}</b><br><small>{$event['ts_event_description']}</small>{$e_tag}</td>
		        	<td>{$s_tag}<a href=\"{$event['author_url']}\">{$event['author_name']}</a>{$e_tag}</td>
		        	<td>{$url}</td>
		        </tr>
				";

			}

			$table .=
			'	</tbody>
</table>';

			$content .= $table;
		}

		return $content;

	}

	private function get_region() {

		return array(
			'オンライン' => 'オンライン',
			'北海道'   => '北海道',
			'青森県'   => '青森県',
			'岩手県'   => '岩手県',
			'宮城県'   => '宮城県',
			'秋田県'   => '秋田県',
			'山形県'   => '山形県',
			'福島県'   => '福島県',
			'茨城県'   => '茨城県',
			'栃木県'   => '栃木県',
			'群馬県'   => '群馬県',
			'埼玉県'   => '埼玉県',
			'千葉県'   => '千葉県',
			'東京都'   => '東京都',
			'神奈川県'  => '神奈川県',
			'新潟県'   => '新潟県',
			'富山県'   => '富山県',
			'石川県'   => '石川県',
			'福井県'   => '福井県',
			'山梨県'   => '山梨県',
			'長野県'   => '長野県',
			'岐阜県'   => '岐阜県',
			'静岡県'   => '静岡県',
			'愛知県'   => '愛知県',
			'三重県'   => '三重県',
			'滋賀県'   => '滋賀県',
			'京都府'   => '京都府',
			'大阪府'   => '大阪府',
			'神戸'    => '神戸',
			'兵庫県'   => '兵庫県',
			'奈良県'   => '奈良県',
			'和歌山県'  => '和歌山県',
			'鳥取県'   => '鳥取県',
			'島根県'   => '島根県',
			'岡山県'   => '岡山県',
			'広島県'   => '広島県',
			'山口県'   => '山口県',
			'徳島県'   => '徳島県',
			'香川県'   => '香川県',
			'愛媛県'   => '愛媛県',
			'高知県'   => '高知県',
			'福岡県'   => '福岡県',
			'佐賀県'   => '佐賀県',
			'長崎県'   => '長崎県',
			'熊本県'   => '熊本県',
			'大分県'   => '大分県',
			'宮崎県'   => '宮崎県',
			'鹿児島県'  => '鹿児島県',
			'沖縄県'   => '沖縄県',
		);
	}

}
