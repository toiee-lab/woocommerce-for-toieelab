<?php

/*
 * Plugin Name: WooCommerce Simple Restrict Content
 * Plugin URI: http://toiee.jp
 * Description: WooCommerceの商品と連動して、コンテンツの閲覧制限を設定できます。また Seriously Simple Podcastの閲覧制限も可能でdす。
 * Author: toiee Lab
 * Version: 0.3.3
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


// Seriously Simple Pocast の会員別のURLを作るときに使う「暗号化シード」のデフォルト値
// 通常は、設定で置き換えることになる
define('WCR_SSP_SECKEY', 'wLEznoW2QdUjEE');


require_once( 'includes/class-wcr-content.php' );
require_once( 'includes/class-wcr-ssp.php' );
require_once( 'includes/wcr-functions.php' );


global $wcr_content;
$wcr_content = new Woocommerce_SimpleRestrictContent();

// Seriously Simple Podcast がインストールされていれば、有効にする
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if(is_plugin_active( 'seriously-simple-podcasting/seriously-simple-podcasting.php' )){
	global $wcr_ssp;
	$wcr_ssp = new WCR_SSP();
}

