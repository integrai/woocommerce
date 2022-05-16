<?php
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-api.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_Pix_Controller extends WP_REST_Controller {

  protected $namespace = 'integrai';
  protected $path = 'pix';

  const PIX = 'PIX';

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

      Integrai_Helper::log($orderId, 'Buscando pix url do pedido: ');

      $api = new Integrai_API();
      $response = $api->send_event( self::PIX, array(
        'orderId'     => $orderId,
      ), false, true);
      $body = json_decode( $response['body'], true );

      return new WP_REST_Response(
        $body,
        200,
        array('Content-type', 'application/json'),
      );
    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao buscar pix');

      return new WP_REST_Response(
        array(
          "qrCode" => null,
          "qrCodeBase64" => null,
          "error" => $e->getMessage()
        ),
        404,
        array( 'Content-type', 'application/json' ),
      );
    }
  }
}