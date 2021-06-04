<?php

if ( class_exists( 'WC_Payment_Gateway' ) ) :
  class Integrai_Payment_Method_Credit_Card extends WC_Payment_Gateway {

    public function __construct() {
      $this->id                 = 'integrai_creditcard';
      $this->has_fields         = true;
      $this->icon 	            = apply_filters('woocommerce_custom_gateway_icon', '');
      $this->title              = __( 'Integrai', 'woocommerce' );
      $this->method_title       = __( 'Integrai', 'woocommerce' );
      $this->method_description = __( 'Método de pagamento da Integrai. Permite fazer pagamento com plataformas como MercadoPago, Wirecard, PagarMe.', 'woocommerce' );

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
      add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
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

      if ( $payment_method !== $this->id )
        return false;

      if( !isset( $payment['cc_card_number'] ) || empty( $payment['cc_card_number'] ) )
        wc_add_notice( __( 'Informe o Número do cartão de crédito.', $this->id ), 'error' );

      if( !isset( $payment['cc_expiration_month'] ) || empty( $payment['cc_expiration_month'] ) )
        wc_add_notice( __( 'Informe o mês de expiração do cartão de crédito.', $this->id ), 'error' );

      if( !isset( $payment['cc_expiration_year'] ) || empty( $payment['cc_expiration_year'] ) )
        wc_add_notice( __( 'Informe o ano de expiração do cartão de crédito.', $this->id ), 'error' );

      if( !isset( $payment['cc_card_cvc'] ) || empty( $payment['cc_card_cvc'] ) )
        wc_add_notice( __( 'Informe o CVC do cartão de crédito.', $this->id ), 'error' );

      if( !isset( $payment['cc_holder_name'] ) || empty( $payment['cc_holder_name'] ) )
        wc_add_notice( __( 'Informe o nome do titular do cartão de crédito.', $this->id ), 'error' );

      if( !isset( $payment['cc_doc_type'] ) || empty( $payment['cc_doc_type'] ) )
        wc_add_notice( __( 'Selecione o tipo do documento (CPF ou CNPJ).', $this->id ), 'error' );

      if( !isset( $payment['cc_doc_number'] ) || empty( $payment['cc_doc_number'] ) )
        wc_add_notice( __( 'Informe o documento (CPF ou CNPJ).', $this->id ), 'error' );

      if( !isset( $payment['cc_birth_date'] ) || empty( $payment['cc_birth_date'] ) )
        wc_add_notice( __( 'Informe a data de nascimento.', $this->id ), 'error' );

      if( !isset( $payment['cc_installments'] ) || empty( $payment['cc_installments'] ) )
        wc_add_notice( __( 'Selecione o número de parcelas.', $this->id ), 'error' );

      // Validate DOCUMENT:
      if ( $payment['cc_doc_type'] === 'cpf' || $payment['cc_doc_type'] === 'cnpj' && isset($payment['cc_doc_number']) ) {
        $is_valid = Integrai_Validator::{$payment['cc_doc_type']}( $payment['cc_doc_number'] );

        if ( !$is_valid )
          wc_add_notice( __( strtoupper('O ' . $payment['cc_doc_type']) . ' informado é inválido. Verifique e tente novamente.', $this->id ), 'error' );
      }

      if( !isset( $payment['cc_card_hashs'] ) || empty( $payment['cc_card_hashs'] || count( $payment['cc_card_hashs'] ) === 0 ) )
        wc_add_notice( __( 'Ocorreu um erro. Aguarde alguns segundos e tente novamente.', $this->id ), 'error' );

      return true;

    }

    public function payment_fields() {
      $configHelper = new Integrai_Model_Config();
      $options = $configHelper->get_payment_creditcard();

      $cart_totals = WC()->session->get('cart_totals');
      $total = $cart_totals['total'] ? $cart_totals['total'] : null;

      $this->get_helper()->get_template(
        'credit-card/payment-form.php',
        array(
          'options' => $options,
          'total' => $total,
        ),
      );
    }

    public function process_payment( $order_id ) {
      if ($_POST['payment_method'] != $this->id)
        return false;

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

    public function thankyou_page( $order_id ) {
      $order        = $this->get_helper()->get_integrai_order( $order_id );
      $configHelper = new Integrai_Model_Config();
      $options      = $configHelper->get_payment_success();

      $this->get_helper()->get_template(
        'credit-card/payment-success.php',
        array(
          'options' => $options,
          'order' => $order,
        ),
      );
    }

    public function update_order_meta( $order_id ) {
      $payment_data = $_POST['payment'];
      $payment_data['payment_method'] = $_POST['payment_method'];

      if ( $_POST['payment_method'] != $this->id || empty( $payment_data ) )
        return;

      // Save data on order
      $this->get_helper()->save_transaction_data( $order_id, $payment_data );

    }

    public function display_admin_order_meta( $order ) {
      $payment_method = get_post_meta( $order->get_id(), '_payment_method', true );

      if ( $payment_method !== $this->id )
        return;

      $data = get_post_meta( $order->get_id(), '_integrai_transaction_data', true );
      $payment_response = (array) get_post_meta( $order->get_id(), 'payment_response', true );

      if (
        isset($data)
        && isset($data['cc_card_brand'])
        && isset($data['cc_doc_type'])
        && isset($data['cc_doc_number'])
        && isset($data['cc_installments'])
      ) {

        $card = isset($payment_response['card']) ? (array) $payment_response['card'] : array();
        $card_number = $card['last_four_digits'];
        $card_installments = $payment_response['installments'] ? $payment_response['installments'] : $data['cc_installments'];
        $card_brand = $card['brand'] ? $card['brand'] : $data['cc_card_brand'];
        $card_holder = $card['holder'];

        $meta_data = array(
          __( 'Pagamento', 'integrai' ) => 'Cartão de Crédito',
          __( 'Processado por', 'integrai' )     => sanitize_text_field($payment_response['module_name']),
          __( 'Identificação da transação', 'integrai' )     => sanitize_text_field($payment_response['transaction_id']),
          __( 'Data de pagamento', 'integrai' )     => sanitize_text_field($payment_response['date_approved']),
          __( 'Número de Parcelas', 'integrai' )  => sanitize_text_field( $card_installments ),
          __( 'Número do cartão', 'integrai' )  => sanitize_text_field( "**** **** **** $card_number" ),
          __( 'Nome do titular', 'integrai' )  => sanitize_text_field( $card_holder ),
          __( 'Bandeira', 'integrai' )  => sanitize_text_field( strtoupper( $card_brand ) ),
          __( 'Documento', 'integrai' )           => sanitize_text_field( strtoupper( $data['cc_doc_type'] ) ),
          __( 'Número do Documento', 'integrai' ) => sanitize_text_field( $data['cc_doc_number'] )
        );

        $this->get_helper()->get_template(
          'credit-card/admin-order-detail.php',
          array(
            'data' => $meta_data,
          ),
        );
      }
    }

    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
      if ( $sent_to_admin || ! in_array( $order->get_status(), array( 'processing', 'on-hold' ), true ) || $this->id !== $order->get_payment_method() ) {
        return;
      }

      $data = get_post_meta( $order->get_id(), '_integrai_transaction_data', true );
      $email_type = $plain_text ? 'plain' : 'html';

      if ( isset($data['card_brand']) && isset($data['installments']) ) {
        $this->get_helper()->get_template(
          'credit-card/emails/' . $email_type . '-instructions.php',
          array(
            'card_brand'   => $data['cc_card_brand'],
            'installments' => $data['cc_installments'],
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
