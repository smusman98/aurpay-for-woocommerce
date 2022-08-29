<?php

class APWC_Init {

    /**
     * @var
     *
     * @version 1.0
     * @since 1.0
     */
    private static $_instance;

    /**
     * Single ton
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @return APWC_Init
     * @since 1.0
     * @version 1.0
     */
    public static function get_instance() {

        if( self::$_instance == null ) {
            self::$_instance = new self();
        }

        return self::$_instance;

    }

    /**
     * APWC_Init constructor.
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function __construct() {

        $this->validate();

    }

    /**
     * Meets requirements
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function validate() {

        if( !function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            $this->init();
        }
        else {
            add_action( 'admin_notices', array( $this, 'missing_wc' ) );
        }

    }

    /**
     * Shows Notice
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function missing_wc() {

        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'In order to use AURPay for WooCommerce, make sure WooCommerce is installed and active.', 'aurpay-wc' ); ?></p>
        </div>
        <?php

    }

    /**
     * Finally initialize the Plugin :)
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    private function init() {

        $this->includes();

    }

    /**
     * Includes files
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function includes() {

        require 'class-gateway.php';

    }

}
