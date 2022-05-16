<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once ABSPATH . 'wp-admin/includes/upgrade.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Integrai_Shipping_Helper' ) ) :

  class Integrai_Shipping_Helper {

    const QUOTE = 'QUOTE';

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

		  return $options['enabled'];
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
      if ( $this->is_enabled() ) {
        try {

          $quote_order = $this->transform_order($order);

          $response = $this->get_api_helper()->send_event(self::QUOTE, $quote_order);

          return $this->transform_response($response);

        } catch(Throwable $e) {
          Integrai_Helper::log($e, 'QUOTE :: ERROR: ');
        } catch(Exception $e) {
          Integrai_Helper::log($e, 'QUOTE :: ERROR: ');
        }
      }
    }

    private function transform_response($response) {
      $body = json_decode( $response['body'] );
      $result = reset( $body );

      if (!$result || !is_object( $result )) {
        return false;
      }

      $titleList = explode(" _ ", $result->methodTitle);
      $logistic_provider = $titleList[0];
      $delivery_deadline = isset($titleList[1]) ? $titleList[1] : null;
      $label = $logistic_provider && $delivery_deadline
        ? "$logistic_provider - $delivery_deadline"
        : $logistic_provider;

      return array(
        'id' => $result->carrierCode,
        'label' => $label,
        'cost' => $result->cost,
        'calc_tax' => $result->price,
        'meta_data' => array(
          'code' => $result->methodCode,
          'description' => $result->methodDescription,
          'carrier_title' => $result->carrierTitle,
        )
      );
    }

    private function transform_order($order) {
      $items = $order['contents'];
      $destination_zipcode = preg_replace('/[^0-9]/', '', $order['destination']['postcode']);
      $cart_total_price = $order['cart_subtotal'];

      $quote_order = array(
        'destinationZipCode' => $destination_zipcode,
        'cartTotalPrice'    => $cart_total_price,
        "cartTotalQuantity" => WC()->cart->get_cart_contents_count(),
        "cartTotalWeight"   => WC()->cart->get_cart_contents_weight(),
        'items'               => $this->transform_items($items),
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
            "weight"     => (float) number_format((float)$product_data->get_weight(), 3),
            "width"      => (float) number_format((float)$width, 2),
            "height"     => (float) number_format((float)$height, 2),
            "length"     => (float) number_format((float)$length, 2),
            "quantity"   => (int) max(1, $quantity),
            "sku"        => (string) $product_data->get_sku(),
            "unit_price" => (float) $product_data->get_sale_price(),
            "product"    => (object) wc_get_product( $item['product_id'] ),
          )
        );
      }

      return $transformed_items;
    }

    private function get_value_or_default($attr, $data) {
        $shipping_config = $this->get_config_helper()->get_shipping();
        $default = isset($shipping_config["{$attr}_default"]) ? $shipping_config["{$attr}_default"] : '';
        return $data->{"get_$attr"}() ?: $default;
    }

    private function valide_item($item) {
      return TRUE;
    }
  }

endif;