<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-api.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

/**
 * 1. Mudar controler para endpoints
 * 2. Migrar logica para os models
 * 3. Start com valores de config defaults
 * 4. Configurar endpoint de config, para solicitar as configs reais
 * 5. Setar API_KEY e SECRET_KEY nas configs do WP Woocommerce
 */

class Integrai_Controller_Config {
  static public function index() {
    try {
      $api = new Integrai_API();
      $response = $api->request('/config');
      $configs = json_decode( $response['body'] );

      $integrai_config = new Integrai_Model_Config();
      $integrai_config->update_config( $configs );

      // Create the response object
      $response = new WP_REST_Response( array( "ok" => true ) );
      $response->set_status( 201 );
      $response->header( 'Content-type', 'application/json' );

      return $response;

    } catch (Exception $e) {

      Integrai_Helper::log('Error ao atualizar configs', $e->getMessage());

      // Redirect para "/"
    }
  }
}