<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';

if ( ! class_exists( 'WC_Integration_Integrai_Settings' ) ) :
class WC_Integration_Integrai_Settings {
	/**
	* Construct the plugin.
	*/
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	* Initialize the plugin.
	*/
	public function init() {
		Integrai_Helper::log('WC :: INTEGRATIONS :: SETTINGS :: INIT: ' );
		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Integration' ) ) {
			// Include our integration class.
			include_once INTEGRAI__PLUGIN_DIR . 'admin/wc-config/class-wc-integration-integrai-settings-integration.php';
			// include_once 'class-wc-integration-integrai-settings-integration.php';
			// Register the integration.
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
		} else {
			// throw an admin error if you like
		}
	}

	/**
	 * Add a new integration to WooCommerce.
	 */
	public function add_integration( $integrations ) {
		$integrations[] = 'WC_Integration_Integrai_Settings_Integration';
		Integrai_Helper::log($integrations, 'WC :: INTEGRATIONS :: SETTINGS :: ADD INTEGRATIONS:  ');

		return $integrations;
	}
}

$WC_Integration_Integrai_Settings = new WC_Integration_Integrai_Settings( __FILE__ );

endif;