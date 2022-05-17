<?php

class Integrai_Helper {
  public static function log($log, $prefix = '') {
    $file_path = INTEGRAI__PLUGIN_DIR . 'debug.log';

    $error = (is_array($log) || is_object($log))
      ? print_r($log, true)
      : $log;

    error_log($prefix . " " . $error . " \n", 3, $file_path);
  }

    public static function checkAuthorization($hash) {
        $config = new Integrai_Model_Config();

        $apiKey = $config->get_api_key();
        $secretKey = $config->get_secret_key();
        $token = base64_encode("{$apiKey}:{$secretKey}");
        return $token === str_replace('Basic ', '', $hash);
    }
}
