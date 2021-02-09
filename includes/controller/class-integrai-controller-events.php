<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-api.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_Events_Controller extends WP_REST_Controller {

  protected $namespace = 'integrai';
  protected $path = 'event';
  protected $_models = array();

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
      $response = $api->request('/store/event');
      $events = json_decode( $response['body'] );
      $success = [];

      if ( isset($events) && !empty($events) ) {
        foreach ($events as $event) {
          $eventId = $event->_id;
          $payload = $event->payload;

          try {
            foreach ($payload->models as $modelItem) {
              $model = new $modelItem->className(...$this->transformArgs($modelItem->modelArgs));
              $methodItem = $modelItem->methods;

              if ( isset($methodItem->method) && isset($methodItem->args) ) {
                call_user_func_array(array($model, $methodItem->method), $this->transformArgs($methodItem->args));
              }

              $this->_models[$modelItem->name] = $model;
            }

            array_push($success, $eventId);

          } catch (Exception $e) {
            Integrai_Helper::log($event, 'Erro ao processar o evento');
            Integrai_Helper::log($e->getMessage(), 'Erro');
          }
        }

        // Delete events with success
        if(count($success) > 0){
          $api->request('/store/event', 'DELETE', array(
            'event_ids' => $success
          ));
        }

        $response = new WP_REST_Response( array( "ok" => true ) );
        $response->header( 'Content-type', 'application/json' );
        $response->set_status( 201 );

        return $response;
      }

    } catch (Exception $e) {

      Integrai_Helper::log($e->getMessage(), 'Error ao solicitar eventos');

    }
  }


  private function get_other_model($modelName) {
    return $this->_models[$modelName];
  }

  private function transformArgs($args = array()) {
    $newArgs = array();

    foreach($args as $arg) {
      if(is_array($arg) && $arg["otherModelName"]){
        array_push($newArgs, $this->get_other_model($arg["otherModelName"]));
      } else {
        array_push($newArgs, $arg);
      }
    }

    return $newArgs;
  }
}