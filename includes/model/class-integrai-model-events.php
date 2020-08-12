<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-helper.php';

class Integrai_Model_Events extends Integrai_Model_Helper {
  public function __construct() {
    parent::__construct('integrai_events');
  }

  public function setup() {
    return $this->create_table();
  }

  public function create_table() {
    $sql = "
      CREATE TABLE IF NOT EXISTS `{$this->prefix}integrai_events` (
        id int(10) unsigned NOT NULL AUTO_INCREMENT,
        event text NOT NULL,
        payload text NOT NULL,
        created_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
      ) $this->collate;
    ";

    return $this->run_query( $sql );
  }

  public function event_is_enabled($name = '') {
    if ( !$name ) {
      return false;
    }

    $events = $this->get_events();

    return in_array( $name, $events );
  }

  public function get_fom_remote() {
    $response = wp_remote_get('http://host.docker.internal:3000/v1/config', array(
      'method' => 'GET',
      'headers' => array(
        'Content-Type' => 'application/json'
        )
      )
    );

    $responseBody = wp_remote_retrieve_body( $response );
    $result = json_decode( $responseBody );

    if( is_wp_error( $response ) ) {
      return Integrai_Helper::log($response->get_error_message(), 'ERROR GETTING FROM REMOTE: ');
    }

    return $result;
  }
}