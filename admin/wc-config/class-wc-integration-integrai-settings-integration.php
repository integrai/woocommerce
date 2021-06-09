<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

if ( ! class_exists('WC_Settings_API' )) {
    require_once WP_PLUGIN_DIR . '/woocommerce/includes/abstracts/abstract-wc-settings-api.php';
}

if ( ! class_exists( 'WC_Integration' )) {
    require_once WP_PLUGIN_DIR . '/woocommerce/includes/abstracts/abstract-wc-integration.php';
}

if ( ! class_exists( 'WC_Integration_Integrai_Settings_Integration' ) ) :
class WC_Integration_Integrai_Settings_Integration extends WC_Integration {
  private $api_key;
  private $secret_key;

  public function __construct() {
    global $woocommerce;

    $this->id                 = 'integrai-settings';
    $this->method_title       = __( 'Integrai', 'woocommerce-integrai-settings' );
    $this->method_description = __( 'Integrações para o seu E-Commerce.', 'woocommerce-integration-settings' );

    // Load the settings.
    $this->init_form_fields();
    $this->init_settings();

    // Define user set variables.
    $this->enabled          = $this->get_option( 'enabled' );
    $this->api_key          = $this->get_option( 'apiKey' );
    $this->secret_key       = $this->get_option( 'secretKey' );

    // Actions.
    add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );

    // Filters.
    add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );

  }

  public function sanitize_settings( $fields ) {
    return $fields;
  }

  public function validate_api_key_field( $key, $value ) {
    if ( strlen( $value ) < 10 ) {
      WC_Admin_Settings::add_error( esc_html__(
        'Chave da API incorreta. Verifique e tente novamente.',
        'woocommerce-integration-settings',
      ) );
    }

    return $value;
  }

  public function validate_secret_key_field( $key, $value ) {
    if ( strlen( $value ) < 10 ) {
      WC_Admin_Settings::add_error( esc_html__(
        'Segredo da chave incorreto. Verifique e tente novamente.',
        'woocommerce-integration-settings',
      ) );
    }

    return $value;
  }

  public function display_errors( ) {
    foreach ( $this->errors as $key => $value ) {
      ?>
          <div class="error">
            <p><?php _e( 'Looks like you made a mistake with the ' . $value . ' field. Make sure it isn&apos;t longer than 20 characters', 'woocommerce-integration-demo' ); ?></p>
          </div>
      <?php
    }
  }

  public function init_form_fields() {
    $this->form_fields = array(
      'enabled' => array(
        'title'             => __( 'Habilitar', 'woocommerce-integrai-settings' ),
        'type'              => 'select',
        'description'       => __( 'Habilitar integração com Integrai.', 'woocommerce-integrai-settings' ),
        'default'           => 'no',
        'options'           => array(
          'true' => 'Sim',
          'false' => 'Não',
        ),
      ),
      'apiKey' => array(
        'title'             => __( 'Chave da API', 'woocommerce-integrai-settings' ),
        'type'              => 'text',
        'description'       => __( 'Sua API Key criadas no painel da Integrai', 'woocommerce-integrai-settings' ),
        'desc_tip'          => true,
        'default'           => ''
      ),
      'secretKey' => array(
        'title'             => __( 'Segredo da Chave', 'woocommerce-integrai-settings' ),
        'type'              => 'password',
        'description'       => __( 'Seu Segredo da Chave criado no painel da Integrai', 'woocommerce-integrai-settings' ),
        'desc_tip'          => true,
        'default'           => '',
      ),
    );
  }
}

endif;