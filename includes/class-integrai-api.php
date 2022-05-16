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

  public function request($body = array(), $params = array()) {
    try {
      $body = is_null($body) ? array() : json_encode($body);
    } catch (Throwable $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao transformar em JSON no request');
    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao transformar em JSON no request');
    }

    $url = $this->api_url;

    if (isset($params) && is_array($params) && count($params) > 0 && !is_string($params)) {
      $url = $url . '&' . http_build_query($params);
    }

    $options = array(
      'method' => 'POST',
      'headers' => $this->get_headers(),
      'timeout' => $this->timeout,
      'body' => $body,
    );

    Integrai_Helper::log($body, "REQUEST ==> $url: ");

    $response = wp_remote_request($url, $options);

    if ( is_wp_error( $response ) ) {
      throw new Exception( $response->get_error_message() );
    }

    return $response;
  }

  private function get_headers() {
    return array(
      "Content-Type" => "application/json; charset=utf-8",
      "Accept" => "application/json",
      "data_format" => "body",
    );
  }

  public function send_event( $event_name, $payload, $resend = false, $isSync = false ) {

    try {
      return $this->request(array(
        'partnerEvent' => $event_name,
        'payload' => $payload,
      ), array( 'isSync' => $isSync ));
    } catch (Throwable $e) {
      $this->error_handling($e, $resend, $event_name, $payload);
    } catch (Exception $e) {
      $this->error_handling($e, $resend, $event_name, $payload);
    }
  }

  private function error_handling($e, $resend, $event_name, $payload) {
      if (!$resend) {
          $this->_backup_event($event_name, $payload);
      } else {
          throw new Exception($e);
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