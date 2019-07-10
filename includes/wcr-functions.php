<?php

/*
 * シリーズのIDを渡されたら、Grid表示の教材一覧を表示します。
 */
function w4t_podcast_grid_display( $channel_ids, $taxonomy ) {
	$grid = <<<EOD
		<div class="uk-child-width-1-2@m uk-grid-match" uk-grid>
			<div>
				%COL1%
			</div>
			<div>
				%COL2%
			</div>
		</div>
EOD;

	if ( ( ! is_array( $channel_ids ) ) || 0 === count( $channel_ids ) ) {
		return;
	}

	$rows = count( $channel_ids ) / 2 + $channel_ids % 2;
	$ret  = '';

	for ( $i = 0; $i < $rows; $i++ ) {
		$col1 = array_shift( $channel_ids );
		$col2 = array_shift( $channel_ids );

		$ret .= str_replace(
			array( '%COL1%', '%COL2%' ),
			array( w4t_podcast_card( $col1, $taxonomy ), w4t_podcast_card( $col2, $taxonomy ) ),
			$grid
		);
	}

	return $ret;
}

function w4t_podcast_card( $ch_id, $taxonomy ) {
	if ( $ch_id == null ) {
		return '';
	}

	$card = <<<EOD
                <div class="uk-card uk-card-default uk-card-small uk-card-body">
                    <div class="uk-grid-collapse uk-height-1-1" uk-grid>
                        <div><img src="%IMG%" alt="" width="100px"></div>
                        <div class="uk-width-expand">
                            <h3 class="uk-card-title uk-margin-left">%TITLE%</h3>
                            <p class=" uk-margin-left uk-text-small">%DESCRIPTION%</p>
                        </div>
                    </div>
   	                <a href="%URL%" class="uk-display-block uk-position-cover"></a>
                </div>
EOD;

	$channel = get_term( $ch_id, $taxonomy );
	if ( null === $channel ) {
		return '';
	}

	$channel_url   = get_term_link( $channel );
	$channel_image = get_field( 'image', $channel );

	return str_replace(
		array( '%URL%', '%IMG%', '%TITLE%', '%DESCRIPTION%' ),
		array( $channel_url, $channel_image, $channel->name, $channel->description ),
		$card
	);
}

/**
 * ポップアップするリダイレクトログインフォームを取得する。
 * 利用する側は、  uk-toggle="target: #modal_login_form" を使う
 *
 * @param  $redirect_url
 * @return string
 */
