<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_API {
  protected $config_model;
  protected $api_url;
  protected $timeout;

  public function __construct() {
    $this->config_model = new Integrai_Model_Config();
    $this->api_url = $this->get_api_url();
    $this->timeout = $this->get_api_timeout();
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

      return $response;
    }

    $body = wp_remote_retrieve_body( $response );
    $http_code = wp_remote_retrieve_response_code( $response );

    return $response;
  }

  private function get_headers() {}
}