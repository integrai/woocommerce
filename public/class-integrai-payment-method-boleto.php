<?php

if ( class_exists( 'WC_Payment_Gateway' ) ) :
  class Integrai_Payment_Method_Boleto extends WC_Payment_Gateway {

    public function __construct() {
      $this->id                 = 'integrai_boleto';
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

    /**
     * Prints the form fields
     *
     * @access public
     * @return void
     */
    public function payment_fields() {
      $configHelper = new Integrai_Model_Config();
      $options = $configHelper->get_payment_boleto();
      $wcCustomer = new WC_Customer( WC()->session->get_customer_id() );
      $customer = $this->get_integrai_customer( $wcCustomer );

      ?>
        <div class="form-list" id="payment_form_integrai-boleto">
            <div id="integrai-payment-boleto"></div>
        </div>

        <script>
            window.integraiBoletoData = JSON.parse('<?php echo json_encode( $options ) ?>');

            window.IntegraiBoleto = Object.assign({}, integraiBoletoData.formOptions, {
                boletoModel: JSON.parse('<?php echo json_encode( $customer ) ?>'),
            });

            console.log(window.IntegraiBoleto)

            integraiBoletoData.scripts.forEach(function (script) {
                let scriptElm = document.createElement('script');
                scriptElm.src = script;

                document.body.appendChild(scriptElm);
            });
        </script>
      <?php
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
      $order        = $this->get_integrai_order( $order_id );
      $configHelper = new Integrai_Model_Config();
      $options      = $configHelper->get_payment_success();

      ?>
        <script>
            const integraiSuccessData = JSON.parse('<?php echo json_encode( $options ) ?>');

            window.IntegraiSuccess = Object.assign({}, integraiSuccessData.pageOptions, {
                order: JSON.parse('<?php echo $order ?>'),
            });

            integraiSuccessData.scripts.forEach(function (script) {
                let scriptElm = document.createElement('script');
                scriptElm.src = script;

                document.body.appendChild(scriptElm);
            });
        </script>

        <ul class="order_details">
            <li>
                <div id="integrai-payment-success"></div>
            </li>
        </ul>
      <?php
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
        __( 'Pagamento', 'integrai' ) => 'Boleto (Integrai)',
        __( 'Documento', 'integrai' )           => sanitize_text_field( strtoupper($doc_type) ),
        __( 'Número do Documento', 'integrai' ) => sanitize_text_field( $doc_number ),
      );

      ?>
        <div class="clear"></div>
        <div class="integrai_payment">
            <h4><?php echo __( 'Método de Pagamento', 'integrai' ) ?></h4>
            <p>
              <?php
                foreach ($meta_data as $key => $value) {
                  echo '<strong>' . $key . ':</strong> ' . $value . '<br />';
                }
              ?>

              <?php
                echo '<strong>' . __( 'Boleto' ) . '</strong>: ';
                echo '<a id="integrai_printBoleto" title="'. __( 'Acessar boleto' ) .'" href="#">' . __( ' clique aqui ' ) . '</a>'
              ?>
            </p>
        </div>

        <script>
            document.querySelector('#integrai_printBoleto').addEventListener('click', function () {
                fetch('<?php echo $boleto_url ?>')
                    .then((response) => response.json())
                    .then((response) => {
                        this.loading = false;
                        window.open(response.boleto_url, '_blank');
                    });
            });
        </script>
      <?php
    }

    private function get_integrai_order( $order_id ) {
      $order = wc_get_order( $order_id );

      return json_encode(array(
        "payment_method"      => $this->id,
        "order_entity_id"     => $order->get_order_number(),
        "order_increment_id"  => $order->get_order_number(),
        "order_link_detail"   => $order->get_view_order_url(),
        "store_url"           => get_home_url(),
        "boleto_url"          => get_rest_url(
            null,
            'integrai/v1/boleto&order_id=' . $order->get_order_number(),
        ),
      ));
    }

    public function get_integrai_customer( $customer ) {
      $id = $customer->get_id();

      $billing = $customer->get_billing();
      $billing_cpf  = get_user_meta($id, 'billing_cpf');
      $billing_cnpj = get_user_meta($id, 'billing_cnpj');
      $billing_number = get_user_meta($id, 'billing_number');
      $billing_company    = get_user_meta($id, 'billing_company');
      $billing_persontype = $this->get_person_type( get_user_meta($id, 'billing_persontype') );

      $doc_number = '';

      if ( isset($billing_persontype) && $billing_persontype === 'cpf' )
        $doc_number = $billing_cpf;

      if ( isset($billing_persontype) && $billing_persontype === 'cnpj' )
        $doc_number = $billing_cnpj;

      return array(
        'name'           => $billing['first_name'],
        'lastName'       => $billing['last_name'],
        'docType'        => $billing_persontype,
        'docNumber'      => $doc_number,
        'addressZipCode' => $billing['postcode'],
        'addressStreet'  => $billing['address_1'],
        'addressNumber'  => $billing_number,
        'addressCity'    => $billing['city'],
        'addressState'   => $billing['state'],
        'companyName'    => $billing_company,
      );
    }

    private function get_person_type( $persontype ) {
      if ( !isset($person_type) || empty($person_type) ) {
          return 'cpf';
      }

      return $persontype[0] == '1' ? 'cpf' : 'cnpj';
    }
  }
endif;
?>
