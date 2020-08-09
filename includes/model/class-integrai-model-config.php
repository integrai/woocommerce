<?php
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';

class Integrai_Model_Config {
  static public function create() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    global $wpdb;

    $collate = '';

    if ( $wpdb->has_cap( 'collation' ) ) {
      $collate = $wpdb->get_charset_collate();
    }

    $sql = "
      CREATE TABLE `{$wpdb->prefix}integrai_config` (
        id int(10) unsigned NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        `values` text NOT NULL,
        created_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        updated_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
      ) $collate;

      CREATE TABLE `{$wpdb->prefix}integrai_events` (
        id int(10) unsigned NOT NULL AUTO_INCREMENT,
        event text NOT NULL,
        payload text NOT NULL,
        created_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
      ) $collate;
    ";

    global $wpdb;

    // $wpdb->hide_errors();
    Integrai_Helper::log('CREATED TABLE');

    return dbDelta( $sql );
  }

  static public function insert($data = array()) {
    global $wpdb;
    $table = $wpdb->prefix . 'integrai_config';

    return $wpdb->insert($table, $data);
  }

  static public function insert_many($data = array()) {
    Integrai_Helper::log('INSERT_MANY');

    if ( $data ) {
      $ids = array();

      foreach($data as $item) {
        $inserted_id = self::insert( array(
          'name' => $item->name,
          'values' => json_encode( $item->values ),
        ) );

        array_push($ids, $inserted_id);
      }

      return !empty($ids) ? $ids : false;
    }

    return false;
  }

  static public function get($where = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'integrai_config';

    $select = "SELECT * FROM {$table}";
    $query = $select . ' ' . $where;

    if ( !self::table_exists() ) {
      return false;
    }

    return $wpdb->get_row($query);
  }

  static public function get_all() {
    return self::get();
  }

  static public function get_by_name($name) {
    return self::get("WHERE name = '$name'");
  }

  static public function update($data = array(), $where = array(), $format = null, $where_format = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'integrai_config';

    $wpdb->update($table, $data, $where, $format, $where_format);

    return $wpdb->insert_id;
  }

  static public function update_many($data = array()) {
    Integrai_Helper::log('UPDATE_MANY');

    if ( $data ) {
      $ids = array();

      foreach($data as $item) {
        $row = array(
          'name' => $item->name,
          'values' => json_encode( $item->values ),
        );

        $where = array( 'name' => $item->name );

        $updated_id = self::update( $row, $where );

        array_push($ids, $updated_id);
      }

      return !empty($ids) ? $ids : false;
    }

    return false;
  }

  static public function update_by_name($name, $data) {
    return self::update($data, "WHERE name = '$name'");
  }

  static public function delete($where, $where_format = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'integrai_config';

    return $wpdb->delete($table, $where, $where_format);
  }

  static public function delete_by_name($name) {
    return self::delete("WHERE name = '$name'");
  }

  static public function delete_all() {
    return self::delete();
  }

  static public function table_exists() {
    global $wpdb;
    $table = $wpdb->prefix . 'integrai_config';
    $result = $wpdb->query("SHOW TABLES LIKE '$table'");

    Integrai_Helper::log($result, 'result: ');

    return $wpdb->query("SHOW TABLES LIKE '$table'") === 1;
  }
}