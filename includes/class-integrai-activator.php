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
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-events.php';

class Integrai_Activator {
  public static function activate() {
    $config = new Integrai_Model_Config();
    $config->setup();

    $events = new Integrai_Model_Events();
    $events->setup();

    // Ativa o CRON
    do_action( 'integrai_cron_activation' );
  }
}