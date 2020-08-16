<?php

class Integrai_API {
  private $api_key;
  private $secret_key;
  private $config_model;
  private $events_model;

  protected $api_url;
  protected $timeout;

  public function __construct() {
    $this->load_depedencies();

    $options = get_option('woocommerce_integrai-settings_settings');

    $this->config_model = new Integrai_Model_Config();
    $this->events_model = new Integrai_Model_Events();
    $this->api_url = $this->get_api_url();
    $this->timeout = $this->get_api_timeout();
    $this->api_key = $options['api_key'];
    $this->secret_key = $options['secret_key'];
    $this->secret_key = $this->config_model->get_secret_key();
  }

  private function load_depedencies() {
    if ( ! class_exists( 'Integrai_Helper' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
		endif;

		if ( ! class_exists( 'Integrai_Model_Config' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . '/includes/model/class-integrai-model-config.php';
		endif;

		if ( ! class_exists( 'Integrai_Model_Events' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . '/includes/model/class-integrai-model-events.php';
		endif;
  }

  public function get_api_url() {
    return $this->config_model->get_api_url();
  }

  public function get_api_timeout() {
    return $this->config_model->get_api_timeout_seconds();
  }

  public function request($endpoint, $method = 'GET', $body = array()) {
    $api_url = $this->get_api_url();
    $timeout = $this->get_api_timeout();
    $headers = $this->get_headers();

    $options = array(
      'method' => $method,
      'headers' => $headers,
      'timeout' => $timeout,
    );

    $response = wp_remote_request($api_url . $endpoint, $options);

    if ( is_wp_error( $response ) ) {
      Integrai_Helper::log($response, 'API REQUEST :: ERROR: ');

      throw new Exception( $response->get_error_message() );
    }

    $body = wp_remote_retrieve_body( $response );
    $http_code = wp_remote_retrieve_response_code( $response );

    return $response;
  }

  public function send_event( $event_name, $payload, $resend = false ) {
    Integrai_Helper::log(array(
      'event_name' => $event_name,
      'payload' => $payload,
    ), 'HOOKS :: SEND_EVENT: ');

    try {

      $response = $this->request('/event/woocommerce', 'POST', array(
        'event' => $event_name,
        'payload' => $payload,
      ));

      Integrai_Helper::log($event_name, 'HOOKS :: SUCCESS: ');

      return $response;

    } catch (Exception $e) {

      if(!$resend) {
        $this->_backup_event($event_name, $payload);
      } else {
        throw new Exception($e);
      }

    }
  }

  private function _backup_event( $event_name, $payload ) {
    Integrai_Helper::log($event_name, 'HOOKS :: BACKUP_EVENT: ');

    $data = array(
      'event' => $event_name,
      'payload' => json_encode($payload),
      'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
    );

    return $this->events_model->insert( $data );
  }

  private function get_headers() {
    global $wp_version, $woocommerce;

    $wc_version = $woocommerce->version;
    $plugin_version = INTEGRAI_VERSION;
    $token = base64_encode("{$this->api_key}:{$this->secret_key}");

    return array(
        "Content-Type: application/json",
        "Accept: application/json",
        "Authorization: Bearer {$token}",
        "x-integrai-plaform: wordpress",
        "x-integrai-plaform-version: {$wp_version}",
        "x-integrai-plaform-framework: woocommerce {$wc_version}",
        "x-integrai-module-version: {$plugin_version}",
    );
  }
}