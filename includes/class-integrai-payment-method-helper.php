<?php

if (! class_exists( 'Integrai_Payment_Method_Helper' )) {
  class Integrai_Payment_Method_Helper {
    private $payment_method = null;
    private $meta_transaction_key = '_integrai_transaction_data';

    public function __construct( $payment_method ) {
      $this->payment_method = $payment_method;
    }

    public function get_integrai_order( $order_id ) {
      $order = wc_get_order( $order_id );

      return array(
        "payment_method"      => $this->payment_method,
        "order_entity_id"     => $order->get_order_number(),
        "order_increment_id"  => $order->get_order_number(),
        "order_link_detail"   => $order->get_view_order_url(),
        "store_url"           => get_home_url(),
        "boleto_url"          => get_rest_url(
          null,
          'integrai/v1/boleto&order_id=' . $order->get_order_number(),
        ),
      );
    }

    public function get_person_type( $person_type ): string
    {
      if ( !isset($person_type) || empty($person_type) ) {
        return 'cpf';
      }

      return $person_type[0] == '1' ? 'cpf' : 'cnpj';
    }

    public function get_integrai_customer( $customer_id ) {
      $customer = new WC_Customer( $customer_id );
      $billing  = $customer->get_billing();

      $billing_cpf        = get_user_meta($customer_id, 'billing_cpf');
      $billing_cnpj       = get_user_meta($customer_id, 'billing_cnpj');
      $billing_number     = get_user_meta($customer_id, 'billing_number');
      $billing_company    = get_user_meta($customer_id, 'billing_company');
      $billing_persontype = $this->get_person_type( get_user_meta($customer_id, 'billing_persontype') );

      $doc_number = ( isset($billing_persontype) && $billing_persontype === 'cpf' )
        ? $billing_cpf
        : $billing_cnpj;

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

    public function get_template_path() {
      return INTEGRAI__PLUGIN_DIR . '/includes/templates/';
    }

    public function get_template($name = '', $data = array()) {
      wc_get_template(
        $name,
        $data,
        'woocommerce/integrai',
        $this->get_template_path(),
      );
    }

    public function save_transaction_data( $order_id, $payment_data = array() ) {
      $sanitized_data = array_map( 'sanitize_text_field', $payment_data );

      return update_post_meta( $order_id, $this->meta_transaction_key, $sanitized_data );
    }

    public function get_transaction_data( $order_id ) {
      return get_post_meta( $order_id, $this->meta_transaction_key, true );
    }
  }
}

?>