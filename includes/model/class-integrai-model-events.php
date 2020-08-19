<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-helper.php';

class Integrai_Model_Events extends Integrai_Model_Helper {
  public function __construct() {
    parent::__construct('integrai_events');
  }

  public function setup() {
    return $this->create_table();
  }

  public function create_table() {
    $sql = "
      CREATE TABLE IF NOT EXISTS `{$this->prefix}integrai_events` (
        id int(10) unsigned NOT NULL AUTO_INCREMENT,
        event text NOT NULL,
        payload text NOT NULL,
        created_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
      ) $this->collate;
    ";

    return $this->run_query( $sql );
  }

  public function get_pending_events() {
    return $this->get_all();
  }
}