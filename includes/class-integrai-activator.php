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
include_once INTEGRAI__PLUGIN_DIR . 'includes/controller/class-integrai-controller-config.php';

class Integrai_Activator {
  public static function activate() {
    $ids = Integrai_Controller_Config::create_table();

    return $ids;
  }
}