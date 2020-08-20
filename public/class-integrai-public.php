<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://integrai.com.br
 * @since      1.0.0
 *
 * @package    Integrai
 * @subpackage Integrai/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Integrai
 * @subpackage Integrai/public
 * @author     Your Name <contato@integrai.com.br>
 */
class Integrai_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $integrai    The ID of this plugin.
	 */
	private $integrai;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $api;
	private $config;
	private $events;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $integrai       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $integrai, $version ) {

		$this->integrai = $integrai;
		$this->version = $version;

		$this->load_dependencies();

	}

	private function get_api_helper() {
		return new Integrai_API();
	}

	private function get_config_helper() {
		return new Integrai_Model_Config();
	}

	private function get_events_helper() {
		return new Integrai_Model_Events();
	}

	private function load_dependencies() {

		if ( ! class_exists( 'Integrai_Helper' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-helpers.php';
		endif;

		if ( ! class_exists( 'Integrai_API' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . '/includes/class-integrai-api.php';
		endif;

		if ( ! class_exists( 'Integrai_Model_Config' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . '/includes/model/class-integrai-model-config.php';
		endif;

		if ( ! class_exists( 'Integrai_Model_Events' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . '/includes/model/class-integrai-model-events.php';
		endif;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Integrai_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Integrai_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->integrai, plugin_dir_url( __FILE__ ) . 'css/integrai-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Integrai_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Integrai_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->integrai, plugin_dir_url( __FILE__ ) . 'js/integrai-public.js', array( 'jquery' ), $this->version, false );

	}

	public function register_rest_route($config_controller) {
		require_once INTEGRAI__PLUGIN_DIR . 'includes/controller/class-integrai-controller-config.php';

		register_rest_route( 'integrai/v1', '/config', array(
			'methods' => 'GET',
			'callback' => array('Integrai_Controller_Config', 'index'),
		) );
	}

	const SAVE_CUSTOMER = 'SAVE_CUSTOMER'; // OK
	const CUSTOMER_BIRTHDAY = 'CUSTOMER_BIRTHDAY';
	const NEWSLETTER_SUBSCRIBER = 'NEWSLETTER_SUBSCRIBER';
	const ADD_PRODUCT_CART = 'ADD_PRODUCT_CART'; // OK
	const ABANDONED_CART = 'ABANDONED_CART';
	const NEW_ORDER = 'NEW_ORDER'; // OK
	const SAVE_ORDER = 'SAVE_ORDER'; // OK
	const CANCEL_ORDER = 'CANCEL_ORDER';

	// Usuário cadastrado?
	public function woocommerce_created_customer( $customer_id, $new_customer_data, $password_generated ) {
		$customer = array(
			'customer_id' => $customer_id,
			'new_customer_data' => $new_customer_data,
			'password_generated' => $password_generated,
		);

		Integrai_Helper::log($customer, 'HOOKS :: CREATED_CUSTOMER: ');
	}

	// Adicionar ao carrinho
	public function woocommerce_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		if ( is_user_logged_in() && $this->get_config_helper()->event_is_enabled(self::ADD_PRODUCT_CART) ) {
			$user_id = get_current_user_id();
			$user_data = get_userdata($user_id);
			$customer = $user_data->data;

			$product = wc_get_product( $product_id );
			$product_data = array(
				'name' => $product->get_name(),
				'slug' => $product->get_slug(),
				'sku' => $product->get_sku(),
				'description' => $product->get_description(),
				'price' => $product->get_price(),
			);

			$cart = array(
				'cart_item_key' => $cart_item_key,
				'product' => $product_data,
				'quantity' => $quantity,
				'variation_id' => $variation_id,
				'variation' => $variation,
				'cart_item_data' => $cart_item_data ,
			);

			$data = array(
				'customer' => $customer,
				'item' => $cart,
			);

			return $this->get_api_helper()->send_event(self::ADD_PRODUCT_CART, $data);
		}
	}

	// Novo Pedido
	public function woocommerce_new_order( $order_id, $order ) {
		$order = array(
			'order_id' => $order_id,
			'order' => $order,
		);

		Integrai_Helper::log($customer, 'HOOKS :: NEW_ORDER: ');
	}

	// Pedido Pago [Verificar]
	public function woocommerce_checkout_order_processed( $order_id, $posted_data, $order ) {
		$order = array(
			'order_id' => $order_id,
			'order' => $order,
			'posted_data' => $posted_data,
		);
	}

	// CRON - EVENTS:
	public function integrai_custom_cron_schedules( $schedules ) {
		$schedules[ 'integrai_every_5_minutes' ] = array(
			'interval' => (5 * MINUTE_IN_SECONDS),
			'display' => __( 'Every 5 minutes' ),
		);

		$schedules[ 'integrai_every_minute' ] = array(
			'interval' => MINUTE_IN_SECONDS,
			'display' => __( 'Every minute' ),
		);

		return $schedules;
	}
	public function integrai_cron_resend_events_activation() {
		if ( ! wp_next_scheduled( 'integrai_cron_resend_events' ) ) {
			wp_schedule_event( time(), 'integrai_every_minute', 'integrai_cron_resend_events' );
		}
	}

	public function integrai_cron_resend_events_deactivation() {
		$timestamp = wp_next_scheduled( 'integrai_cron_resend_events' );
		wp_unschedule_event ($timestamp, 'integrai_cron_resend_events');
	}

	public function integrai_cron_resend_events() {
		$options = get_option('woocommerce_integrai-settings_settings');
		$is_enabled = $options['enable_integration'];

		$pending_events = $this->get_events_helper()->get_pending_events();

		if ( $is_enabled && count( $pending_events ) > 0 ) {
			foreach( $pending_events as $event ) {
				try {
					$event_id = $event->id;
					$event_name = $event->event;
					$payload = json_decode($event->payload, true);

					$response = $this->get_api_helper()->send_event($event_name, $payload);

					$this->get_events_helper()->delete_by_id( $event_id );

				} catch (Exception $e) {
					Integrai_helper::log('Error ao reenviar o evento', $event_name);
				}
			}
		}
	}
}