<?php

if ( class_exists( 'WC_Payment_Gateway' ) ) :
  class Integrai_Payment_Method_Credit_Card extends WC_Payment_Gateway {
    public $fields_list = array();

    public function __construct() {
      $this->id                 = 'integrai_creditcard';
      $this->has_fields         = true;
      $this->icon 	            = apply_filters('woocommerce_custom_gateway_icon', '');
      $this->title              = __( 'Integrai', 'woocommerce' );
      $this->method_title       = __( 'Integrai', 'woocommerce' );
      $this->method_description = __( 'Método de pagamento da Integrai. Permite fazer pagamento com plataformas como MercadoPago, Wirecard, PagarMe.', 'woocommerce' );
      $this->fields_list        = array(
        'cc_card_number',
        'cc_expiration_month',
        'cc_expiration_year',
        'cc_card_cvc',
        'cc_holder_name',
        'cc_doc_type',
        'cc_doc_number',
        'cc_birth_date',
        'cc_installments',
        'cc_installment_amount',
        'cc_installment_total_amount',
        'cc_card_hashs',
        'cc_card_brands',
        'cc_card_brand',
      );

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

      // Custom thankyou page
      add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
      add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 3 );
    }

    private function is_enabled() {
      $configHelper = new Integrai_Model_Config();
      $options  = $configHelper->get_payment_creditcard();

      $formOptions = isset($options) && isset($options['formOptions']) ? $options['formOptions'] : array();
      $gateways = isset($formOptions) && is_array($formOptions) ? $formOptions['gateways'] : array();

      return $configHelper->event_is_enabled('NEW_ORDER') && count($gateways) > 0 ? 'yes' : 'no';
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
      $payment_method = $this->get_helper()->get_sanitized($_POST['payment_method']);
      $payment        = $this->get_helper()->sanitize_fields($this->fields_list, $_POST['payment']);

      if ( $payment_method !== $this->id || !$payment )
        return false;

      $doc_type   = $payment['cc_doc_type'];
      $doc_number = $payment['cc_doc_number'];

      if( !$payment['cc_card_number'] )
        wc_add_notice( __( 'Informe o Número do cartão de crédito.', $this->id ), 'error' );

      if( !$payment['cc_expiration_month'] )
        wc_add_notice( __( 'Informe o mês de expiração do cartão de crédito.', $this->id ), 'error' );

      if( !$payment['cc_expiration_year'] )
        wc_add_notice( __( 'Informe o ano de expiração do cartão de crédito.', $this->id ), 'error' );

      if( !$payment['cc_card_cvc'] )
        wc_add_notice( __( 'Informe o CVC do cartão de crédito.', $this->id ), 'error' );

      if( !$payment['cc_holder_name'] )
        wc_add_notice( __( 'Informe o nome do titular do cartão de crédito.', $this->id ), 'error' );

      if( !$doc_type )
        wc_add_notice( __( 'Selecione o tipo do documento (CPF ou CNPJ).', $this->id ), 'error' );

      if( !$doc_number )
        wc_add_notice( __( 'Informe o documento (CPF ou CNPJ).', $this->id ), 'error' );

      if( !$payment['cc_birth_date'] )
        wc_add_notice( __( 'Informe a data de nascimento.', $this->id ), 'error' );

      if( !$payment['cc_installments'] )
        wc_add_notice( __( 'Selecione o número de parcelas.', $this->id ), 'error' );

      // Validate DOCUMENT:
      if ( $doc_type === 'cpf' || $doc_type === 'cnpj' && isset($doc_number) ) {
        $is_valid = Integrai_Validator::{$doc_type}( $doc_number );

        if ( !$is_valid ) {
          $message = strtoupper('O ' . $doc_type) . ' informado é inválido. Verifique e tente novamente.';
          wc_add_notice( __($message, $this->id), 'error' );
        }
      }

      if( !$payment['cc_card_hashs'] )
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
      $payment_method = $this->get_helper()->get_sanitized('payment_method', $_POST);

      if ($payment_method != $this->id)
        return false;

      $order = wc_get_order( $order_id );
      $order->update_status('on-hold', __( 'Integrai: Transação sendo processada', 'integrai' ));

      global $woocommerce;
      $woocommerce->cart->empty_cart();

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
      $payment_method = $this->get_helper()->get_sanitized('payment_method', $_POST);
      $payment_data   = $this->get_helper()->sanitize_fields($this->fields_list, $_POST['payment']);

      if ( $payment_method != $this->id || empty( $payment_data ) )
        return;

      $payment_data['payment_method'] = $payment_method;

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
        $card_installments = isset($payment_response['installments']) ? $payment_response['installments'] : $data['cc_installments'];
        $card_brand = isset($card['brand']) ? $card['brand'] : $data['cc_card_brand'];
        $card_holder = $card['holder'];

        $meta_data = array(
          __( 'Pagamento', 'integrai' )                  => 'Cartão de Crédito',
          __( 'Processado por', 'integrai' )             => sanitize_text_field($payment_response['module_name']),
          __( 'Identificação da transação', 'integrai' ) => sanitize_text_field($payment_response['transaction_id']),
          __( 'Data de pagamento', 'integrai' )          => sanitize_text_field($payment_response['date_approved']),
          __( 'Número de Parcelas', 'integrai' )         => sanitize_text_field( $card_installments ),
          __( 'Número do cartão', 'integrai' )           => sanitize_text_field( "**** **** **** $card_number" ),
          __( 'Nome do titular', 'integrai' )            => sanitize_text_field( $card_holder ),
          __( 'Bandeira', 'integrai' )                   => sanitize_text_field( strtoupper( $card_brand ) ),
          __( 'Documento', 'integrai' )                  => sanitize_text_field( strtoupper( $data['cc_doc_type'] ) ),
          __( 'Número do Documento', 'integrai' )        => sanitize_text_field( $data['cc_doc_number'] )
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
      $shouldReceiveInstructions = in_array( $order->get_status(), array( 'processing', 'on-hold' ), true );

      if ($sent_to_admin || !$shouldReceiveInstructions || $this->id !== $order->get_payment_method()) {
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
