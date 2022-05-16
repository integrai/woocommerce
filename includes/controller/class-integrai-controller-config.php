<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-api.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_Config_Controller extends WP_REST_Controller {

  protected $namespace = 'integrai';
  protected $path = 'config';

  public function register_routes() {
    register_rest_route( $this->namespace, '/' . $this->path, [
      array(
        'methods'  => 'POST',
        'callback' => array( $this, 'get_items' ),
        'permission_callback' => '__return_true'
      ),
    ]);
  }

  public function get_items( $request ) {
    try {
        if (!Integrai_Helper::checkAuthorization($request->get_header('Authorization'))) {
            $response = new WP_REST_Response(array("error" => "Unauthorized"));
            $response->header( 'Content-type', 'application/json' );
            $response->set_status( 401 );

            return $response;
        }

      $configs = json_decode($request->get_body());;

      $integrai_config = new Integrai_Model_Config();
      $integrai_config->update_configs( $configs );

      $response = new WP_REST_Response( array( "ok" => true ) );
      $response->header( 'Content-type', 'application/json' );
      $response->set_status( 201 );

      return $response;

    } catch (Throwable $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao atualizar configs');
    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao atualizar configs');
    }
  }
}