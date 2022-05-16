<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-api.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_Categories_Controller extends WP_REST_Controller {

  protected $namespace = 'integrai';
  protected $path = 'categories';

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

        $categories = get_categories(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));

      $response = new WP_REST_Response($this->get_categories($categories, 0));
      $response->header( 'Content-type', 'application/json' );
      $response->set_status( 200 );

      return $response;

    } catch (Throwable $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao buscar categorias');
    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao buscar categorias');
    }
  }

  private function get_categories($categories, $parent_id) {
      $options = array();

      foreach ($categories as $category) {
        if($category->category_parent === $parent_id) {
            $item = array(
                'id' => $category->term_id,
                'label' => $category->name,
            );

            $children = $this->get_categories($categories, $category->term_id);
            if(isset($children) && count($children)) {
                $item['children'] = $children;
            }

            $options[] = $item;
        }
      }

    return $options;
  }
}