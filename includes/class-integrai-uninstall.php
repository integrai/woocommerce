<?php

/**
 * Fired during plugin uninstalled
 *
 * @link       http://integrai.com.br
 * @since      1.0.0
 *
 * @package    Integrai
 * @subpackage Integrai/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Integrai
 * @subpackage Integrai/includes
 * @author     Integrai <contato@integrai.com.br>
 */

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';

class Integrai_Uninstall {
  public static function init() {
    self::delete_events_table();
    self::delete_config_table();
  }

  public static function delete_events_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'integrai_events';

    // drop the table from the database.
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
  }

  public static function delete_config_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'integrai_config';

    // drop the table from the database.
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
  }
}