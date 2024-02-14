<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://integrai.com.br
 * @since      1.0.0
 *
 * @package    Integrai
 * @subpackage Integrai/includes
 */

/**
 * @since      1.0.0
 * @package    Integrai
 * @subpackage Integrai/includes
 * @author     Integrai <contato@integrai.com.br>
 */
class Integrai {
	protected $loader;
	protected $Integrai;
	protected $version;

	public function __construct() {
		if ( defined( 'INTEGRAI_VERSION' ) ) {
			$this->version = INTEGRAI_VERSION;
		} else {
			$this->version = '1.0.24';
		}
		$this->Integrai = 'integrai';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	private function load_dependencies() {
		require_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-loader.php';
		require_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-i18n.php';
		require_once INTEGRAI__PLUGIN_DIR . 'admin/class-integrai-admin.php';
		require_once INTEGRAI__PLUGIN_DIR . 'public/class-integrai-public.php';
    require_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
    require_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-validator.php';
    require_once INTEGRAI__PLUGIN_DIR . 'admin/wc-config/class-wc-integration-integrai-settings-integration.php';

    if ( ! class_exists( 'Integrai_Cron_Process_Events' ) ) :
      require_once INTEGRAI__PLUGIN_DIR . 'includes/model/class-integrai-model-process-events.php';
    endif;

		$this->loader = new Integrai_Loader();
	}

	private function set_locale() {

		$plugin_i18n = new Integrai_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	private function define_admin_hooks() {

		$plugin_admin = new Integrai_Admin( $this->Integrai(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// CHECK WOOCOMMERCE
		$this->loader->add_action('admin_notices', $plugin_admin, 'admin_notices');

		// WOOCOMMERCE
		$this->loader->add_action( 'woocommerce_integrations', $plugin_admin, 'woocommerce_integrations' );

	}

	private function define_public_hooks() {
		$plugin_public = new Integrai_Public( $this->Integrai(), $this->get_version() );

		// FILTERS
		$this->loader->add_filter( 'cron_schedules', $plugin_public, 'integrai_custom_cron_schedules' );

		// QUOTE
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// REST API
		$this->loader->add_action( 'rest_api_init', $plugin_public, 'rest_api_init' );

		// CRON
		$this->loader->add_action( 'integrai_cron_activation', $plugin_public, 'integrai_cron_activation' );
		$this->loader->add_action( 'integrai_cron_deactivation', $plugin_public, 'integrai_cron_deactivation' );
		$this->loader->add_action( 'integrai_cron_resend_events', $plugin_public, 'integrai_cron_resend_events' );
		$this->loader->add_action( 'integrai_cron_abandoned_cart', $plugin_public, 'integrai_cron_abandoned_cart' );
		$this->loader->add_action( 'integrai_cron_proccess_events', $plugin_public, 'integrai_cron_proccess_events' );

		/** WOOCOMMERCE */

    // Events
    $this->loader->add_action( 'woocommerce_created_customer', $plugin_public, 'woocommerce_created_customer' );
    $this->loader->add_action( 'woocommerce_add_to_cart', $plugin_public, 'woocommerce_add_to_cart', 10, 6 );
    $this->loader->add_action( 'woocommerce_checkout_order_created', $plugin_public, 'woocommerce_new_order' );
    $this->loader->add_action( 'woocommerce_update_order', $plugin_public, 'woocommerce_update_order' );
    $this->loader->add_action( 'woocommerce_order_status_cancelled', $plugin_public, 'woocommerce_order_status_cancelled' );
    $this->loader->add_action( 'woocommerce_order_refunded', $plugin_public, 'woocommerce_order_refunded' );
    $this->loader->add_action( 'save_post', $plugin_public, 'woocommerce_save_product', 10, 3 );
    $this->loader->add_action( 'before_delete_post', $plugin_public, 'woocommerce_delete_product', 10, 3 );

    // QUOTE
		if ( class_exists( 'WC_Integration' ) ) {

			$this->loader->add_action( 'woocommerce_shipping_init', $plugin_public, 'woocommerce_shipping_init' );
			$this->loader->add_filter( 'woocommerce_shipping_methods', $plugin_public, 'woocommerce_shipping_methods' );
			$this->loader->add_filter( 'woocommerce_add_cart_item_data', $plugin_public, 'woocommerce_add_cart_item_data' );

		}

    // CHECKOUT
    $this->loader->add_filter( 'woocommerce_payment_gateways', $plugin_public, 'woocommerce_payment_gateways' );

	}

	public function run() {
		$this->loader->run();
	}

	public function Integrai() {
		return $this->Integrai;
	}

	public function get_loader() {
		return $this->loader;
	}

	public function get_version() {
		return $this->version;
	}

}