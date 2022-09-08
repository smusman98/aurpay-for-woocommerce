<?php

class APWC_Gateway extends WC_Payment_Gateway {

    /**
     * End Point
     *
     * @var string
     * @since 1.0
     * @version 1.0
     */
    private $endpoint      = 'https://dashboard.aurpay.net/api/order/plugin';

    /**
     * APWC_Gateway constructor.
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function __construct() {

        $this->id = 'aurpay';
        $this->title = $this->get_option( 'title' );
        $this->icon =  apply_filters( 'wcap_icon', APWC_PLUGIN_URL . '/assets/images/icon.png' );
        $this->has_fields = true;
        $this->method_title = 'AURPay';
        $this->description = $this->get_option( 'description' );
        $this->public_key = $this->get_option( 'public_key' );
        $this->has_fields = false;
        $this->method_description = 'Allows customer to checkout with AURPay.';
        $this->init_form_fields();
        $this->init_settings();

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_api_apwc_gateway', array( $this, 'ipn_callback' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

    }

    /**
     * Enqueue Scripts | Action Callback
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function enqueue_scripts() {

        wp_enqueue_style( 'aurpay', AURPAY_PLUGIN_URL . 'assets/css/style.css', false, APWC_VERSION );

    }

    /**
     * Admin form fields
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function init_form_fields() {

        $this->form_fields = array(
            'enabled'   =>  array(
                'title'     =>  'Enabled/ Disabled',
                'type'      =>  'checkbox',
                'label'     =>  'Enable AURPay',
                'default'   =>  'no'
            ),
            'description'   =>  array(
                'title'         =>  'Pay with AURPay',
                'type'          =>  'textarea',
                'default'       =>  'Pay with AURPay',
                'desc_tip'      =>  true,
                'description'   =>  'Add a new description for AURPay Gateway, Customers will se at checkout.',
            ),
            'public_key' => array(
                'title'       => 'Public Key',
                'type'        => 'password',
                'description' => __( 'Please enter your AURPay Public Key, this is needed in order to take payment.', 'aurpay-wc' ),
            ),
            'currency_selection_text' => array(
                'title'       => 'Checkout Currency Selection Label',
                'type'        => 'text',
                'default'     =>  'Select a cryptocurrency',
                'description' => __( 'Please enter your AURPay Public Key, this is needed in order to take payment.', 'aurpay-wc' ),
            )
        );

    }

    /**
     * Add payment fields for woocommerce
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function payment_fields()
    {
        $options = [
            'ETH'        => 'ETH',
            'USDC-ERC20' => 'USDC-ERC20',
            'USDT-ERC20' => 'USDT-ERC20',
            'DAI-ERC20'  => 'DAI-ERC20',
            // 'BTC'        => 'BTC',
            // 'USDT-OMNI'  => 'USDT-OMNI',
            'TRX'        => 'TRX',
            'USDT-TRC20' => 'USDT-TRC20',
            'USDC-TRC20' => 'USDC-TRC20',
        ];
        ksort($options);


        woocommerce_form_field('aurpay_coin',
            [
                'type'      => 'select',
                'class'     => ['aurpay_coin'],
                'label'     => esc_html__( $this->get_option( 'currency_selection_text' ) ),
                'options'   => $options,
                'required'  => true,
                'autofocus' => true,
            ]
        );
    }

    /**
     * Get currency from chain
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function get_chain( $currency )
    {
        $ETH = array('ETH', 'USDC-ERC20', 'USDT-ERC20', 'DAI-ERC20');
        $BTC = array('BTC', 'USDT-OMNI');
        $TRX = array('TRX', 'USDT-TRC20', 'USDC-TRC20');

        if(in_array($currency, $ETH)){
            return 'ETH';
        }
        if(in_array($currency, $BTC)){
            return 'BTC';
        }
        if(in_array($currency, $TRX)){
            return 'TRX';
        }

    }

    /**
     * Process Admin Settings | Validate
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @return bool|void
     * @since 1.0
     * @version 1.0
     */
    public function process_admin_options() {

        parent::process_admin_options();

        if ( empty( $_POST['woocommerce_aurpay_public_key'] ) ) {
            WC_Admin_Settings::add_error( 'Error: Public Key is required.' );
            return false;
        }

    }