function get_popup_login_form( $redirect_url = null ) {
	if ( $redirect_url == null ) {
		$redirect_url = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	// error message がある場合、モーダルウィンドウを表示する（ための準備）
	ob_start();
	wc_print_notices();
	$wc_notices = ob_get_contents();
	ob_end_clean();

	$js = ( $wc_notices != '' ) ?
	"<script>el = document.getElementById('modal_login_form');UIkit.modal(el).show();</script>"
	: '';

	// ログインフォームの取得
	ob_start();
	echo $wc_notices;
	woocommerce_login_form( array( 'redirect' => $redirect_url ) );
	echo $js;
	$login_form = ob_get_contents();
	ob_end_clean();

	// 登録フォームの取得
	ob_start();
	?>
	<form method="post" class="uk-form-horizontal">
	<?php do_action( 'woocommerce_register_form_start' ); ?>

	<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

		<div class="uk-margin">
			<label for="reg_username" class="uk-form-label"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
			<div class="uk-form-controls">
				<input type="text" class="uk-input" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
			</div>
		</div>

	<?php endif; ?>

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
			<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" />
		</p>

	<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
				<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
			</p>

	<?php endif; ?>

	<?php do_action( 'woocommerce_register_form' ); ?>

		<p class="woocommerce-FormRow form-row">
	<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
			<button type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
		</p>

	<?php do_action( 'woocommerce_register_form_end' ); ?>

	</form>
	<?php
	$register_form = ob_get_contents();
	ob_end_clean();   // 登録フォーム取得、ここまで

	$html = <<<EOD
<!-- This is the modal -->
<div id="modal_login_form" uk-modal>
    <div class="uk-modal-dialog uk-modal-body">
	    <ul uk-tab id="modal_login_form_tab">
		    <li><a href="#">会員ログイン</a></li>
		    <li><a href="#">新規登録</a></li>
		</ul>
		<ul class="uk-switcher uk-margin">
		    <li>{$login_form}</li>
		    <li>
		        <div class="uk-alert-success" uk-alert>無料登録で、様々なコンテンツをご覧いただけます。</div>
				{$register_form}
			</li>
		</ul>
        <p class="uk-text-right">
            <button class="uk-button uk-button-default uk-modal-close" type="button">閉じる</button>
        </p>
    </div>
</div>
EOD;

	return $html;
}

// xor 暗号化
if ( ! function_exists( 'toiee_xor_encrypt' ) ) {
	function toiee_xor_encrypt( $plaintext, $key ) {
		$len = strlen( $plaintext );
		$enc = '';
		for ( $i = 0; $i < $len; $i++ ) {
				$asciin = ord( $plaintext[ $i ] );
				$enc   .= chr( $asciin ^ ord( $key[ $i ] ) );
		}
		$enc = base64_encode( $enc );
		return $enc;
	}
}

if ( ! function_exists( 'toiee_xor_decrypt' ) ) {
	function toiee_xor_decrypt( $encryptedText, $key ) {
		$enc       = base64_decode( $encryptedText );
		$plaintext = '';
		$len       = strlen( $enc );
		for ( $i = 0; $i < $len; $i++ ) {
				$asciin     = ord( $enc[ $i ] );
				$plaintext .= chr( $asciin ^ ord( $key[ $i ] ) );
		}
		return $plaintext;
	}
}

function toiee_simple_the_content( $text ) {
	$text = wptexturize( $text );
	$text = convert_smilies( $text );
	$text = convert_chars( $text );
	$text = wpautop( $text );
	$text = shortcode_unautop( $text );
	$text = do_shortcode( $text );
	// $text = prepend_attachment( $text );
	return $text;
}


/**
 * プレイヤーをだす
 *
 * @param $src
 * @param string $type
 */
function the_episode_player( $src, $type = 'video' ) {

	if ( 'video' === $type ) {
		if ( preg_match( '|https://player.vimeo.com/external/([0-9]+)|', $src, $matches ) ) {
			$vid = $matches[1];
			?>
			<div style="padding:56.25% 0 0 0;position:relative;"><iframe src="https://player.vimeo.com/video/<?php echo esc_html( $vid ); ?>?title=0&byline=0&portrait=0" style="position:absolute;top:0;left:0;width:100%;height:100%;" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe></div><script src="https://player.vimeo.com/api/player.js"></script>
			<?php
		} else {
			echo do_shortcode( '[video src="' . $src . '" /]' );
		}
	} else {
		echo do_shortcode( '[audio src="' . $src . '" /]' );
	}
}

/**
 * Plyr.io を利用したプレイヤー
 *
 * @param $src
 * @param string $type
 */
function the_episode_player_plyr( $src, $type = 'video', $ext = '' ) {

	if ( '' !== $ext ) {
		$ext = '-' . $ext;
	}

	if ( 'video' === $type ) {
		?>
		<div class="plyr-container-video">
			<?php
			if ( preg_match( '|https://player.vimeo.com/external/([0-9]+)|', $src, $matches ) ) {
				$vid = $matches[1];
				?>
				<div class="plyr-player<?php echo $ext; ?>" data-plyr-provider="vimeo"
				     data-plyr-embed-id="<?php echo esc_attr( $vid ); ?>"></div>
				<?php
			} else {
				/* ビデオのサムネイルが出るので、デフォルトのプレイヤーを使う */
				echo do_shortcode( '[video src="' . $src . '" /]' );
			}
			?>
		</div>
		<?php
	} else {
/*
		$pid = attachment_url_to_postid( $src );
		if ( $pid ) {
			$mime_type = get_post_mime_type( $pid );
		} else {
			$headers = get_headers( $src, 1 );
			if ( is_array( $headers['Content-Type'] ) ) {
				$mime_type = $headers['Content-Type'][1];
			} else {
				$mime_type = $headers['Content-Type'];
			}
		}
*/
		?>
		<div class="plyr-container-audio">
			<audio class="plyr-player<?php echo $ext; ?>" controls preload="metadata">
				<source src="<?php echo esc_url( $src ); ?>" />
			</audio>
		</div>
		<?php
	}
}

function the_episode_player_dummy( $type = 'video', $message = '閲覧するには、<a href="#" uk-toggle="target: #modal_login_form">ログイン</a>してください' ) {
	if ( $type == 'video' ) {
		$img = plugins_url( '/images/na-video.png', dirname( __FILE__ ) );
	} else {
		$img = plugins_url( '/images/na-audio.png', dirname( __FILE__ ) );
	}

	echo str_replace(
		array( '%IMG%', '%MESSAGE%' ),
		array( $img, $message ),
		'
<div class="uk-margin-medium-top uk-margin-small-bottom">
<img src="%IMG%" /><br>
<span class="uk-text-meta uk-text-small">%MESSAGE%</span>&nbsp;
</div>					
'
	);
}

function toiee_get_edit_button( $post = null, $echo = true ) {

	if ( current_user_can( 'edit_posts' ) ) {
		if ( null === $post ) {
			$post_id = get_the_ID();
		} elseif ( is_object( $post ) ) {
			$post_id = $post->ID;
		} elseif ( is_integer( $post ) ) {
			$post_id = $post;
		}

		$edit_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
		$tag      = '<a href="' . $edit_url . '" class="uk-button uk-button-default uk-margin-small-right uk-align-right">編集する</a>';

		if ( $echo ) {
			echo $tag;
		}

		return $tag;
	}

	return null;
}

/**
 * Customize name field properties.
 *
 * @param array $properties
 * @param array $field
 * @param array $form_data
 * @return array
 */
function toiee_wpf_name_field_properties( $properties, $field, $form_data ) {

	// Change sublabel values
	$properties['inputs']['first']['sublabel']['value'] = '名(太郎)';
	$properties['inputs']['middle']['sublabel']['value'] = 'Middle';
	$properties['inputs']['last']['sublabel']['value'] = '姓(戸井)';

	return $properties;
}
add_filter( 'wpforms_field_properties_name' , 'toiee_wpf_name_field_properties', 10, 3 );

