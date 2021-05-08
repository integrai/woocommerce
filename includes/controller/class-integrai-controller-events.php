<?php

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
      $batchId = isset($_GET['batchId']) ? trim($_GET['batchId']) : "";
      $response = $api->request(
        '/store/event',
        'GET',
        null,
        array("batchId" => $batchId),
      );

      $events = json_decode( $response['body'] );
      Integrai_Helper::log(count($events), 'Total de eventos carregados: ');

      if ( isset($events) && !empty($events) ) {
        // Pega os IDs do retorno da api
        $eventIds = array_map(function ($event) {
          return $event['_id'];
        }, $events);

        $eventsModel = new Integrai_Model_Events();
        $actualEvents = $eventsModel->get("select * where $eventIds in 'event_id'");
        Integrai_Helper::log($actualEvents, '$actualEvents');

        // Pega os IDs dos eventos que vieram do banco da Loja
        $actualEventIds = array();
        foreach ($actualEvents as $actualEvent) {
          $actualEventIds[] = $actualEvent['event_id'];
        }

        $data = array();
        foreach ($events as $event) {
          Integrai_Helper::log($event, '$event');
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

          Integrai_Helper::log(count($data), 'Total de eventos agendados para processar: ');
        }

        $success = false;

        if (count($data) > 0) {
          $processEventsModel = new Integrai_Model_Process_Events();
          $success = $processEventsModel->insert_batch($data);
        }

        $response = new WP_REST_Response( array( "ok" => $success ) );
        $response->header( 'Content-type', 'application/json' );
        $response->set_status( 200 );

        return $response;
      }

    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao solicitar eventos');
    }
  }
}