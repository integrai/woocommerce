<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';

class Integrai_Deactivator {
  public static function deactivate() {
    Integrai_Helper::log('Desativando plugin...');

    // Desativa o CRON
    do_action( 'integrai_cron_deactivation' );
  }
}