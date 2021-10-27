<?php
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-helper.php';

class Integrai_Model_Config extends Integrai_Model_Helper {
  public function __construct() {
    parent::__construct('integrai_config');
  }

  public function setup() {
    $this->create_table();

    return $this->update_configs(
      $this->get_default_config(),
    );
  }

  public function update_config($name, $value) {
    if ( !isset( $name ) || !isset( $value ) ) return false;

    try {
        $this->insert_or_update(
            $name,
            array( "values" => $value ),
            array( "name" => $name ),
            false
        );
    } catch (Throwable $e) {
       Integrai_Helper::log($e->getMessage(), 'Erro ao atualizar configurações: ');
    } catch (Exception $e) {
      Integrai_Helper::log($e->getMessage(), 'Erro ao atualizar configurações: ');
    }
  }

  public function update_configs($data) {
    if ( !isset( $data ) || empty( $data ) ) return false;

    if ( $this->table_exists() ) {
      foreach ( $data as $item ) {
        $name = is_array( $item ) ? $item['name'] : $item->name;
        $values = is_array( $item ) ? $item['values'] : $item->values;

        try {
          $this->insert_or_update(
            $name,
            array( 'name' => $name, 'values' => $values ),
            array( 'name' => $name ),
          );
        } catch (Throwable $e) {
            Integrai_Helper::log($e->getMessage(), 'Erro ao atualizar configurações: ');
        } catch (Exception $e) {
          Integrai_Helper::log($e->getMessage(), 'Erro ao atualizar configurações: ');
        }
      }
    }
  }

  public function create_table() {
    $sql = "
      CREATE TABLE IF NOT EXISTS `{$this->prefix}integrai_config` (
        id int(10) unsigned NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        `values` text NOT NULL,
        createdAt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        updatedAt timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
      ) $this->collate;
    ";

    return $this->run_query( $sql );
  }

  public function get_enabled_events() {
    return $this->get_by_name('events_enabled');
  }

  public function is_enabled() {
    $options = get_option('woocommerce_integrai-settings_settings');
    return isset($options['enabled']) ? $options['enabled'] : false;
  }

  public function event_is_enabled($name = '') {
    if ( !$name ) {
      return false;
    }

    $events = $this->get_enabled_events();

    return in_array( $name, $events );
  }

  public function get_options() {
    return get_option('woocommerce_integrai-settings_settings');
  }

  public function get_global() {
    return $this->get_by_name('global');
  }

  public function get_api_url() {
    $configs = $this->get_global();

    return isset( $configs['apiUrl'] ) ? $configs['apiUrl'] : false;
  }

  public function get_danger_alert() {
    $configs = $this->get_global();

    return isset( $configs['dangerAlert'] ) ? $configs['dangerAlert'] : false;
  }

  public function get_api_key() {
    $options = $this->get_options();

    return isset( $options['apiKey'] ) ? $options['apiKey'] : false;
  }

  public function get_secret_key() {
    $options = $this->get_options();

    return isset( $options['secretKey'] ) ? $options['secretKey'] : false;
  }

  public function get_api_timeout_seconds() {
    $configs = $this->get_global();

    return $configs['apiTimeoutSeconds'] ? $configs['apiTimeoutSeconds'] : false;
  }

  public function get_global_config( $name, $defaultValue = null ) {
    $configs = $this->get_global();

    return isset($configs) && isset($configs[$name]) ? $configs[$name] : $defaultValue;
  }

  public function get_minutes_abandoned_cart_lifetime() {
    $configs = $this->get_global();

    return $configs['minutesAbandonedCartLifetime']
      ? $configs['minutesAbandonedCartLifetime']
      : false;
  }

  public function get_shipping() {
    return $this->get_by_name('shipping');
  }

  public function check_if_exists($name = '') {
    $row = $this->get_by_name($name);

    return !is_null( $row );
  }

  public function get_payment_success() {
    return $this->get_by_name('payment_success');
  }

  public function get_payment_pix() {
    return $this->get_by_name('payment_pix');
  }

  public function get_payment_boleto() {
    return $this->get_by_name('payment_boleto');
  }

  public function get_payment_creditcard() {
    return $this->get_by_name('payment_creditcard');
  }

  public function get_creditcard_scripts() {
    $configs = $this->get_payment_creditcard();

    return $configs['scripts'] ? $configs['scripts'] : false;
  }

  public function get_creditcard_form_options() {
    $configs = $this->get_payment_creditcard();

    return $configs['formOptions'] ? $configs['formOptions'] : false;
  }

  public function config_exists() {
    if ( $this->table_exists() ) {
      $configs = array(
        'GLOBAL',
        'SHIPPING',
        'PAYMENT_CREDITCARD',
        'PAYMENT_BOLETO',
        'PAYMENT_PIX',
        'EVENTS_ENABLED',
        'SCRIPTS',
      );

      $count = 0;
      foreach( $configs as $item ) {
        if (self::check_if_exists($item)) {
          $count++;
        }
      }

      return count( $configs ) === $count;
    }

    return false;
  }

  public function get_default_config() {
    return array(
      array(
        'name' => 'EVENTS_ENABLED',
        'values' => '[]',
        'createdAt' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updatedAt' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'GLOBAL',
        'values' => '{
          "minutesAbandonedCartLifetime": 60,
          "apiUrl": "https://api.integrai.com.br",
          "apiTimeoutSeconds": 15,
          "processEventsLimit": 50
        }',
        'createdAt' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updatedAt' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'SHIPPING',
        'values' => '{
          "attributeWidth": "width",
          "attributeHeight": "height",
          "attributeLength": "length",
          "widthDefault": 11,
          "heightDefault": 2,
          "lengthDefault": 16
        }',
        'createdAt' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updatedAt' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'SCRIPTS',
        'values' => '[]',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'PAYMENT_CREDITCARD',
        'values' => '{"formOptions": {"gateways": []}}',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'PAYMENT_PIX',
        'values' => '{"formOptions": {"gateways": []}}',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'PAYMENT_BOLETO',
        'values' => '{"formOptions": {"gateways": []}}',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'PROCESS_EVENTS_RUNNING',
        'values' => 'NOT_RUNNING',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
    );
  }
}