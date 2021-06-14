<?php
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-api.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_Boleto_Controller extends WP_REST_Controller {

  protected $namespace = 'integrai';
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
      $orderId = isset($_GET['orderId']) ? sanitize_text_field( strval( trim($_GET['orderId']) ) ) : '';
      $isDuplicate = isset($_GET['isDuplicate']) ? sanitize_text_field( strval( trim($_GET['isDuplicate']) ) ) : 'false';

      $api = new Integrai_API();
      $response_boleto = $api->request( '/store/boleto', 'GET', null, array(
        'orderId'     => $orderId,
        'IsDuplicate' => $isDuplicate,
      ) );

      $body = json_decode( wp_remote_retrieve_body( $response_boleto ) );
      $url = isset($body) && isset($body->boletoUrl) ? $body->boletoUrl : false;

      Integrai_Helper::log($url, '$url: ');

      // Pq não redireciona quando eu dou um throw new error?
      if (!$url) {
        throw new Exception('Boleto não encontrado');
      } else {
        new WP_REST_Response(
          array( "ok" => true ),
          201,
          array('Content-type', 'application/json'),
        );

        wp_redirect( $url );
        exit;
      }

    } catch (Exception $e) {
      new WP_REST_Response(
        array(
          "ok" => false,
          "error" => $e->getMessage()
        ),
        404,
        array( 'Content-type', 'application/json' ),
      );

      wp_redirect( '/' );
      exit;
    }
  }
}