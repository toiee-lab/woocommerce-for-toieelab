<?php

/*
 * シリーズのIDを渡されたら、Grid表示の教材一覧を表示します。
 */
function w4t_podcast_grid_display( $series_ids ){

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

	$rows = count( $series_ids ) / 2 + $series_ids%2;
	$ret = '';

	for($i=0; $i<$rows; $i++){
		$col1 = array_shift( $series_ids );
		$col2 = array_shift( $series_ids );

		$ret .= str_replace(
			array('%COL1%', '%COL2%'),
			array( w4t_podcast_card($col1), w4t_podcast_card($col2) ),
			$grid
		);
	}

	return $ret;
}

function w4t_podcast_card( $sid ){
	if($sid == NULL) return '';

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

	$series = get_term( $sid, 'series');
	$series_url   = get_term_link( $series );
	$series_image = get_option( 'ss_podcasting_data_image_' . $sid, 'no-image' );

	return str_replace(
		array('%URL%','%IMG%', '%TITLE%', '%DESCRIPTION%'),
		array($series_url, $series_image, $series->name, $series->description),
		$card
	);
}

// xor 暗号化
if( ! function_exists('toiee_xor_encrypt') ) {
	function toiee_xor_encrypt($plaintext, $key){
        $len = strlen($plaintext);
        $enc = "";
        for($i = 0; $i < $len; $i++){
                $asciin = ord($plaintext[$i]);
                $enc .= chr($asciin ^ ord($key[$i]));
        }
        $enc = base64_encode($enc);
        return $enc;
	}
}

if( ! function_exists( 'toiee_xor_decrypt' ) ) {	
	function toiee_xor_decrypt($encryptedText, $key){
        $enc = base64_decode($encryptedText);
        $plaintext = "";
        $len = strlen($enc);
        for($i = 0; $i < $len; $i++){
                $asciin = ord($enc[$i]);
                $plaintext .= chr($asciin ^ ord($key[$i]));
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
//	$text = prepend_attachment( $text );
		
	return $text;	
}