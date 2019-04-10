<?php

/*
 * Plugin Name: WooCommerce Extension for toiee Lab
 * Plugin URI: http://toiee.jp
 * Description: WooCommerceの商品と商品をまとめるデータと連動して、コンテンツの閲覧制限、Seriously Simple Podcastの閲覧制限・機能拡張、ユーザー固有のフィードURL生成、マイライブラリ機能、ショートコードなどを実装
 * Author: toiee Lab
 * Version: 1.0
 * Author URI: http://toiee.jp
 */


/*
	Copyright 2017 toiee Lab (email : desk@toiee.jp)
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

// Use version 2.0 of the update checker.
require 'plugin-update-checker/plugin-update-checker.php';
$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/toiee-lab/wc-restrict/raw/master/update-metadata.json',
	__FILE__,
	'wc-restrict'
);

// include ACF(free)
add_filter(
	'acf/settings/path',
	function( $path ) {
		$path = plugin_dir_path( __FILE__ ) . 'acf/';
		return $path;
	}
);
add_filter(
	'acf/settings/dir',
	function ( $dir ) {
		$dir = plugin_dir_url( __FILE__ ) . 'acf/';
		return $dir;
	}
);
require_once plugin_dir_path( __FILE__ ) . '/acf/acf.php';

require_once 'includes/custom-fields-by-acf.php';  // custom fields

// mailerlite
require 'vendor/autoload.php';

// include some feature
require_once 'includes/woocommerce_settings.php';
require_once 'includes/class-wcr-content.php';
require_once 'includes/class-wcr-ssp.php';
require_once 'includes/wcr-functions.php';
require_once 'includes/class-wcr-mylib.php';
require_once 'includes/class-wcr-ctag.php';
require_once 'includes/class-simple-event.php';
require_once 'includes/class-mailerlite-group.php';
require_once 'includes/toiee-shortcodes.php';
require_once 'includes/class-wcr-login.php';
require_once 'includes/class-installment.php';
require_once 'includes/class-scrum-post.php';
require_once 'includes/class-magazine-post.php';
require_once 'includes/class-poketera-post.php';
require_once 'includes/class-mmdmy-post.php';
require_once 'includes/class-subscription-bank.php';
require_once 'includes/class-pcast.php';
require_once 'includes/class-tkb-post.php';



// generate instances
global $wcr_content;
$wcr_content             = new Woocommerce_SimpleRestrictContent();
$wcr_content->plugin_url = plugins_url( '', __FILE__ );

// Seriously Simple Podcast がインストールされていれば、有効にする
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( is_plugin_active( 'seriously-simple-podcasting/seriously-simple-podcasting.php' ) ) {
	global $wcr_ssp;
	$wcr_ssp             = new WCR_SSP();
	$wcr_ssp->plugin_url = plugins_url( '', __FILE__ );
}

global $wc_installment;
$wc_installment = new ToieeLab_Installment();

global $wc_subscription_bank;
$wc_subscription_bank = new ToieeLab_Subscription_Bank();

global $wcr_mylibrary;
$wcr_mylibrary = new toiee_woocommerce_mylibrary();

global $wcr_customtab;
$wcr_customtab = new Woocommerce_CustomTabs();

global $toiee_simple_event;
$toiee_simple_event = new Toiee_SimpleEvent();

global $toiee_ml_group;
$toiee_ml_group = new Toiee_Mailerlite_Group();

global $wcr_login;
$wdr_login = new Toiee_WCLogin();

global $toiee_scrum;
$toiee_scrum = new Toiee_Scrum_Post( __FILE__ );

global $toiee_magazine;
$toiee_magazine = new Toiee_Magazine_Post();

global $toiee_pocketera;
$toiee_pocketera = new Toiee_Pocketera_Post();

global $toiee_mimidemy;
$toiee_mimidemy = new Toiee_Mimidemy_Post( __FILE__ );

global $toiee_pcast;
$toiee_pcast = new Toiee_Pcast( __FILE__ );

global $toiee_knowledge;
$toiee_knowledge = new Toiee_Tkb_Post( __FILE__ );


// JetPack を WooCommerce Productページでは実行しない
function exclude_jetpack_related_from_products( $options ) {
	if ( is_product() ) {
		$options['enabled'] = false;
	}

	return $options;
}
add_filter( 'jetpack_relatedposts_filter_options', 'exclude_jetpack_related_from_products' );
