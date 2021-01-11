<?php

if ( class_exists( 'WC_Payment_Gateway' ) ) :
  class Integrai_Payment_Method_Credit_Card extends WC_Payment_Gateway {

    public function __construct() {
      $this->id                 = 'integrai_payment_method_credit_card';
      $this->has_fields         = true;
      $this->icon 	            = apply_filters('woocommerce_custom_gateway_icon', '');
      $this->title              = __( 'Integrai', 'woocommerce' );
      $this->method_title       = __( 'Integrai', 'woocommerce' );  // Title shown in admin
      $this->method_description = __( 'Método de pagamento da Integrai. Permite fazer pagamento com plataformas como MercadoPago, Wirecard, PagarMe.', 'woocommerce-integrai-settings' );  // Title shown in admin
      $this->supports           = array(
        'products',
        'refunds',
      );

      $this->init();
    }

    public function init() {

      // Load the settings API
      $this->init_form_fields();
      $this->init_settings();

      // Define user set variables.
      $this->enabled      = $this->get_option( 'enabled' );
      $this->title        = $this->get_option( 'title' );
      $this->description  = $this->get_option( 'description' );

      // Save settings in admin if you have any defined
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

    }

    public function init_form_fields() {
      $this->form_fields = array(
        'enabled' => array(
          'title' => __( 'Enable', 'woocommerce' ),
          'type' => 'select',
          'description' => __( 'Habilitar pagamento via Cartão de Crédito (Integrai)', 'woocommerce' ),
          'default' => 'yes',
          'options' => array(
            'true' => 'Sim',
            'false' => 'Não',
          ),
        ),
        'title' => array(
          'title' => __( 'Title', 'woocommerce' ),
          'type' => 'text',
          'description' => __( 'Título que o seu cliente irá visualizar na tela de pagamento, opção Cartão de Crédito.', 'woocommerce' ),
          'default' => __( 'Cartão de Crédito', 'woocommerce' ),
          'desc_tip'      => true,
        ),
        'description' => array(
          'title' => __( 'Description', 'woocommerce' ),
          'type' => 'textarea',
          'default' => '',
        )
      );
    }

    /**
     * Prints the form fields
     *
     * @access public
     * @return void
     */
    public function payment_fields() {
      ?>

        <p>Cartão de Crédito</p>

        <div class="form-list" id="payment_form_integrai">
          <div id="integrai-payment-creditcard"></div>
        </div>

      <?php
    }

    public function process_payment( $order_id ) {
      global $woocommerce;
      $order = new WC_Order( $order_id );

      // Mark as on-hold (we're awaiting the cheque)
      $order->update_status('on-hold', __( 'Awaiting cheque payment', 'woocommerce' ));

      // Remove cart
      $woocommerce->cart->empty_cart();

      // Return thankyou redirect
      return array(
        'result' => 'success',
        'redirect' => $this->get_return_url( $order )
      );
    }

  }
endif;
?>
