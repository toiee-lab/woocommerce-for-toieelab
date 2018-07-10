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