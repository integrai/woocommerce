<?php
include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
include_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-helper.php';

class Integrai_Model_Config extends Integrai_Model_Helper {
  public function __construct() {
    parent::__construct('integrai_config');
  }

  public function setup() {
    $this->create_table();

    $data = $this->get_default_config();
    $action = $this->config_exists() ? 'update_many' : 'insert_many';

    $ids = $this->{$action}($data);

    return $ids;
  }

  public function update_config($data) {
    if ( !$data || empty( $data ) ) return false;

    $table_exists = $this->table_exists();
    $config_exists = $this->config_exists();
    $action = $config_exists ? 'update_many' : 'insert_many';

    return $table_exists ? $this->{$action}($data) : false;
  }

  public function create_table() {
    $sql = "
      CREATE TABLE IF NOT EXISTS `{$this->prefix}integrai_config` (
        id int(10) unsigned NOT NULL AUTO_INCREMENT,
        name text NOT NULL,
        `values` text NOT NULL,
        created_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        updated_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (id)
      ) $this->collate;
    ";

    return $this->run_query( $sql );
  }

  public function get_enabled_events() {
    return $this->get_by_name('events_enabled');
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

    return isset( $configs['api_url'] ) ? $configs['api_url'] : false;
  }

  public function get_api_key() {
    $options = $this->get_options();

    return isset( $options['api_key'] ) ? $options['api_key'] : false;
  }

  public function get_secret_key() {
    $options = $this->get_options();

    return isset( $options['secret_key'] ) ? $options['secret_key'] : false;
  }

  public function get_api_timeout_seconds() {
    $configs = $this->get_global();

    return $configs['api_timeout_seconds'] ? $configs['api_timeout_seconds'] : false;
  }

  public function get_global_config( $name ) {
    $configs = $this->get_global();

    return $configs[$name] ? $configs[$name] : false;
  }

  public function get_minutes_abandoned_cart_lifetime() {
    $configs = $this->get_global();

    return $configs['minutes_abandoned_cart_lifetime']
      ? $configs['minutes_abandoned_cart_lifetime']
      : false;
  }

  public function get_shipping() {
    return $this->get_by_name('shipping');
  }

  public function check_if_exists($name = '') {
    $row = $this->get_by_name($name);

    return !is_null( $row );
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
    $table_exists = $this->table_exists();

    if ( $this->table_exists() ) {
      $configs = array(
        'GLOBAL',
        'SHIPPING',
        'PAYMENT_CREDITCARD',
        'EVENTS_ENABLED',
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
        'values' => '[
          "NEW_CUSTOMER",
          "CUSTOMER_BIRTHDAY",
          "NEWSLETTER_SUBSCRIBER",
          "ADD_PRODUCT_CART",
          "ABANDONED_CART",
          "NEW_ORDER",
          "SAVE_ORDER",
          "CANCEL_ORDER",
          "FINALIZE_CHECKOUT"
        ]',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'GLOBAL',
        'values' => '{
          "minutes_abandoned_cart_lifetime": 60,
          "api_url": "http://host.docker.internal:3000/v1",
          "api_timeout_seconds": 3
        }',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'SHIPPING',
        'values' => '{
          "attribute_width": "width",
          "attribute_height": "height",
          "attribute_length": "length",
          "width_default": 11,
          "height_default": 2,
          "length_default": 16
        }',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
      array(
        'name' => 'PAYMENT_CREDITCARD',
        'values' => '{
          "scripts": [
            "https://assets.integrai.com.br/payment-form/creditcard/magento.js",
            "https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js",
            "https://assets.pagar.me/pagarme-js/4.11/pagarme.min.js",
            "https://assets.integrai.com.br/gateways/scripts/moip-sdk-min.js"
          ],
          "formOptions": {
            "labels": {
              "number": "Número do cartão de crédito ",
              "expiration": "Data de expiração",
              "cvv": "Código de segurança",
              "holder_name": "Nome no cartão",
              "doc_type": "Tipo de documento",
              "doc_number": "Número do documento",
              "installments": "Número de parcelas",
              "installments_placeholder": "Informe o número de parcelas",
              "installments_replace": "%sx de %s (%s)"
            },
            "beforeForm": "%3Ch1%3E%3Cbr%3E%3C/h1%3E",
            "afterForm": "%3Cp%3E%20%3C/p%3E",
            "gateways": [
              {
                "name": "mercadopago",
                "isMain": true,
                "credentials": {
                  "publicKey": "TEST-089dddcf-8cb5-448e-aa5d-56ffc180bd4d"
                }
              },
              {
                "name": "pagarme",
                "isMain": false,
                "credentials": {
                  "publicKey": "ek_test_sGN33foESLLWjyuGXNMv9NQxgaJ0cP",
                  "free_installments": "1",
                  "max_installments": "12",
                  "interest_rate": "1,8",
                  "min_value": "5,50"
                }
              },
              {
                "name": "wirecard",
                "isMain": false,
                "credentials": {
                  "publicKey": "-----BEGIN PUBLIC KEY-----MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAobunyDlls7veMBaxxDTormHS17p/RA6IQMlBlM9VIFQ8U4Uwdd5Wwua2qZNomaIfequ1+lOPNby+eykyn9K76EFzIYVTuQJRfMCLqrEj/XbfCP8GhJAY07hCSlizkllI7JAIwKCfPhJ8c7MrsTcXg59Qgt9Wbv0sr2RCYpbkaXRFwPADcA42l7nOZONYxw3/5ZQ6HFzZ+8FmM4gIjPKD4Ly2STcoi3a03p2nxhg9+7rOwn36n1dexD+fOmdciF1v6KBkaMlQABMFIZV7fjg5HU54FeGHggWBObB2wg4riWbTNQumUY2murxWKecbOCaozvocm0mCUzo30dxvzRK+zwIDAQAB-----END PUBLIC KEY-----",
                  "free_installments": "1",
                  "max_installments": "12",
                  "interest_rate": "1,8",
                  "min_value": "5"
                }
              }
            ]
          }
        }',
        'created_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
        'updated_at' => strftime('%Y-%m-%d %H:%M:%S', time()),
      ),
    );
  }
}