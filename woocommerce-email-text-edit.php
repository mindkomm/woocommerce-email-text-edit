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
if ( ! class_exists( 'WooCommerce_Email_Text_Edit' ) ) {
	require_once 'lib/class-woocommerce-email-text-edit.php';
}

add_action( 'plugins_loaded', function() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	load_plugin_textdomain( 'woocommerce-email-text-edit', false, basename( dirname( __FILE__ ) ) . '/languages/' );

	if ( class_exists( 'WooCommerce_Email_Text_Edit' ) ) {
		$wc_ete = new WooCommerce_Email_Text_Edit();
		$wc_ete->init();
	}
} );
