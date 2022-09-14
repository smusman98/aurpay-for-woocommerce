<?php
/**
 * Plugin Name: AURPay for WooCommerce
 * Plugin URI: https://www.scintelligencia.com/
 * Author: SCI Intelligencia
 * Description: Allow WooCommerce user to checkout AURPay.
 * Version: 1.0.1
 * Author: Syed Muhammad Usman
 * Author URI: https://www.linkedin.com/in/syed-muhammad-usman/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * @author Syed Muhammad Usman
 * @url https://www.linkedin.com/in/syed-muhammad-usman/
 */


defined( 'ABSPATH' ) || exit;

if ( ! defined( 'APWC_PLUGIN_FILE' ) ) {
    define( 'APWC_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'APWC_VERSION' ) ) {
    define( 'APWC_VERSION', '1.0' );
}

if ( ! defined( 'APWC_PLUGIN_URL' ) ) {
    define( 'APWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if( !defined( 'AURPAY_PLUGIN_FILE' ) ) {
    define( 'AURPAY_PLUGIN_FILE', __FILE__ );
}

if( !defined( 'AURPAY_PLUGIN_URL' ) ) {
    define( 'AURPAY_PLUGIN_URL', plugins_url( '/', AURPAY_PLUGIN_FILE ) );
}



require dirname( APWC_PLUGIN_FILE ) . '/includes/class-apwc-init.php';

add_action( 'plugins_loaded', 'load_apwc' );


/**
 * Loads Plugin
 *
 * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
 * @since 1.0
 * @version 1.0
 */
function load_apwc() {
    APWC_Init::get_instance();
}
