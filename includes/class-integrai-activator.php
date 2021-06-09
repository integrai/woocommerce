<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-events.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-process-events.php';

class Integrai_Activator {
  public static function activate() {
    Integrai_Helper::log('Ativando plugin...');
    $config = new Integrai_Model_Config();
    $config->setup();

    $events = new Integrai_Model_Events();
    $events->setup();

    $process_events = new Integrai_Model_Process_Events();
    $process_events->setup();

    do_action( 'integrai_cron_activation' );
  }
}