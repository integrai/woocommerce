<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-helper.php';

class Integrai_Model_Process_Events extends Integrai_Model_Helper {
  public function __construct() {
    parent::__construct('integrai_process_events');
  }

  public function setup() {
    return $this->create_table();
  }

  public function create_table() {
    $sql = "
      CREATE TABLE IF NOT EXISTS `{$this->prefix}integrai_process_events` (
        id int(10) unsigned NOT NULL AUTO_INCREMENT,
        event_id text NOT NULL,
        event text NOT NULL,
        payload text NOT NULL,
        created_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
      ) $this->collate;
    ";

    $eventIdIndex = "
        CREATE INDEX my_index 
        ON {$this->prefix}integrai_process_events (event_id)
    ";

    $this->run_query( $sql );

    return $this->run_query( $eventIdIndex );
  }

  public function get_table_name() {
    return $this->table;
  }

  public function get_pending_events() {
    return $this->get_all();
  }

  public function save_events($events = array()) {
    $place_holders = array();

    if (count($events) > 0) {
      foreach($events as $data) {
        $place_holders[] = "( %s, %s, %s, %s)";
      }

      return $this->insert_batch( $place_holders, $events );
    }
  }

  public function insert_batch($place_holders = array(), $values = array()) {
    $queryPlaceholders = implode( ', ', $place_holders);
    $query = "
        INSERT INTO {$this->table} 
            (`event_id`, `event`, `payload`, `created_at`) 
        VALUES {$queryPlaceholders}";

    try {
      foreach ($values as $value) {
        $query = $this->wpdb->prepare( $query, array_values($value));

        $this->wpdb->query( $query );
      }

      return true;
    } catch (Throwable $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao salvar eventos: ');
      return false;
    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Error ao salvar eventos: ');
      return false;
    }
  }
}