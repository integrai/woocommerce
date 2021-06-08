<?php

class Integrai_Events_Controller extends WP_REST_Controller {

  protected $namespace = 'integrai';
  protected $path = 'event';
  protected $_models = array();

  public function get_process_events() {
    return new Integrai_Model_Process_Events();
  }

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
      $batchId = sanitize_text_field($_GET['batchId']);
      $api = new Integrai_API();

      $response = $api->request(
        '/store/event',
        'GET',
        array(),
        array("batchId" => isset($batchId) ? trim($batchId) : ""),
      );

      $events = json_decode( $response['body'] );
      Integrai_Helper::log(count($events), 'Total de eventos carregados: ');

      if ( isset($events) && !empty($events) ) {
        // Pega os IDs do retorno da api
        $eventIds = array_map(function ($event) {
          return $event->_id;
        }, $events);

        $ids = implode(', ', array_map(function ($id) { return "'$id'"; }, $eventIds) );
        $actualEvents = $this->get_process_events()->get("where `event_id` in ($ids)", true);

        // Pega os IDs dos eventos que vieram do banco da Loja
        $actualEventIds = array();
        if (!empty($actualEvents)) {
          foreach ($actualEvents as $actualEvent) {
            $actualEventIds[] = $actualEvent->event_id;
          }
        }

        $data = array();
        foreach ($events as $event) {
          $eventId = $event->_id;
          $payload = $event->payload;

          if (!in_array($eventId, $actualEventIds)) {
            // Formata o evento
            $data[] = array(
              'event_id' => $eventId,
              'event' => $event->event,
              'payload' => json_encode($payload),
              'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
            );
          }
        }

        Integrai_Helper::log(count($data), 'Total de eventos agendados para processar: ');

        if (count($data) > 0) {
          try {
            $processEventsModel = $this->get_process_events();
            $processEventsModel->save_events($data);
          } catch (Exception $e) {
            Integrai_Helper::log($e->getMessage(), 'Error Integrai_Model_Process_Events: ');
            throw new Exception($e);
          }
        }
      }

      $response = new WP_REST_Response( array( "ok" => true ) );
      $response->header( 'Content-type', 'application/json' );
      $response->set_status( 200 );

      return $response;
    } catch (Exception $e) {
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