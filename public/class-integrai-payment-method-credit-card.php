<?php

if ( class_exists( 'WC_Payment_Gateway' ) ) :
  class Integrai_Payment_Method_Credit_Card extends WC_Payment_Gateway {

    public function __construct() {
      $this->id                 = 'integrai_payment_cc';
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

      // Custom thankyou page
      add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

      // Process order checkout
      add_action( 'woocommerce_checkout_process', array( $this, 'checkout_process' ) );

      // Add the custom data to order post
      add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ) );

      // Display custom order data on admin
      add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_admin_order_meta' ) );

      // Customer Emails
      // add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
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
     * Output for the order received page.
     */
    public function thankyou_page() {
       echo wpautop( wptexturize( 'OBRIGADO! Funcionou' ) );
    }

    public function payment_fields() {
      if ( $this->supports( 'tokenization' ) && is_checkout() ) {
        $this->tokenization_script();
        $this->saved_payment_methods();
        $this->form();
        $this->save_payment_method_checkbox();
      } else {
        $this->form();
      }
    }

    public function form() {
//      wp_enqueue_script( 'wc-credit-card-form' );
      $configHelper = new Integrai_Model_Config();
      $options = $configHelper->get_payment_creditcard();

      $cart_totals = WC()->session->get('cart_totals');
      $total = $cart_totals['total'] ? $cart_totals['total'] : null;

      ?>
        <p>Cartão de Crédito</p>

        <div class="form-list" id="payment_form_integrai">
            <div id="integrai-payment-creditcard"></div>
        </div>

        <script>
            if (!window.integraiData) {
                window.integraiData = JSON.parse('<?php echo json_encode( $options ) ?>');
                console.log('integraiData: ', integraiData);
            }

            window.IntegraiCreditCard = Object.assign({}, integraiData.formOptions, {
                amount: <?php echo $total ?>
            });

            integraiData.scripts.forEach(function (script) {
                let scriptElm = document.createElement('script');
                scriptElm.src = script;

                document.body.appendChild(scriptElm);
                console.log('Append: ', script);
            });
        </script>
      <?php

    }

    public function checkout_process( $order_id ) {
      if ($_POST['payment_method'] != 'integrai_payment_cc')
        return;

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

    public function update_order_meta( $order_id ) {

      if ($_POST['payment_method'] != 'integrai_payment_cc')
        return;

       echo "<pre>";
        print_r($_POST);
       echo "</pre>";
       exit();

//      update_post_meta( $order_id, 'mobile', $_POST['mobile'] );
//      update_post_meta( $order_id, 'transaction', $_POST['transaction'] );
    }

    public function display_admin_order_meta( $order ) {
      $method = get_post_meta( $order->id, '_payment_method', true );

      if ($method != 'integrai_payment_cc')
        return;

//      $mobile      = get_post_meta( $order->id, 'mobile', true );
//      $transaction = get_post_meta( $order->id, 'transaction', true );

      echo '<p><strong>'.__( 'Teste' ).':</strong> 123</p>';
//      echo '<p><strong>'.__( 'Transaction ID').':</strong> ' . $transaction . '</p>';

    }

  }
endif;
?>
