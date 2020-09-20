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

	// EVENTOS PARA MAPEAR
	// const NEWSLETTER_SUBSCRIBER = 'NEWSLETTER_SUBSCRIBER';
	// const ADD_PRODUCT_CART = 'ADD_PRODUCT_CART';
	// const NEW_ORDER = 'NEW_ORDER';
	// const SAVE_ORDER = 'SAVE_ORDER';
	// const CANCEL_ORDER = 'CANCEL_ORDER';

	// TO CHECK

	/** NEWSLETTER_SUBSCRIBER: ***********************************
	 * Não tem nativamente. Só via plugin.
	 * Podemos procurar os hooks dos mais populares pra integrar.
	 * Vale a pena?
	 * Ou criar uma feature para fazer isso.
	*************************************************************/
	const NEWSLETTER_SUBSCRIBER = 'NEWSLETTER_SUBSCRIBER';

	/** CUSTOMER_BIRTHDAY: ***********************************
	 * WP e WC não oferecem esse campo nativamente.
	 * Podemos adicionar via plugin, como uma user_meta e usar
	 * para integrar o evento.
	 * Vale a pena?
	*************************************************************/
	const CUSTOMER_BIRTHDAY = 'CUSTOMER_BIRTHDAY';

	// const FINALIZE_CHECKOUT = 'FINALIZE_CHECKOUT';
	const ABANDONED_CART = 'ABANDONED_CART';

	// EVENT OK

	// DONE
	const NEW_CUSTOMER = 'NEW_CUSTOMER';
	const ADD_PRODUCT_CART = 'ADD_PRODUCT_CART';
	const NEW_ORDER = 'NEW_ORDER';
	const CANCEL_ORDER = 'CANCEL_ORDER';
	const REFUND_INVOICE = 'REFUND_INVOICE';
	const SAVE_ORDER = 'SAVE_ORDER';

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

	private function get_customer_sessions() {
		global $wpdb;

		$order_sessions = $wpdb->get_results("
				SELECT *
				FROM ". $wpdb->prefix."woocommerce_sessions
		");

		$serialized_sessions = array();
		foreach ( $order_sessions as $order ) {
			$parsed_order = array();
			$raw_value = maybe_unserialize($order->session_value);

			$parsed_order['cart'] = maybe_unserialize( $raw_value['cart'] );
			$parsed_order['cart_totals'] = maybe_unserialize( $raw_value['cart_totals'] );
			$parsed_order['applied_coupons'] = maybe_unserialize( $raw_value['applied_coupons'] );
			$parsed_order['coupon_discount_totals'] = maybe_unserialize( $raw_value['coupon_discount_totals'] );
			$parsed_order['coupon_discount_tax_totals'] = maybe_unserialize( $raw_value['coupon_discount_tax_totals'] );
			$parsed_order['removed_cart_contents'] = maybe_unserialize( $raw_value['removed_cart_contents'] );
			$parsed_order['shipping_for_package_0'] = maybe_unserialize( $raw_value['shipping_for_package_0'] );
			$parsed_order['previous_shipping_methods'] = maybe_unserialize( $raw_value['previous_shipping_methods'] );
			$parsed_order['chosen_shipping_methods'] = maybe_unserialize( $raw_value['chosen_shipping_methods'] );
			$parsed_order['shipping_method_counts'] = maybe_unserialize( $raw_value['shipping_method_counts'] );
			$parsed_order['customer'] = maybe_unserialize( $raw_value['customer'] );

			if ( !empty( $parsed_order['customer']['id'] ) ) {
				array_push($serialized_sessions, $parsed_order);
			}
		}

		return $serialized_sessions;
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

	private function get_customer( $customer_id ) {
		$customer = new WC_Customer( $customer_id );

		return array(
			'id' => $customer_id,
			'email' => $customer->get_email(),
			'first_name' => $customer->get_first_name(),
			'last_name' => $customer->get_last_name(),
			'billing' => $customer->get_billing(),
			'shipping' => $customer->get_shipping(),
		);
	}

	// private function get_customers() {
	// 	$users_per_page = get_option( 'posts_per_page' );

	// 	$query_args = array(
	// 		'fields'  => 'ID',
	// 		'role'    => 'customer',
	// 		'orderby' => 'registered',
	// 		'number'  => $users_per_page,
	// 	);

	// 	$query = new WP_User_Query( $query_args );
	// }

	private function get_order( $order_id ) {
		$order = new WC_Order( $order_id );

		return $order->get_data();
	}

	private function get_refund( $refund_id ) {
		$refund = new WC_Order_Refund( $refund_id) ;

		return array(
			'type' => $refund->get_type(),
			'status' => $refund->get_status(),
			'post_title' => $refund->get_post_title(),
			'amount' => $refund->get_amount(),
			'reason' => $refund->get_reason(),
			'refunded_by' => $refund->get_refunded_by(),
			'refunded_payment' => $refund->get_refunded_payment(),
			'formatted_refund_amount' => $refund->get_formatted_refund_amount(),
		);
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

	/** EVENTS */

	// NEW_CUSTOMER
	public function woocommerce_created_customer( $customer_id, $new_customer_data = null, $password_generated = null ) {

		if ( isset($customer_id) && $this->get_config_helper()->event_is_enabled(self::NEW_CUSTOMER) ) {

			$customer = $this->get_customer( $customer_id );

			return $this->get_api_helper()->send_event(self::NEW_CUSTOMER, $customer);

		}

	}

	// Filter on ADD_CART to add created_at
	public function woocommerce_add_cart_item_data( $cart_item_data ) {
		$cart_item_data['created_at'] = date('Y-m-d H:i:s', strtotime("now"));

		return $cart_item_data;
	}

	// ADD_PRODUCT_CART
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

			$payload = array(
				'customer' => $customer,
				'item' => $cart,
			);

			return $this->get_api_helper()->send_event(self::ADD_PRODUCT_CART, $payload);
		}
	}

	// NEW_ORDER
	public function woocommerce_new_order( $order_id ) {
		$OrderInstance = new WC_Order($order_id);
		$order = $this->get_order( $order_id );

		$customer_id = $OrderInstance->get_customer_id();
		$customer = $this->get_customer( $customer_id );

		$order['customer'] = $customer;

		return $this->get_api_helper()->send_event(self::NEW_ORDER, $order);
	}

	// SAVE_ORDER
	public function woocommerce_update_order( $order_id ) {
		$OrderInstance = new WC_Order($order_id);
		$order = $this->get_order( $order_id );

		$customer_id = $OrderInstance->get_customer_id();
		$customer = $this->get_customer( $customer_id );

		$order['customer'] = $customer;

		return $this->get_api_helper()->send_event(self::SAVE_ORDER, $order);
	}

	// CANCEL_ORDER
	public function woocommerce_cancelled_order( $order_id ) {
		$OrderInstance = new WC_Order($order_id);

		$customer_id = $OrderInstance->get_customer_id();
		$CustomerInstance = new WC_Customer( $customer_id );

		$customer = array(
			'id' => $customer_id,
			'email' => $CustomerInstance->get_email(),
			'first_name' => $CustomerInstance->get_first_name(),
			'last_name' => $CustomerInstance->get_last_name(),
			'shipping' => $CustomerInstance->get_shipping(),
			'billing' => $CustomerInstance->get_billing(),
		);

		$payload = $OrderInstance->get_data();
		$payload['customer'] = $customer;

		return $this->get_api_helper()->send_event(self::CANCEL_ORDER, $payload);
	}

	// CANCEL_ORDER
	public function woocommerce_order_refunded( $order_id, $refund_id ) {
		$OrderInstance = new WC_Order($order_id);
		$customer_id = $OrderInstance->get_customer_id();

		$order = $this->get_order( $order_id );
		$refund = $this->get_refund( $refund_id );
		$customer = $this->get_customer( $customer_id );

		$payload = $order;
		$payload['refund'] = $refund;
		$payload['customer'] = $customer;

		return $this->get_api_helper()->send_event(self::REFUND_INVOICE, $payload);
	}

	// ABANDONED_CART


	/** CRON - EVENTS: */
	public function integrai_custom_cron_schedules( $schedules ) {
		$schedules['integrai_every_5_minutes'] = array(
			'interval' => (5 * MINUTE_IN_SECONDS),
			'display' => __( 'Every 5 minutes' ),
		);

		$schedules['integrai_every_minute'] = array(
			'interval' => MINUTE_IN_SECONDS,
			'display' => __( 'Every minute' ),
		);

		return $schedules;
	}

	public function integrai_cron_activation() {
		if ( ! wp_next_scheduled( 'integrai_cron_resend_events' ) ) {
			wp_schedule_event( time(), 'integrai_every_minute', 'integrai_cron_resend_events' );
		}

		if ( ! wp_next_scheduled( 'integrai_cron_abandoned_cart' ) ) {
			wp_schedule_event( time(), 'integrai_every_minute', 'integrai_cron_abandoned_cart' );
		}

		// if ( ! wp_next_scheduled( 'integrai_check_dob' ) ) {
		// 	wp_schedule_event( time(), 'daily', 'integrai_check_dob' );
		// }
	}

	public function integrai_cron_deactivation() {
		// Resend Events
		$events_timestamp = wp_next_scheduled( 'integrai_cron_resend_events' );
		wp_unschedule_event ($events_timestamp, 'integrai_cron_resend_events');

		// Check the date of birth
		// $dob_timestamp = wp_next_scheduled( 'integrai_check_dob' );
		// wp_unschedule_event ($dob_timestamp, 'integrai_check_dob');
	}

	// public function integrai_check_dob() {

	// }
	public function integrai_cron_abandoned_cart() {
		// Pegar o carrinhos
		// Verificar a data da ultima atualização
		// se for maior que X minutos, disparar action para notificação
		// com o id do carrinho

		if ( $this->get_config_helper()->event_is_enabled(self::ABANDONED_CART) ) {
			$cart_lifetime = $this->get_config_helper()->get_minutes_abandoned_cart_lifetime();
			$minutes = $cart_lifetime ? $cart_lifetime : 60;
			$from_date = date('Y-m-d H:i:s', strtotime('-' . $minutes . ' minutes'));
			$cart_created = date('Y-m-d H:i:s', strtotime("now"));

			$sessions = $this->get_customer_sessions();
			$abandoned_cart = array();

			foreach ($sessions as $session) {
				$cart = $session['cart'];

				// Verifica qual dos produtos do carrinho tem a data de criação mais antiga
				foreach ($cart as $product) {
					$created_at = $product['created_at'];

					// Pega a data mais antiga e considera a data da criação do carrinho
					if ($created_at < $cart_created) {
						$cart_created = $created_at;
					}
				}

				// Se a data de criação for mais antiga que a data de corte, considera como abandadono
				if ($cart_created < $from_date) {
					$item = array();

					$item['created_at'] = $cart_created;
					$item['customer'] = $session['customer'];
					$item['cart'] = $session['cart'];
					$item['cart_totals'] = $session['cart_totals'];

					array_push($abandoned_cart, $item);
				}
			}

			Integrai_Helper::log($abandoned_cart, '==> ABANDONED_CART: ');

			if ( !empty($abandoned_cart) ) {
				return $this->get_api_helper()->send_event(self::ABANDONED_CART, $abandoned_cart);
			}
		}
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

	/** QUOTE */
	// SHIPPING METHODS
	public function woocommerce_shipping_methods($methods) {
		$methods['integrai_shipping_method'] = 'Integrai_Shipping_Methods';

		return $methods;
	}

	public function woocommerce_shipping_init() {
		include_once INTEGRAI__PLUGIN_DIR . 'public/class-integrai-shipping-methods.php';
	}

}