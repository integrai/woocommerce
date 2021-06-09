<?php

include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';

if ( ! class_exists( 'WC_Integration_Integrai_Settings' ) ) :
class WC_Integration_Integrai_Settings {
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init() {
		if ( class_exists( 'WC_Integration' ) ) {
			include_once INTEGRAI__PLUGIN_DIR . 'admin/wc-config/class-wc-integration-integrai-settings-integration.php';

			// Register the integration.
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
		}
	}

	public function add_integration( $integrations ) {
		$integrations[] = 'WC_Integration_Integrai_Settings_Integration';

		return $integrations;
	}
}

$WC_Integration_Integrai_Settings = new WC_Integration_Integrai_Settings( __FILE__ );

endif;