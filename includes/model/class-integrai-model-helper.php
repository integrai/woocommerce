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

  public function insert_or_update($name = '', $data = array(), $where = array(), $parseJson = true) {
    if ( !isset($name) || !isset($data) ) return false;

    if ( is_null( $this->get_by_name($name, $parseJson) ) ) {
      return $this->insert( array_merge($data, $where, array('updatedAt' => date('Y-m-d H:i:s'), 'createdAt' => date('Y-m-d H:i:s'))) );
    } else {
      return $this->update( array_merge($data, array('updatedAt' => date('Y-m-d H:i:s'))), $where );
    }
  }

  public function insert_many($data = array()) {
    if ( $data && !empty( $data ) ) {
      $ids = array();

      foreach($data as $item) {
        $is_array = is_array( $item );
        $name = $is_array ? $item['name'] : $item->name;
        $values = $is_array ? $item['values'] : $item->values;

        $row = array( 'name' => $name, 'values' => $values );

        $inserted_id = $this->insert( $row );

        array_push($ids, $inserted_id);
      }

      return !empty($ids) ? $ids : false;
    }

    return false;
  }

  public function get($where = '', $all = false) {
    $select = "SELECT * FROM {$this->table}";
    $query = $select . ' ' . $where;
    $action = $all ? 'get_results' : 'get_row';

    if ( !$this->table_exists() ) {
      return false;
    }

    return $this->wpdb->{$action}($query);
  }

  public function get_all() {
    return $this->get('', true);
  }

  public function get_by_name($name, $parseJson = true) {
    $upper_name = strtoupper( $name );
    $raw_data = $this->get("WHERE name = '$upper_name'");

    try {
      return $parseJson ? json_decode( $raw_data->values, 2 ) : $raw_data->values;
    } catch (Throwable $e) {
      Integrai_Helper::log( $e->getMessage() );
    } catch (Exception $e) {
      Integrai_Helper::log( $e->getMessage() );
    }
  }

  public function update(
    $data = array(),
    $where = array(),
    $format = null,
    $where_format = null
  ) {
    try {
      $this->wpdb->update($this->table, $data, $where, $format, $where_format);
    } catch (Throwable $e) {
      Integrai_Helper::log( $e->getMessage() );
    } catch (Exception $e) {
      Integrai_Helper::log( $e->getMessage() );
    }

    return $this->wpdb->insert_id;
  }

  public function update_many($data = array()) {
    if ( $data && !empty( $data ) ) {
      $ids = array();

      foreach( $data as $item ) {
        $is_array = is_array( $item );
        $name = $is_array ? $item['name'] : $item->name;
        $values = $is_array ? $item['values'] : $item->values;

        $row = array( 'name' => $name, 'values' => $values );
        $where = array( 'name' => $name );

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

  public function delete_query($where = '') {
      return $this->wpdb->query("DELETE FROM $this->table WHERE $where");
  }

  public function delete($where = array(), $where_format = null) {
    return $this->wpdb->delete($this->table, $where, $where_format);
  }

  public function delete_by_id($id) {
    return $this->wpdb->delete($this->table, array('id' => $id));
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