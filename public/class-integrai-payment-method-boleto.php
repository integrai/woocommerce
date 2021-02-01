<?php

if ( class_exists( 'WC_Payment_Gateway' ) ) :
  class Integrai_Payment_Method_Boleto extends WC_Payment_Gateway {

    public function __construct() {
      $this->id                 = 'integrai_boleto';
      $this->has_fields         = true;
      $this->icon 	            = apply_filters('woocommerce_custom_gateway_icon', '');
      $this->title              = __( 'Integrai', 'woocommerce' );
      $this->method_title       = __( 'Integrai', 'woocommerce' );  // Title shown in admin
      $this->method_description = __( 'Método de pagamento da Integrai. Permite fazer pagamento via Boleto.', 'woocommerce' );  // Title shown in admin


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

    public function validate_fields() {
      $payment          = $_POST['payment'];
      $payment_method   = $_POST['payment_method'];

      $doc_type         = $payment['boleto_doc_type'];
      $first_name       = $payment['boleto_first_name'];
      $last_name        = $payment['boleto_last_name'];
      $company_name     = $payment['boleto_company_name'];
      $doc_number       = $payment['boleto_doc_number'];
      $address_street   = $payment['boleto_address_street'];
      $address_zipcode  = $payment['boleto_address_zipcode'];
      $address_number   = $payment['boleto_address_number'];
      $address_city     = $payment['boleto_address_city'];
      $address_state    = $payment['boleto_address_state'];

      if ( $payment_method !== $this->id )
        return true;

      if( !isset( $doc_number ) || empty( $doc_number ) )
        wc_add_notice( __( 'Informe o número do documento (CPF / CNPJ)', $this->id ), 'error' );

      if( !isset( $address_street ) || empty( $address_street ) )
        wc_add_notice( __( 'Informe um endereço', $this->id ), 'error' );

      if( !isset( $address_zipcode ) || empty( $address_zipcode ) )
        wc_add_notice( __( 'Informe o CEP', $this->id ), 'error' );

      if( !isset( $address_number ) || empty( $address_number ) )
        wc_add_notice( __( 'Informe o número do endereço', $this->id ), 'error' );

      if( !isset( $address_city ) || empty( $address_city ) )
        wc_add_notice( __( 'Informe a cidade', $this->id ), 'error' );

      if( !isset( $address_state ) || empty( $address_state ) )
        wc_add_notice( __( 'Informe o estado', $this->id ), 'error' );

      if( !isset( $doc_type ) || empty( $doc_type ) )
        wc_add_notice( __( 'Informe o Tipo do documento (CPF / CNPJ)', $this->id ), 'error' );

      // Person
      if ( $doc_type === 'cpf' ) {
        if( !isset( $first_name ) || empty( $first_name ) )
          wc_add_notice( __( 'Informe o Primeiro nome', $this->id ), 'error' );

        if( !isset( $last_name ) || empty( $last_name ) )
          wc_add_notice( __( 'Informe o sobrenome', $this->id ), 'error' );
      }

      // Company
      if ( $doc_type === 'cnpj' ) {
        if( !isset( $company_name ) || empty( $company_name ) )
          wc_add_notice( __( 'Informe o nome da empresa', $this->id ), 'error' );
      }

      // Validate DOCUMENT:
      if ( $doc_type === 'cpf' || $doc_type === 'cnpj' ) {
        $is_valid = Integrai_Validator::{$doc_type}( $doc_number );

        if ( !$is_valid )
          wc_add_notice( __( 'Número de ' . strtoupper($doc_type) . ' inválido', $this->id ), 'error' );
      }

      return true;

    }

    public function payment_fields() {
      $configHelper = new Integrai_Model_Config();
      $options  = $configHelper->get_payment_boleto();
      $customer = $this->get_helper()->get_integrai_customer( WC()->session->get_customer_id() );

      $this->get_helper()->get_template(
        'boleto/payment-form.php',
        array(
           'options' => $options,
           'customer' => $customer,
        ),
      );
    }

    public function process_payment( $order_id ) {
      if ($_POST['payment_method'] != $this->id)
        return false;

      global $woocommerce;
      $order = new WC_Order( $order_id );

      // Mark as on-hold (we're awaiting the cheque)
      $order->update_status('on-hold', __( 'Integrai: Aguardando pagamento do boleto', 'woocommerce' ));

      // Remove cart
      $woocommerce->cart->empty_cart();

      // Return thankyou redirect
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
        'boleto/payment-success.php',
        array(
          'options' => $options,
          'order' => $order,
        ),
      );
    }

    public function update_order_meta( $order_id ) {
      $payment          = $_POST['payment'];
      $payment_method   = $_POST['payment_method'];

      if ( $payment_method != $this->id || empty( $payment ) )
        return;

      $payment_data = array_map(
        'sanitize_text_field',
        array(
          'payment_method'         => $payment_method,
          'boleto_doc_type'        => $payment['boleto_doc_type'],
          'boleto_first_name'      => $payment['boleto_first_name'],
          'boleto_last_name'       => $payment['boleto_last_name'],
          'boleto_company_name'    => $payment['boleto_company_name'],
          'boleto_doc_number'      => $payment['boleto_doc_number'],
          'boleto_address_street'  => $payment['boleto_address_street'],
          'boleto_address_zipcode' => $payment['boleto_address_zipcode'],
          'boleto_address_number'  => $payment['boleto_address_number'],
          'boleto_address_city'    => $payment['boleto_address_city'],
          'boleto_address_state'   => $payment['boleto_address_state'],
          'boleto_custom_hidden'   => $payment['boleto_custom_hidden'],
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

      $doc_type   = get_post_meta( $order->id, 'boleto_doc_type',       true );
      $doc_number = get_post_meta( $order->id, 'boleto_doc_number',     true );

      $boleto_url = get_rest_url(
        null,
        'integrai/v1/boleto&order_id=' . $order->get_order_number() . '&duplicated=true',
      );

      // Update meta data title
      $meta_data = array(
        __( 'Pagamento', 'integrai' )           => 'Boleto (Integrai)',
        __( 'Documento', 'integrai' )           => sanitize_text_field( strtoupper($doc_type) ),
        __( 'Número do Documento', 'integrai' ) => sanitize_text_field( $doc_number ),
      );

      $this->get_helper()->get_template(
        'boleto/admin-order-detail.php',
        array(
          'data' => $meta_data,
          'boleto_url' => $boleto_url,
        ),
      );
    }

    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
      if ( $sent_to_admin || ! in_array( $order->get_status(), array( 'processing', 'on-hold' ), true ) || $this->id !== $order->payment_method ) {
        return;
      }

      $email_type = $plain_text ? 'plain' : 'html';

      $this->get_helper()->get_template(
        'boleto/emails/' . $email_type . '-instructions.php',
        array(
          'url' => get_rest_url(
            null,
            'integrai/v1/boleto&order_id=' . $order->get_order_number(),
          ),
        ),
      );
    }

    private function get_helper() {
        return new Integrai_Payment_Method_Helper( $this->id );
    }
  }
endif;
?>
