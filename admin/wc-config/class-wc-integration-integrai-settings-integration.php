<?php

/**
 * Integration Demo Integration.
 *
 * @package  WC_Integration_Integrai_Settings_Integration
 * @category Integration
 * @author   Integrai
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists('WC_Settings_API' )) {
    require_once ABSPATH . 'wp-content/plugins/woocommerce/includes/abstracts/abstract-wc-settings-api.php';
}

if ( ! class_exists( 'WC_Integration' )) {
    require_once ABSPATH . 'wp-content/plugins/woocommerce/includes/abstracts/abstract-wc-integration.php';
}

if ( ! class_exists( 'WC_Integration_Integrai_Settings_Integration' ) ) :
class WC_Integration_Integrai_Settings_Integration extends WC_Integration {
  /**
 * Init and hook in the integration.
 */
  public function __construct() {
    global $woocommerce;

    $this->id                 = 'integrai-settings';
    $this->method_title       = __( 'Integrai', 'woocommerce-integrai-settings' );
    $this->method_description = __( 'Integrações para o seu E-Commerce.', 'woocommerce-integration-settings' );

    // Load the settings.
    $this->init_form_fields();
    $this->init_settings();

    // Define user set variables.
    $this->api_key          = $this->get_option( 'api_key' );
    $this->secret_key       = $this->get_option( 'secret_key' );

    // Actions.
    add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );


  }

	/**
	 * Override the normal options so we can print the database file path to the admin,
	 */
	public function admin_options() {
		parent::admin_options();

		// include INTEGRAI__PLUGIN_DIR . '/admin/partials/integrai-admin-form.php';
	}

  /**
   * Initialize integration settings form fields.
   */
  public function init_form_fields() {
    $this->form_fields = array(
      'enable_integration' => array(
        'title'             => __( 'Habilitar', 'woocommerce-integrai-settings' ),
        'type'              => 'select',
        'description'       => __( 'Habilitar integração com IntegrAi.', 'woocommerce-integrai-settings' ),
        'default'           => 'no',
        'options'           => array(
          'true' => 'Sim',
          'false' => 'Não',
        ),
      ),
      'api_key' => array(
        'title'             => __( 'Chave da API', 'woocommerce-integrai-settings' ),
        'type'              => 'text',
        'description'       => __( 'Sua API Key criadas no painel da IntegrAi', 'woocommerce-integrai-settings' ),
        'desc_tip'          => true,
        'default'           => ''
      ),
      'secret_key' => array(
        'title'             => __( 'Segredo da Chave', 'woocommerce-integrai-settings' ),
        'type'              => 'text',
        'description'       => __( 'Seu Segredo da Chave criado no painel da IntegrAi', 'woocommerce-integrai-settings' ),
        'desc_tip'          => true,
        'default'           => '',
      ),
    );
  }
}

endif;