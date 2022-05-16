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
        if (!Integrai_Helper::checkAuthorization($request->get_header('Authorization'))) {
            $response = new WP_REST_Response(array("error" => "Unauthorized"));
            $response->header( 'Content-type', 'application/json' );
            $response->set_status( 401 );

            return $response;
        }

        $options = array();
        $attributes = wc_get_attribute_taxonomies();

        foreach ($attributes as $attribute) {
            $values = [];

            $terms = get_terms( array('taxonomy' => 'pa_' . $attribute->attribute_name ));
            foreach ($terms as $option) {
                if ($option->name) {
                    $values[] = $option->name;
                }
            }

            $options[] = array(
                "code" => $attribute->attribute_name,
                "label" => $attribute->attribute_label,
                "values" => $values
            );
        }

      $response = new WP_REST_Response($options);
      $response->header( 'Content-type', 'application/json' );
      $response->set_status( 200 );

      return $response;

    } catch (Throwable $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao buscar atributos');
    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao buscar atributos');
    }
  }
}