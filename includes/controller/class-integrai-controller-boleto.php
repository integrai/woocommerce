<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-api.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_Boleto_Controller extends WP_REST_Controller {

  protected $namespace = 'integrai/v1';
  protected $path = 'boleto';

  public function register_routes() {
    register_rest_route( $this->namespace, '/' . $this->path, [
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
      $response_boleto = $api->request( '/store/boleto', 'GET', null, array(
        'order_id'     => strval( trim($_GET['order_id']) ),
        'is_duplicate' => $_GET['is_duplicate']
      ) );

      $body = json_decode( wp_remote_retrieve_body( $response_boleto ) );
      $url = isset($body) && isset($body->boleto_url) ? $body->boleto_url : '/' ;

      $response = new WP_REST_Response( array( "ok" => true ));
      $response->header( 'Content-type', 'application/json' );
      $response->set_status( 201 );

      wp_redirect( $url );
      exit;

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