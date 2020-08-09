<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_Controller_Config {
  static public function create_table() {
    $created = Integrai_Model_Config::create();

    $data = self::get_fom_remote();

    Integrai_Helper::log($created, '$created => ');

    $action = self::config_exists() ? 'update_many' : 'insert_many';

    $ids = Integrai_Model_Config::{$action}($data);

    return $ids;
  }

  static public function check_if_exists($name = '') {
    $row = Integrai_Model_Config::get_by_name($name);

    return !is_null( $row );
  }

  static public function config_exists() {
    $table_exists = Integrai_Model_Config::table_exists();

    Integrai_Helper::log($table_exists, '$table_exists: ');

    if ( Integrai_Model_Config::table_exists() ) {
      $configs = array(
        'EVENTS_ENABLED',
        'SHIPPING',
        'GLOBAL',
      );

      $count = 0;
      foreach( $configs as $item ) {
        if (self::check_if_exists($item)) {
          $count++;
        }
      }

      return count( $configs ) === $count;
    }

    return false;
  }

  static public function get_fom_remote() {
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