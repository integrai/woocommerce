<?php

/**
 * Fired during plugin deactivation
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
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-uninstall.php';

class Integrai_Deactivator {
  public static function deactivate() {
    Integrai_Uninstall::uninstall();

    Integrai_Helper::log('DEACTIVATOR');

    // Desativa o CRON
    do_action( 'integrai_cron_deactivation' );
  }
}