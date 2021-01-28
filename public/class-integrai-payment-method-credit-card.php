<?php

if ( class_exists( 'WC_Payment_Gateway' ) ) :
  class Integrai_Payment_Method_Credit_Card extends WC_Payment_Gateway {

    public function __construct() {
      $this->id                 = 'integrai_payment_cc';
      $this->has_fields         = true;
      $this->icon 	            = apply_filters('woocommerce_custom_gateway_icon', '');
      $this->title              = __( 'Integrai', 'woocommerce' );
      $this->method_title       = __( 'Integrai', 'woocommerce' );
      $this->method_description = __( 'Método de pagamento da Integrai. Permite fazer pagamento com plataformas como MercadoPago, Wirecard, PagarMe.', 'woocommerce-integrai-settings' );

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

      // Validate custom form data
      // add_action('woocommerce_checkout_process', array( $this, 'process_custom_payment' ));

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

    public function validate_fields() {
      $payment          = $_POST['payment'];
      $payment_method   = $_POST['payment_method'];

      $card_number      = $payment['card_number'];
      $expiration_month = $payment['expiration_month'];
      $expiration_year  = $payment['expiration_year'];
      $card_cvc         = $payment['card_cvc'];
      $holder_name      = $payment['holder_name'];
      $doc_type         = $payment['doc_type'];
      $doc_number       = $payment['doc_number'];
      $birth_date       = $payment['birth_date'];
      $installments     = $payment['installments'];

      if ( $payment_method !== $this->id )
          return;

      if( !isset( $card_number ) || empty( $card_number ) )
        wc_add_notice( __( 'Card Number is required', $this->id ), 'error' );

      if( !isset( $expiration_month ) || empty( $expiration_month ) )
        wc_add_notice( __( 'Expiration month is required', $this->id ), 'error' );

      if( !isset( $expiration_year ) || empty( $expiration_year ) )
        wc_add_notice( __( 'Expiration year is required', $this->id ), 'error' );

      if( !isset( $card_cvc ) || empty( $card_cvc ) )
        wc_add_notice( __( 'Card CVC is required', $this->id ), 'error' );

      if( !isset( $holder_name ) || empty( $holder_name ) )
        wc_add_notice( __( 'Card Holder\'s name is required', $this->id ), 'error' );

      if( !isset( $doc_type ) || empty( $doc_type ) )
        wc_add_notice( __( 'Document type (CPF / CNPJ) is required', $this->id ), 'error' );

      if( !isset( $doc_number ) || empty( $doc_number ) )
        wc_add_notice( __( 'Document number is required', $this->id ), 'error' );

      if( !isset( $birth_date ) || empty( $birth_date ) )
        wc_add_notice( __( 'Birth date is required', $this->id ), 'error' );

      if( !isset( $installments ) || empty( $installments ) )
        wc_add_notice( __( 'Select the number of installments', $this->id ), 'error' );

      // Validate DOCUMENT:
      if ( $doc_type === 'cpf' || $doc_type === 'cnpj' ) {
        $is_valid = Integrai_Validator::{$doc_type}( $doc_number );

        if ( !$is_valid )
          wc_add_notice( __( strtoupper($doc_type) . ' number is invalid', $this->id ), 'error' );
      }

      return true;

    }

    public function thankyou_page() {
//       echo wpautop( wptexturize( 'OBRIGADO! Funcionou' ) );
    }

    public function payment_fields() {
      $configHelper = new Integrai_Model_Config();
      $options = $configHelper->get_payment_creditcard();

      $cart_totals = WC()->session->get('cart_totals');
      $total = $cart_totals['total'] ? $cart_totals['total'] : null;

      ?>
        <div class="form-list" id="payment_form_integrai">
            <div id="integrai-payment-creditcard"></div>
        </div>

        <script>
            if (!window.integraiCCData) {
                window.integraiCCData = JSON.parse('<?php echo json_encode( $options ) ?>');
            }

            window.IntegraiCreditCard = Object.assign({}, integraiCCData.formOptions, {
                amount: <?php echo $total ?>
            });

            integraiCCData.scripts.forEach(function (script) {
                let scriptElm = document.createElement('script');
                scriptElm.src = script;

                document.body.appendChild(scriptElm);
            });
        </script>
      <?php
    }

    public function process_payment( $order_id ) {
      if ($_POST['payment_method'] != $this->id)
        return;

      global $woocommerce;
      $order = wc_get_order( $order_id );

      // Mark as on-hold (we're awaiting the cheque)
      $order->update_status('on-hold', __( 'Integrai: Transação sendo processada', 'integrai' ));

      // Remove cart
      $woocommerce->cart->empty_cart();

      // Return thank you redirect
      return array(
        'result' => 'success',
        'redirect' => $this->get_return_url( $order )
      );
    }

    public function update_order_meta( $order_id ) {
      $payment_method = $_POST['payment_method'];
      $data = $_POST['payment'];

      if ( $payment_method != $this->id || empty( $data ) )
        return;

      // Sanitize data
      $payment_data = array_map(
        'sanitize_text_field',
        array(
          'payment_method'  => $payment_method,
          'installments'    => $data['installments'],
          'card_brand'      => $data['additional_data']['card_brand'],
          'doc_type'        => $data['doc_type'],
          'doc_number'      => $data['doc_number'],
        )
      );

      // Save data on order
      foreach ( $payment_data as $key => $value ) {
        update_post_meta( $order_id, $key, $value );
      }
    }

    public function display_admin_order_meta( $order ) {
      $payment_method = get_post_meta( $order->id, '_payment_method', true );

      if ( $payment_method !== $this->id )
        return;

      $installments  = get_post_meta( $order->id, 'installments',   true );
      $card_brand    = get_post_meta( $order->id, 'card_brand',     true );
      $doc_type      = get_post_meta( $order->id, 'doc_type',       true );
      $doc_number    = get_post_meta( $order->id, 'doc_number',     true );

      // Update meta data title
      $meta_data = array(
        __( 'Método de Pagamento', 'integrai' ) => 'Cartão de Crédito (Integrai)',
        __( 'Bandeira do Cartão', 'integrai' )  => sanitize_text_field( ucfirst( $card_brand ) ),
        __( 'Documento', 'integrai' )           => sanitize_text_field( strtoupper($doc_type) ),
        __( 'Número do Documento', 'integrai' ) => sanitize_text_field( $doc_number ),
        __( 'Número de Parcelas', 'integrai' )  => sanitize_text_field( $installments ),
      );

      ?>
        <div class="clear"></div>
        <div class="integrai_payment">
            <h4><?php echo __( 'Payment Method', '' ) ?></h4>
            <p>
              <?php
                foreach ($meta_data as $key => $value) {
                  echo '<strong>' . $key . ':</strong> ' . $value . '<br />';
                }
              ?>
            </p>
        </div>

      <?php
    }
  }
endif;
?>
