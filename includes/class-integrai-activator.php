<?php

/**
 * Fired during plugin activation
 *
 * @link       http://integrai.com.br
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Integrai
 * @subpackage Integrai/includes
 * @author     Integrai <contato@integrai.com.br>
 */

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';

class Integrai_Activator {
  public static function init() {
    self::create_tables();
    $data = self::get_config();
    $id = self::insert_config($data);

    return $id;
  }

  public static function insert_config($data) {
    // Substituir dados se já existir aquele item no banco

    if ( $data ) {
      global $wpdb;
      $table = $wpdb->prefix . 'integrai_config';
      $ids = array();

      foreach($data as $item) {
        $wpdb->insert( $table, array(
          'name' => $item->name,
          'values' => json_encode( $item->values ),
        ));

        array_push($ids, $wpdb->insert_id);
      }

      return !empty($ids) ? $ids : false;
    }

    return false;
  }

  public static function transform_config($data) {
    if ( !is_array($data) ) {
      return false;
    }

    $result = array();

    Integrai_Helper::log($data, 'data: ');

    foreach($data as $item) {
      $name = $item->name;
      $values = json_encode( $item->values );

      $result['name'] = $name;
      $result['values'] = $values;

      array_push($result);

      Integrai_Helper::log($item, '$item: ');
    }

    Integrai_Helper::log($result, 'TRANSFORM_CONFIG: ');

    return $result;
  }

  public static function create_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    global $wpdb;

    $wpdb->hide_errors();

    dbDelta( self::get_schema() );
  }

  public static function get_schema() {
    global $wpdb;

    $collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

    $sql = "
      CREATE TABLE {$wpdb->prefix}integrai_config (
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

    return $sql;
  }

  public static function get_config() {
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
      return Integrai_Helper::log($response->get_error_message(), 'ERROR: ');
    }

    return $result;
  }
}