<?php

class Integrai_Helper {
  public static function log($log, $prefix = '') {
    $file_path = INTEGRAI__PLUGIN_DIR . 'debug.log';

    $error = (is_array($log) || is_object($log))
      ? print_r($log, true)
      : $log;

    error_log($prefix . $error . " \n", 3, $file_path);
  }
}
