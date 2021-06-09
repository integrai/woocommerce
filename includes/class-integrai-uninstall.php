<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';

class Integrai_Uninstall {
  public static function uninstall() {
    self::drop_config_table();
  }

  public static function drop_config_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'integrai_config';

    Integrai_Helper::log("Dropping $table_name");
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
  }
}