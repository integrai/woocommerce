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

class Integrai_Config_Controller extends WP_REST_Controller {
  public function register_routes() {
    $namespace = 'integrai/v1';
    $path = 'config';

    register_rest_route( $namespace, '/' . $path, [
      array(
        'methods'  => 'GET',
        'callback' => array( $this, 'get_items' ),
        'permission_callback' => '__return_true'
      ),
    ]);
  }

  public function get_items( $request ) {
    try {
      $api = new Integrai_API();
      $response = $api->request('/config');
      $configs = json_decode( $response['body'] );

      $integrai_config = new Integrai_Model_Config();
      $integrai_config->update_config( $configs );

      // Create the response object
      $response = new WP_REST_Response( array( "ok" => true ) );
      $response->header( 'Content-type', 'application/json' );
      $response->set_status( 201 );

      return $response;

    } catch (Exception $e) {

      Integrai_Helper::log($e->getMessage(), 'Error ao atualizar configs');

    }
  }
}