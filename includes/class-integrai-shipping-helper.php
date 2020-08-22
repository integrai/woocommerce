<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once ABSPATH . 'wp-admin/includes/upgrade.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Integrai_Shipping_Helper' ) ) :

  class Integrai_Shipping_Helper {

    public function __construct() {
      $this->load_dependencies();
    }

    private function get_api_helper() {
      return new Integrai_API();
    }

    private function get_config_helper() {
      return new Integrai_Model_Config();
    }

    private function get_default_config_keys() {
      $config_keys = array_keys( $this->get_config_helper()->get_shipping() );
      $default_keys = array();

      foreach ($config_keys as $key) {
        if ( strpos($key, 'default') ) {
          array_push( $default_keys, str_replace('_default', '', $key) );
        }
      }

      return $default_keys;
    }

    private function is_enabled() {
      $options = get_option('woocommerce_integrai-settings_settings');

		  return $options['enable_integration'];
    }

    private function load_dependencies() {

      if ( ! class_exists( 'Integrai_Helper' ) ) :
        include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
      endif;

      if ( ! class_exists( 'Integrai_API' ) ) :
        include_once INTEGRAI__PLUGIN_DIR . '/includes/class-integrai-api.php';
      endif;

      if ( ! class_exists( 'Integrai_Model_Config' ) ) :
        include_once INTEGRAI__PLUGIN_DIR . '/includes/model/class-integrai-model-config.php';
      endif;

    }

    public function quote($order) {
      /***
       * 1. [ok] Verificar se a Integrai está ativa
       * 2. [ok] Verificar se os metodos de entrega estão ativos
       * 3. [ok] Pegar os dados do produto e o CEP para fazer a cotação
       * 4. [ok] Preparar os parametros para enviar para a API /shipping/quote
       * 5. Transformar o retorno da API para exibir no resultado da cotação
       * 6. Tratar erro
      */

      if ( $this->is_enabled() ) {
        try {

          $quote_order = $this->transform_order($order);

          $response = $this->get_api_helper()->request('/shipping/quote', 'POST', $quote_order);

          return $this->transform_response($response);

        } catch(Exception $e) {
          Integrai_Helper::log($e, 'QUOTE :: ERROR: ');
        }
        }

    }

    private function transform_response($response) {
      $body = json_decode( $response['body'] );

      return array(
        'id' => $body['carrierCode'],
        'label' => $body['methodTitle'],
        'cost' => $body['cost'],
        'calc_tax' => $body['price'],
        'meta_data' => array(
          'code' => $body['methodCode'],
          'description' => $body['methodDescription'],
          'carrier_title' => $body['carrierTitle'],
         )
      );
    }

    private function transform_order($order) {
      $items = $order['contents'];
      $destination_zipcode = $order['destination']['postcode'];
      $cart_total_price = $order['cart_subtotal'];

      $quote_order = array(
        'destination_zipcode' => $destination_zipcode,
        'cart_total_price' => $cart_total_price,
        'cart_total_quantity' => $this->get_total('quantity', $items),
        'cart_total_weight' => $this->get_total('weight', $items),
        'cart_total_height' => $this->get_total('height', $items),
        'cart_total_width' => $this->get_total('width', $items),
        'cart_total_length' => $this->get_total('length', $items),
        'items' => $this->transform_items($items),
      );

      return $quote_order;
    }

    private function transform_items($items = array(), $order = array()) {
      $transformed_items = array();

      foreach ($items as $item) {
        if (!$this->valide_item($item)) {
          continue;
        }

        $quantity = $item['quantity'];
        $product_data = $item['data'];

        $width = $this->get_value_or_default('width', $product_data);
        $height = $this->get_value_or_default('height', $product_data);
        $length = $this->get_value_or_default('length', $product_data);

        array_push($transformed_items,
          array(
            "weight" => (float) $product_data->get_weight(),
            "width" =>  (float) $width,
            "height" => (float) $height,
            "length" => (float) $length,
            "quantity" => (int) max(1, $quantity),
            "sku" => (string) $product_data->get_sku(),
            "unit_price" => (float) $product_data->get_sale_price(),
            "product" => (object) wc_get_product( $item['product_id'] ),
          )
        );
      }

      return $transformed_items;
    }

    private function get_total($attr, $items) {
      $data_items = array(
        'price',
        'weight',
        'length',
        'width',
        'height',
      );

      $accumulator = 0;

      foreach ($items as $item) {
        $data = $item['data'];

        if ( in_array( $attr, $data_items ) ) {
          $value = in_array($attr, $this->get_default_config_keys())
            ? $this->get_value_or_default($attr, $data)
            : $data->{"get_$attr"}();

          $accumulator = $accumulator + (float) $value;
        } else {
          $accumulator = $accumulator + $item[$attr];
        }
      }

      return $accumulator;
    }

    private function get_value_or_default($attr, $data) {
      $shipping_config = $this->get_config_helper()->get_shipping();

      return $data->{"get_$attr"}() ?: $shipping_config["{$attr}_default"];
    }

    private function valide_item($item) {
      return TRUE;
    }
  }

endif;