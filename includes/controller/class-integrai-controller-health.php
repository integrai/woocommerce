<?php
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-api.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_Health_Controller extends WP_REST_Controller {

  protected $namespace = 'integrai';
  protected $path = 'health';

  public function register_routes() {
    register_rest_route( $this->namespace, '/' . $this->path, [
      array(
        'methods'  => 'GET',
        'callback' => array( $this, 'get_info' ),
        'permission_callback' => '__return_true'
      ),
    ]);
  }

    private function get_config_helper() {
        return new Integrai_Model_Config();
    }

  public function get_info( $request ) {
    try {
        $woocommerceVersion = WC_VERSION;
        $moduleVersion = INTEGRAI_VERSION;

        $isRunningEventProcess = $this->get_config_helper()->get_by_name('PROCESS_EVENTS_RUNNING', false);

        $ProcessEventsModel = new Integrai_Model_Process_Events();
        $processEvents = $ProcessEventsModel->get(null, true);
        $totalEventsToProcess = count($processEvents);

        $eventsModel = new Integrai_Model_Events();
        $events = $eventsModel->get(null, true);
        $totalUnsentEvent = count($events);

        $data = array(
            'phpVersion' => phpversion(),
            'platform' => 'woocommerce',
            'platformVersion' => $woocommerceVersion,
            'moduleVersion' => $moduleVersion,
            'isRunningEventProcess' => $isRunningEventProcess === 'RUNNING',
            'totalEventsToProcess' => $totalEventsToProcess,
            'totalUnsentEvent' => $totalUnsentEvent
        );

        $api = new Integrai_API();
        $api->request(
            '/store/health',
            'POST',
            $data,
        );

      return new WP_REST_Response(
        array(
            "ok" => true
        ),
        200,
        array('Content-type', 'application/json'),
      );
    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Error health');

      return new WP_REST_Response(
        array(
          "ok" => false,
          "error" => $e->getMessage()
        ),
        500,
        array( 'Content-type', 'application/json' ),
      );
    }
  }
}