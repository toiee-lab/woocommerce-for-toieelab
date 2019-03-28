<?php

/*
require_once '../../../../wp-load.php';
$errors = array();
if (
	isset( $_POST['type'] ) &&
	is_user_logged_in() &&
	isset( $_POST['_wpnonce'] ) &&
	wp_verify_nonce( $_POST['_wpnonce'], 'csv_exporter' )
) {
	check_admin_referer('csv_exporter');

	global $wpdb;


	//ダウンロードの指示
	header( 'Content-Type:application/octet-stream' );
	header( 'Content-Disposition:filename='.$filename );  //ダウンロードするファイル名
	header( 'Content-Length:' . filesize( $filepath ) );   //ファイルサイズを指定
	readfile( $filepath )

}
*/
