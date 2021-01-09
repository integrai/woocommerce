<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once ABSPATH . 'wp-admin/includes/upgrade.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Integrai_Model_Shipping_Config extends Integrai_Model_Helper {
  public function __construct() {
    parent::__construct('integrai_shipping');
  }
}