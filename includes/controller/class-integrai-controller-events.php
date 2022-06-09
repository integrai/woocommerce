<?php

class Integrai_Events_Controller extends WP_REST_Controller {

  protected $namespace = 'integrai';
  protected $path = 'event';

  public function get_process_events() {
    return new Integrai_Model_Process_Events();
  }

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

        $body = json_decode($request->get_body());
        $eventId = $body->eventId;
        $event = $body->event;
        $payload = $body->payload;
        $isSync = (bool)$body->isSync;

        if ($isSync) {
            Integrai_Helper::log($event, 'Executando evento');

            $processEvent = new Integrai_Process_Event();
            $response = $processEvent->process($payload);

            if(!$response) return null;

            $response = new WP_REST_Response($response);
            $response->header( 'Content-type', 'application/json' );
            $response->set_status( 200 );

            return $response;
        } else {
            Integrai_Helper::log($event, 'Salvando o evento');

            $data = array(
                'event_id' => $eventId,
                'event' => $event,
                'payload' => json_encode($payload),
                'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
            );
            $processEventsModel = $this->get_process_events();
            $processEventsModel->save_events($data);
        }

      $response = new WP_REST_Response( array( "ok" => true ) );
      $response->header( 'Content-type', 'application/json' );
      $response->set_status( 200 );

      return $response;
    } catch (Throwable $e) {
        Integrai_Helper::log($e->getMessage(), 'Error ao solicitar eventos');

        $response = new WP_REST_Response( array(
            "ok" => false,
            "error" => $e->getMessage()
        ) );
        $response->header( 'Content-type', 'application/json' );
        $response->set_status( 500 );

        return $response;
    }
  }
}