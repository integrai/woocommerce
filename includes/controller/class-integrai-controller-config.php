<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

/**
 * 1. Mudar controler para endpoints
 * 2. Migrar logica para os models
 * 3. Start com valores de config defaults
 * 4. Configurar endpoint de config, para solicitar as configs reais
 * 5. Setar API_KEY e SECRET_KEY nas configs do WP Woocommerce
 */

class Integrai_Controller_Config {
  static public function create_table() {

  }
}