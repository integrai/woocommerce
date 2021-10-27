<?php

include_once INTEGRAI__PLUGIN_DIR . 'public/class-integrai-payment-method.php';

if ( class_exists( 'WC_Payment_Gateway' ) ) :
  class Integrai_Payment_Method_MarketPlace extends WC_Payment_Gateway {
    public $fields_list = array();

    public function __construct() {
      $this->id                 = 'integrai_marketplace';
      $this->has_fields         = true;
      $this->icon 	            = apply_filters('woocommerce_custom_gateway_icon', '');
      $this->title              = __( 'Marketplace', 'woocommerce' );
      $this->method_title       = __( 'Marketplace', 'woocommerce' );  // Title shown in admin
      $this->method_description = __( 'Método de pagamento do Marketplace.', 'woocommerce' );  // Title shown in admin
      $this->fields_list       = array();

      $this->init();
    }

    public function init() {

      // Load the settings API
      $this->init_form_fields();
      $this->init_settings();

      // Define user set variables.
      $this->enabled      = $this->is_enabled();
      $this->title        = $this->get_option( 'title' );
      $this->description  = $this->get_option( 'description' );

      // Save settings in admin if you have any defined
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

      // Add the custom data to order post
      add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ) );

      // Display custom order data on admin
      add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_admin_order_meta' ) );

    }

    private function is_enabled() {
      return true;
    }

    public function init_form_fields() {
      $this->form_fields = array(
        'enabled' => array(
          'title' => __( 'Enable', 'woocommerce' ),
          'type' => 'select',
          'default' => 'yes',
          'options' => array(
            'true' => 'Sim',
            'false' => 'Não',
          ),
        ),
        'title' => array(
          'title' => __( 'Title', 'woocommerce' ),
          'type' => 'text',
          'default' => __( 'Marketplace', 'woocommerce' ),
          'desc_tip'      => true,
        ),
        'description' => array(
          'title' => __( 'Description', 'woocommerce' ),
          'type' => 'textarea',
          'default' => '',
        )
      );
    }

    public function update_order_meta( $order_id ) {
      $payment_method = $this->get_helper()->get_sanitized('payment_method', $_POST);
      $payment = $this->get_helper()->sanitize_fields($this->fields_list, $_POST['payment']);

      if ( $payment_method != $this->id || empty( $payment ) )
        return;

      $payment['payment_method'] = $payment_method;

      $this->get_helper()->save_transaction_data( $order_id, $payment );
    }

    public function display_admin_order_meta( $order ) {
      $payment_method = get_post_meta( $order->get_id(), '_payment_method', true );

      if ( $payment_method !== $this->id )
        return;

      $payment_method_model = new Integrai_Payment_Method();
      $admin_data = $payment_method_model->admin_order_meta($order->get_id());

      if (!empty($admin_data['marketplace_data']) || !empty($admin_data['payments'])) {
        $this->get_helper()->get_template(
            'admin-order-detail.php',
            array(
                'marketplace_data' => $admin_data['marketplace_data'],
                'payments' => $admin_data['payments'],
            ),
        );
      }
    }

    private function get_helper() {
        return new Integrai_Payment_Method_Helper( $this->id );
    }
  }
endif;
?>
