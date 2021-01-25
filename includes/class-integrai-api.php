<?php

class Integrai_API {
  private $api_key;
  private $secret_key;
  private $events_model;

  protected $api_url;
  protected $timeout;

  public function __construct() {
    $this->load_depedencies();

    // Load Models
    $Config = new Integrai_Model_Config();
    $this->events_model = new Integrai_Model_Events();

    // Load Configs
    $this->api_url      = $Config->get_api_url();
    $this->timeout      = $Config->get_api_timeout_seconds();
    $this->api_key      = $Config->get_api_key();
    $this->secret_key   = $Config->get_secret_key();
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

  public function request($endpoint, $method = 'GET', $body = '') {

    try {
      $body = json_encode($body);
    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao transformar em JSON no request');
    }

    $options = array(
      'method' => $method,
      'headers' => $this->get_headers(),
      'timeout' => $this->timeout,
      'body' => $body,
    );

    $response = wp_remote_request($this->api_url . $endpoint, $options);

    if ( is_wp_error( $response ) ) {
      throw new Exception( $response->get_error_message() );
    }

    $body = wp_remote_retrieve_body( $response );
    $http_code = wp_remote_retrieve_response_code( $response );

    return $response;
  }

  private function get_headers(): array {
    global $wp_version, $woocommerce;

    $wc_version = $woocommerce->version;
    $plugin_version = INTEGRAI_VERSION;
    $token = base64_encode("{$this->api_key}:{$this->secret_key}");

    Integrai_Helper::log($token, '==> $token: ');

    return array(
      "Content-Type" => "application/json; charset=utf-8",
      "Accept" => "application/json",
      "data_format" => "body",
      "Authorization" => "Basic {$token}",
      "x-integrai-plaform" => "wordpress",
      "x-integrai-plaform-version" => "{$wp_version}",
      "x-integrai-plaform-framework" => "woocommerce {$wc_version}",
      "x-integrai-module-version" => "{$plugin_version}",
    );
  }

  public function send_event( $event_name, $payload, $resend = false ) {

    try {

      $response = $this->request('/event/woocommerce', 'POST', array(
        'event' => $event_name,
        'payload' => $payload,
      ));

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

    $data = array(
      'event' => $event_name,
      'payload' => json_encode($payload),
      'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
    );

    return $this->events_model->insert( $data );
  }
}