    /**
     * Process Payment
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function process_payment( $order_id )
    {
        global $woocommerce;
        $order = new WC_Order( $order_id );

        if ( !$_POST['aurpay_coin'] ) {
            wc_add_notice( __('Please select a currency' ), 'error' );
            exit;
        }


        // No payment is required to exit
        if ( !$order || !$order->needs_payment() ) {
            wp_redirect( $this->get_return_url( $order ) );
            exit;
        }

        $headers = array(
            'Content-Type'  =>  'application/json; charset=utf-8',
            'API-Key'        =>  $this->get_option( 'public_key' ),
        );

        $currency = sanitize_text_field( $_POST['aurpay_coin'] );
        $chain = $this->get_chain( $currency );

        $body = [

            'chain'     => $chain,
            'currency'  => $currency,
            'callback'  => $this->get_return_url( $order ),
            'platform'  => 'WOOCOMMERCE',
            'origin'    => [
                'id'        => $order_id,
                'currency'  => $order->get_currency(),
                'price'     => $order->get_total(),
                'url'       => site_url(),
                'callback_url'  =>  site_url() . '/?wc-api=APWC_Gateway' . '&order_id=' . $order_id,
            ]

        ];

        $options = array(
            'timeout'       =>  30,
            'redirection'   =>  5,
            'headers'       =>  $headers,
            'body'          =>  wp_json_encode( $body ),
            'sslverify'     =>  false,
        );

        $response = wp_remote_post(
            $this->endpoint,
            $options
        );

        $resp_data = json_decode( wp_remote_retrieve_body( $response ) ) ?: array();
        $response_code = wp_remote_retrieve_response_code( $response );

        if ( $response_code != 200 ){
            wc_add_notice( sprintf( 'Server Error[%d]: %s',  $response_code, $resp_data->msg), 'error' );
            return;
        }

        if ( $response_code == 200 && $resp_data->code == 0 ) {

            $order->update_status( 'Processing', __( 'Awaiting cheque payment', 'woocommerce' ));

            // Remove cart
            $woocommerce->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'   => 'success',
                'redirect' => $resp_data->data->pay_url,
            );
        } else {
            if ( $resp_data->code = 401 ) {
                wc_add_notice( sprintf( 'Aurpay payment notify Error: %s', 'Please fill in the public key in admin'), 'error' );
            }
            else {
                wc_add_notice( sprintf( 'Aurpay payment notify Error[%d]: %s',  $resp_data->code, $resp_data->message ), 'error' );
            }
        }
    }

    /**
     * Webhook Catcher | action_hook callback
     *
     * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
     * @since 1.0
     * @version 1.0
     */
    public function ipn_callback() {


            $public_key = isset( $_GET['public_key'] ) ? sanitize_text_field( $_GET['public_key'] ) : '';
            $order_id = isset( $_GET['order_id'] ) ? sanitize_text_field( $_GET['order_id'] ) : '';

            if($public_key != $this->public_key)  wp_send_json( array( 'message'    =>	'Public Key Error: '.$public_key ), 400 );
            try
            {
                $order = new WC_Order( $order_id );

                $order->update_status( 'completed', 'AURPay finished IPN Call.' );

			    delete_option( $order_id );

                wp_send_json_success( array(
                    'a' => $this->public_key,
                    'order_id' => $order_id,
                ), 200 );
            } catch (Exception $e) {
                wp_send_json( array( 'message'	=>	'No order associated with this ID.' . $order_id . $e ), 400 );
            }

    }

}

/**
 * Adds Gateway into WooCommerce
 *
 * @author Syed Muhammad Usman (@smusman98) <smusman98@gmail.com>
 * @param $gateways
 * @return mixed
 * @since 1.0
 * @version 1.0
 */
if ( !function_exists( 'add_aurpay_to_wc' ) ):
    function add_aurpay_to_wc( $gateways ) {
        $gateways[] = 'APWC_Gateway';
        return $gateways;
    }
endif;

add_filter( 'woocommerce_payment_gateways', 'add_aurpay_to_wc' );