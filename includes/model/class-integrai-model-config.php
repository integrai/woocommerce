<?php
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-helper.php';

class Integrai_Model_Config extends Integrai_Model_Helper {
  public function __construct() {
    parent::__construct('integrai_config');
  }

  public function setup() {
    $created = $this->create_table();

    $data = $this->get_default_config();

    $action = $this->config_exists() ? 'update_many' : 'insert_many';

    $ids = $this->{$action}($data);

    return $ids;
  }

  public function update_config($data) {
    if ( !$data || empty( $data ) ) return false;

    $table_exists = $this->table_exists();
    $config_exists = $this->config_exists();
    $action = $config_exists ? 'update_many' : 'insert_many';

    return $table_exists ? $this->{$action}($data) : false;
  }

  public function create_table() {
    $sql = "
      CREATE TABLE IF NOT EXISTS `{$this->prefix}integrai_config` (
        id int(10) unsigned NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        `values` text NOT NULL,
        created_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        updated_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
      ) $this->collate;
    ";

    return $this->run_query( $sql );
  }

  public function get_enabled_events() {
    return $this->get_by_name('events_enabled');
  }

  public function event_is_enabled($name = '') {
    if ( !$name ) {
      return false;
    }

    $events = $this->get_enabled_events();

    return in_array( $name, $events );
  }

  public function get_global() {
    return $this->get_by_name('global');
  }

  public function get_api_url() {
    $configs = $this->get_global();

    return isset( $configs['api_url'] ) ? $configs['api_url'] : false;
  }

  public function get_api_key() {
    $configs = $this->get_global();

    return isset( $configs['api_key'] ) ? $configs['api_key'] : false;
  }

  public function get_secret_key() {
    $configs = $this->get_global();

    return isset( $configs['secret_key'] ) ? $configs['secret_key'] : false;
  }

  public function get_api_timeout_seconds() {
    $configs = $this->get_global();

    return $configs['api_timeout_seconds'] ? $configs['api_timeout_seconds'] : false;
  }

  public function get_global_config() {
    $configs = $this->get_global();

    return $configs[$name] ? $configs[$name] : false;
  }

  public function get_shipping() {
    return $this->get_by_name('shipping');
  }

  public function check_if_exists($name = '') {
    $row = $this->get_by_name($name);

    return !is_null( $row );
  }

  public function config_exists() {
    $table_exists = $this->table_exists();

    if ( $this->table_exists() ) {
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

  public function get_default_config() {
    return array(
      array(
        'name' => 'EVENTS_ENABLED',
        'values' => '[
          "NEW_CUSTOMER",
          "CUSTOMER_BIRTHDAY",
          "NEWSLETTER_SUBSCRIBER",
          "ADD_PRODUCT_CART",
          "ABANDONED_CART",
          "NEW_ORDER",
          "SAVE_ORDER",
          "CANCEL_ORDER",
          "FINALIZE_CHECKOUT"
        ]',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'GLOBAL',
        'values' => '{
          "minutes_abandoned_cart_lifetime": 60,
          "api_url": "http://host.docker.internal:3000/v1",
          "api_timeout_seconds": 3
        }',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'SHIPPING',
        'values' => '{
          "attribute_width": "width",
          "attribute_height": "height",
          "attribute_length": "length",
          "width_default": 11,
          "height_default": 2,
          "length_default": 16
        }',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
    );
  }

  public function get_from_remote() {
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