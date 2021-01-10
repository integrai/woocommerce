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
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Integrai
 * @subpackage Integrai/includes
 * @author     Integrai <contato@integrai.com.br>
 */
class Integrai {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Integrai_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $Integrai    The string used to uniquely identify this plugin.
	 */
	protected $Integrai;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	* Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'INTEGRAI_VERSION' ) ) {
			$this->version = INTEGRAI_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->Integrai = 'integrai';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Integrai_Loader. Orchestrates the hooks of the plugin.
	 * - Integrai_i18n. Defines internationalization functionality.
	 * - Integrai_Admin. Defines all hooks for the admin area.
	 * - Integrai_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once INTEGRAI__PLUGIN_DIR . 'admin/class-integrai-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once INTEGRAI__PLUGIN_DIR . 'public/class-integrai-public.php';
		include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';


		include_once INTEGRAI__PLUGIN_DIR . 'admin/wc-config/class-wc-integration-integrai-settings-integration.php';

		$this->loader = new Integrai_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Integrai_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Integrai_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Integrai_Admin( $this->Integrai(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// CHECK WOOCOMMERCE
		$this->loader->add_action('admin_notices', $plugin_admin, 'admin_notices');

		// WOOCOMMERCE
		$this->loader->add_action( 'woocommerce_integrations', $plugin_admin, 'woocommerce_integrations' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
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

		/** WOOCOMMERCE */

    // Events
    $this->loader->add_action( 'woocommerce_created_customer', $plugin_public, 'woocommerce_created_customer' );
    $this->loader->add_action( 'woocommerce_add_to_cart', $plugin_public, 'woocommerce_add_to_cart', 10, 6 );
    $this->loader->add_action( 'woocommerce_new_order', $plugin_public, 'woocommerce_new_order' );
    $this->loader->add_action( 'woocommerce_update_order', $plugin_public, 'woocommerce_update_order' );
    $this->loader->add_action( 'woocommerce_order_status_cancelled', $plugin_public, 'woocommerce_order_status_cancelled' );
    $this->loader->add_action( 'woocommerce_order_refunded', $plugin_public, 'woocommerce_order_refunded' );

    // QUOTE
		if ( class_exists( 'WC_Integration' ) ) {

			$this->loader->add_action( 'woocommerce_shipping_init', $plugin_public, 'woocommerce_shipping_init' );
			$this->loader->add_filter( 'woocommerce_shipping_methods', $plugin_public, 'woocommerce_shipping_methods' );
			$this->loader->add_filter( 'woocommerce_add_cart_item_data', $plugin_public, 'woocommerce_add_cart_item_data' );

		}

    // CHECKOUT
    $this->loader->add_filter( 'woocommerce_payment_gateways', $plugin_public, 'woocommerce_payment_gateways' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function Integrai() {
		return $this->Integrai;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Integrai_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}