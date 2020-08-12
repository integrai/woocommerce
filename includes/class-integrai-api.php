<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-config.php';

class Integrai_API {

  public function get_api_url() {
    $config = new Integrai_Model_Config();

    return $config->get_global_config('api_url');
  }
}