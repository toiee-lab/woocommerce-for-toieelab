<?php


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