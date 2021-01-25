<?php

if ( class_exists( 'WC_Payment_Gateway' ) ) :
  class Integrai_Payment_Method_Boleto extends WC_Payment_Gateway {

    public function __construct() {
      $this->id                 = 'integrai_payment_method_boleto';
      $this->has_fields         = true;
      $this->icon 	            = apply_filters('woocommerce_custom_gateway_icon', '');
      $this->title              = __( 'Integrai', 'woocommerce-integrai-settings' );
      $this->method_title       = __( 'Integrai', 'woocommerce-integrai-settings' );  // Title shown in admin
      $this->method_description = __( 'Método de pagamento da Integrai. Permite fazer pagamento via Boleto.', 'woocommerce-integrai-settings' );  // Title shown in admin


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

      // Add the custom data to order post
      add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ) );

      // Display custom order data on admin
      add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_admin_order_meta' ) );

      // Custom thankyou page
      add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

    }

    public function init_form_fields() {
      $this->form_fields = array(
        'enabled' => array(
          'title' => __( 'Enable', 'woocommerce' ),
          'type' => 'select',
          'description' => __( 'Habilitar pagamento via Boleto (Integrai)', 'woocommerce' ),
          'default' => 'yes',
          'options' => array(
            'true' => 'Sim',
            'false' => 'Não',
          ),
        ),
        'title' => array(
          'title' => __( 'Title', 'woocommerce' ),
          'type' => 'text',
          'description' => __( 'Título que o seu cliente irá visualizar na tela de pagamento, opção Boleto.', 'woocommerce' ),
          'default' => __( 'Boleto', 'woocommerce' ),
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
      $configHelper = new Integrai_Model_Config();
      $options = $configHelper->get_payment_boleto();
      ?>
        <div class="form-list" id="payment_form_integrai-boleto">
            <div id="integrai-payment-boleto"></div>
        </div>

        <script>
            window.integraiBoletoData = JSON.parse('<?php echo json_encode( $options ) ?>');

            window.IntegraiBoleto = Object.assign({}, integraiCCData.formOptions, {
                boletoModel: JSON.parse('<?php echo $this->getCustomer() ?>'),
            });

            integraiBoletoData.scripts.forEach(function (script) {
                let scriptElm = document.createElement('script');
                scriptElm.src = script;

                document.body.appendChild(scriptElm);
            });
        </script>

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

    public function thankyou_page() {
       echo wpautop( wptexturize( 'LINK do Boleto' ) );
    }

  }
endif;
?>
