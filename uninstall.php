<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// delete database table
global $wpdb;
$table_name = $wpdb->prefix .'integrai_config';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

$table_name = $wpdb->prefix . 'integrai_process_events';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

$table_name = $wpdb->prefix . 'integrai_events';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");
