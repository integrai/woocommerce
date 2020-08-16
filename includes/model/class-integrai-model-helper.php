<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once ABSPATH . 'wp-admin/includes/upgrade.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Integrai_Model_Helper {
  public $collate;
  public $prefix;
  public $wpdb;
  public $dbDelta;
  public $table;

  public function __construct($table_name) {
    global $wpdb;

    $this->wpdb = $wpdb;
    $this->collate = '';
    $this->prefix = $this->wpdb->prefix;
    $this->table = $this->wpdb->prefix . $table_name;

    if ( $wpdb->has_cap( 'collation' ) ) {
      $this->collate = $wpdb->get_charset_collate();
    }
  }

  public function run_query($query = '') {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    return dbDelta( $query );
  }

  public function insert($data = array()) {
    return $this->wpdb->insert($this->table, $data);
  }

  public function insert_many($data = array()) {
    if ( $data && !empty( $data ) ) {
      $ids = array();

      foreach($data as $item) {
        $inserted_id = $this->insert(
          array(
            'name' => $item['name'],
            'values' => $item['values'],
          )
        );

        array_push($ids, $inserted_id);
      }

      return !empty($ids) ? $ids : false;
    }

    return false;
  }

  public function get($where = '') {
    $select = "SELECT * FROM {$this->table}";
    $query = $select . ' ' . $where;

    if ( !$this->table_exists() ) {
      return false;
    }

    return $this->wpdb->get_row($query);
  }

  public function get_all() {
    return $this->get();
  }

  public function get_by_name($name) {
    $lower_name = strtolower( $name );
    $raw_data = $this->get("WHERE name = '$lower_name'");

    try {

      return json_decode( $raw_data->values, 2 );

    } catch (Exception $e) {
      Integrai_Helper::log( $e.getMessage() );
    }
  }

  public function update(
    $data = array(),
    $where = array(),
    $format = null,
    $where_format = null
  ) {
    $this->wpdb->update($this->table, $data, $where, $format, $where_format);

    return $this->wpdb->insert_id;
  }

  public function update_many($data = array()) {
    if ( $data && !empty( $data ) ) {
      $ids = array();

      foreach( $data as $item ) {
        $row = array(
          'name' => $item->name,
          'values' => $item->values,
        );

        $where = array( 'name' => $item->name );

        $updated_id = $this->update( $row, $where );

        array_push($ids, $updated_id);
      }

      return !empty($ids) ? $ids : false;
    }

    return false;
  }

  public function update_by_name($name, $data) {
    return $this->update($data, "WHERE name = '$name'");
  }

  public function delete($where, $where_format = null) {
    return $this->wpdb->delete($this->table, $where, $where_format);
  }

  public function delete_by_name($name) {
    return $this->delete("WHERE name = '$name'");
  }

  public function delete_all() {
    return $this->delete();
  }

  public function table_exists() {
    return $this->wpdb->query("SHOW TABLES LIKE '$this->table'") === 1;
  }
}