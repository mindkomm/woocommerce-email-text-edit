<?php
/**
 * Plugin Name: WooCommerce Email Text Edit
 * Description: Modify email texts used in your WooCommerce emails without having to edit email templates.
 * Author: MIND
 * Author URI: https://www.mind.ch
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-email-text-edit
 * Domain Path: languages/
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'WooCommerce_Email_Text_Edit' ) ) {
	require_once 'lib/class-woocommerce-email-text-edit.php';
}

add_action( 'init', function() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$locale  = get_locale();
	$path    = trailingslashit( dirname( __FILE__ ) );
	$mo_path = $path . "languages/woocommerce-email-text-edit-{$locale}.mo";

	load_textdomain( 'woocommerce-email-text-edit', $mo_path );

	if ( class_exists( 'WooCommerce_Email_Text_Edit' ) ) {
		$wc_ete = new WooCommerce_Email_Text_Edit();
		$wc_ete->init();
	}
} );
