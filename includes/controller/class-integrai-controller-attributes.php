<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-api.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_Attributes_Controller extends WP_REST_Controller {

  protected $namespace = 'integrai';
  protected $path = 'attributes';

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
        $options = array();
        $attributes = wc_get_attribute_taxonomies();

        foreach ($attributes as $attribute) {
            $options[] = array(
                "label" => $attribute->attribute_label,
                "value" => $attribute->attribute_name,
            );
        }

      $response = new WP_REST_Response($options);
      $response->header( 'Content-type', 'application/json' );
      $response->set_status( 200 );

      return $response;

    } catch (Throwable $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao atualizar configs');
    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao atualizar configs');
    }
  }
}