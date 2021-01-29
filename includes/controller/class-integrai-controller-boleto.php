<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-api.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_Boleto_Controller extends WP_REST_Controller {

  protected $_models = array();

  public function register_routes() {
    $namespace = 'integrai/v1';
    $path = 'boleto';

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
      $order_id     = strval( trim($_GET['order_id']) );
      $is_duplicate = 'false';

      if ( isset($_GET['is_duplicate']) && $_GET['is_duplicate'] === true ) {
        $is_duplicate = 'true';
      }

      $api = new Integrai_API();
      $response_boleto = $api->request( '/store/boleto', 'GET', null, array(
        'order_id' => $order_id,
        'is_duplicate' => $is_duplicate
      ) );

      // Create the response object
      $response = new WP_REST_Response( array(
        'boleto_url' => isset( $response_boleto['boleto_url'] )
          ? $response_boleto['boleto_url']
          : null,
      ));
      $response->header( 'Content-type', 'application/json' );
      $response->set_status( 201 );

      return $response;

    } catch (Exception $e) {

      // Create the response object
      $response = new WP_REST_Response( array(
        "ok" => false,
        "error" => $e->getMessage()
      ));

      $response->header( 'Content-type', 'application/json' );
      $response->set_status( 400 );

      wp_redirect( '/' );
      exit;

    }
  }
}