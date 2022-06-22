<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Integrai
 * @subpackage Integrai/public
 * @author     Integrai <contato@integrai.com.br>
 */

class Integrai_Public {
	private $integrai;
	private $version;

	private $api;
	private $config;
	private $events;

	const CREATE_CUSTOMER = 'CREATE_CUSTOMER';
	const ADD_PRODUCT_CART = 'ADD_PRODUCT_CART';
	const CREATE_ORDER = 'CREATE_ORDER';
	const UPDATE_ORDER_ITEM = 'UPDATE_ORDER_ITEM';
	const UPDATE_ORDER = 'UPDATE_ORDER';
	const REFUND_INVOICE = 'REFUND_INVOICE';
	const ABANDONED_CART = 'ABANDONED_CART';
	const ABANDONED_CART_ITEM = 'ABANDONED_CART_ITEM';
	const CREATE_PRODUCT = 'CREATE_PRODUCT';
	const UPDATE_PRODUCT = 'UPDATE_PRODUCT';
	const DELETE_PRODUCT = 'DELETE_PRODUCT';

	public function __construct( $integrai, $version ) {
		$this->integrai = $integrai;
		$this->version = $version;

		$this->load_dependencies();
	}

	private function try_serialize($list, $key) {
		return array_key_exists( $key, $list ) ? maybe_unserialize( $list[$key] ) : null;
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

	private function get_payment_helper() {
		return new Integrai_Payment_Method_Helper();
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

			$parsed_order['cart'] 			 								= $this->try_serialize( $raw_value, 'cart' );
			$parsed_order['cart_totals'] 								= $this->try_serialize( $raw_value, 'cart_totals' );
			$parsed_order['applied_coupons'] 						= $this->try_serialize( $raw_value, 'applied_coupons' );
			$parsed_order['coupon_discount_totals']			= $this->try_serialize( $raw_value, 'coupon_discount_totals' );
			$parsed_order['coupon_discount_tax_totals'] = $this->try_serialize( $raw_value, 'coupon_discount_tax_totals' );
			$parsed_order['removed_cart_contents'] 			= $this->try_serialize( $raw_value, 'removed_cart_contents' );
			$parsed_order['shipping_for_package_0'] 		= $this->try_serialize( $raw_value, 'shipping_for_package_0' );
			$parsed_order['previous_shipping_methods'] 	= $this->try_serialize( $raw_value, 'previous_shipping_methods' );
			$parsed_order['chosen_shipping_methods'] 		= $this->try_serialize( $raw_value, 'chosen_shipping_methods' );
			$parsed_order['shipping_method_counts'] 		= $this->try_serialize( $raw_value, 'shipping_method_counts' );
			$parsed_order['customer'] 									= $this->try_serialize( $raw_value, 'customer' );

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

		if ( ! class_exists( 'Integrai_Payment_Method_Helper' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-payment-method-helper.php';
		endif;

		if ( ! class_exists( 'Integrai_API' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . '/includes/class-integrai-api.php';
		endif;

        if ( ! class_exists( 'Integrai_Process_Event' ) ) :
            include_once INTEGRAI__PLUGIN_DIR . '/includes/class-integrai-process-event.php';
        endif;

		if ( ! class_exists( 'Integrai_Model_Config' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . '/includes/model/class-integrai-model-config.php';
		endif;

		if ( ! class_exists( 'Integrai_Model_Events' ) ) :
			include_once INTEGRAI__PLUGIN_DIR . '/includes/model/class-integrai-model-events.php';
		endif;

		if ( ! class_exists( 'Integrai_Cron_Process_Events' ) ) :
      include_once INTEGRAI__PLUGIN_DIR . 'includes/cron/class-integrai-cron-process-events.php';
		endif;

	}

	private function get_customer( $customer_id ) {
		$customer = new WC_Customer( $customer_id );
		$person_type = $customer->get_meta('billing_persontype');
    $doc_type = isset($person_type) && $customer->get_meta('billing_persontype') == '2' ? 'cnpj' : 'cpf';
		$doc_key  = $doc_type == 'cpf' ? 'billing_cpf' : 'billing_cnpj';

		return array(
			'id' => $customer_id,
			'email' => $customer->get_email(),
			'first_name' => $customer->get_first_name(),
			'last_name' => $customer->get_last_name(),
      'document_type' => $doc_type,
      'document_number' => $customer->get_meta( $doc_key ),
			'billing' => $customer->get_billing(),
			'shipping' => $customer->get_shipping(),
		);
	}

	private function get_customer_from_order( $order_id ) {
    $date = new DateTime();
    $orderInstance = new WC_Order( $order_id );
    $orderData = $orderInstance->get_data();
    $billing = $orderData['billing'];
    $payment = $this->get_payment( $order_id, true );

    return array(
      'id' => $date->getTimestamp(),
      'email' => $billing['email'],
      'first_name' => isset($billing['first_name']) ? $billing['first_name'] : $payment['first_name'],
      'last_name' => isset($billing['last_name']) ? $billing['last_name'] : $payment['last_name'],
      'document_type' => $payment['doc_type'],
      'document_number' => $payment['document_number'],
      'billing' => $billing,
    );
  }

	private function get_order( $order_id ) {
		$order = new WC_Order( $order_id );

		return $order->get_data();
	}

	private function get_full_order( $order_id ) {
    $orderInstance = new WC_Order( $order_id );
    $order = $orderInstance->get_data();

    $order['payment']         = $this->get_payment( $order_id );
    $order['items']           = $this->get_items( $order_id );
    $order['shipping_method'] = $this->get_shipping_method();
    $order['customer'] = isset( $order['customer_id'] ) && $order['customer_id']
      ? $this->get_customer( $order['customer_id'] )
      : $this->get_customer_from_order( $order_id );

    return $order;
  }

  private function get_shipping_method() {
    $rate_table = array();

    $shipping_methods = WC()->shipping->get_shipping_methods();

    foreach($shipping_methods as $shipping_method){
      $shipping_method->init();

      foreach($shipping_method->rates as $key => $rate)
        $meta[$key] = $rate->get_meta_data();

      if ($rate->method_id !== 'integrai_shipping_method') {
        return array();
      }

      $rate_table[$key] = array(
        'id' => $rate->id,
        'method_id' => $rate->method_id,
        'instance_id' => $rate->instance_id,
        'label' => $rate->label,
        'cost' => $rate->cost,
        'taxes' => $rate->taxes,
        'code' => $meta['code'],
        'description' => $meta['carrier_title'] . ' - ' . $meta['description'],
        'carrier_title' => $meta['carrier_title'],
      );
    }

    return $rate_table[WC()->session->get( 'chosen_shipping_methods' )[0]];
  }

  private function get_product_by_id($id) {
    $product = new WC_Product( $id );

    return array(
      'id' => $product->get_id(),
      'sku' => $product->get_sku(),
      'name' => $product->get_name(),
      'description' => $product->get_description(),
    );
  }

  private function get_product_categories ( $product_id ) {
    $product_categories = array();
    $categories = get_the_terms( $product_id, 'product_cat' );

    foreach ( $categories as $category ) {
      array_push($product_categories, $category->to_array());
    }

    return $product_categories;
  }

  private function get_items( $order_id ) {
    $order = wc_get_order( $order_id );
    $products = array();

    foreach ( $order->get_items() as $item ) {
      $product = new WC_Product( $item->get_product_id() );

      array_push($products, array(
        'id' => $item->get_product_id(),
        'sku' => $product->get_sku(),
        'name' => $product->get_name(),
        'description' => $product->get_description(),
        'qty' => $item->get_quantity(),
        'price' => $order->get_line_total( $item, true, false ),
        'weight' => $product->get_weight(),
        'categories' => $this->get_product_categories( $item->get_product_id() ),
      ));
    }

    return $products;
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

	private function get_payment( $order_id, $raw = false ) {
    $data = $this->get_payment_helper()->get_transaction_data( $order_id );

    if ( !isset( $data ) || empty( $data ) ) {
      return array();
    }

    if ($data['payment_method'] === 'integrai_pix') {
      $transformed_data = array(
        'doc_type'        => $data['pix_doc_type'],
        'doc_number'      => $data['pix_doc_number'],
        'first_name'      => $data['pix_first_name'],
        'last_name'       => $data['pix_last_name'],
        'company_name'    => $data['pix_company_name'],
        'address_zipcode' => $data['pix_address_zipcode'],
        'address_street'  => $data['pix_address_street'],
        'address_number'  => $data['pix_address_number'],
        'address_city'    => $data['pix_address_city'],
        'address_state'   => $data['pix_address_state'],
      );

      if ( $raw ) {
        $transformed_data['payment_method'] = $data['payment_method'];
        return $transformed_data;
      }

      return array( 'pix' => $transformed_data );
    }

    if ($data['payment_method'] === 'integrai_boleto') {
      $transformed_data = array(
        'doc_type'        => $data['boleto_doc_type'],
        'doc_number'      => $data['boleto_doc_number'],
        'first_name'      => $data['boleto_first_name'],
        'last_name'       => $data['boleto_last_name'],
        'company_name'    => $data['boleto_company_name'],
        'address_zipcode' => $data['boleto_address_zipcode'],
        'address_street'  => $data['boleto_address_street'],
        'address_number'  => $data['boleto_address_number'],
        'address_city'    => $data['boleto_address_city'],
        'address_state'   => $data['boleto_address_state'],
      );

      if ( $raw ) {
        $transformed_data['payment_method'] = $data['payment_method'];
        return $transformed_data;
      }

      return array( 'boleto' => $transformed_data );
    }

    if ($data['payment_method'] === 'integrai_creditcard') {
      $transformed_data = array(
        'doc_type'                 => $data['cc_doc_type'],
        'doc_number'               => $data['cc_doc_number'],
        'birth_date'               => $data['cc_birth_date'],
        'holder_name'              => $data['cc_holder_name'],
        'installments'             => $data['cc_installments'],
        'installment_amount'       => $data['cc_installment_amount'],
        'installment_total_amount' => $data['cc_installment_total_amount'],
        'card_hashs'               => $data['cc_card_hashs'],
        'card_brands'              => $data['cc_card_brands'],
        'card_brand'               => $data['cc_card_brand'],
      );

      if ( $raw ) {
        $transformed_data['payment_method'] = $data['payment_method'];
        return $transformed_data;
      }

      return array( 'creditcard' => $transformed_data );
    }
  }

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

		wp_enqueue_script( 'integrai-public.js', plugin_dir_url( __FILE__ ) . 'js/integrai-public.js', array( 'jquery' ), $this->version, true );

	}

	public function rest_api_init() {
    require_once INTEGRAI__PLUGIN_DIR . 'includes/controller/class-integrai-controller-attributes.php';
    require_once INTEGRAI__PLUGIN_DIR . 'includes/controller/class-integrai-controller-config.php';
    require_once INTEGRAI__PLUGIN_DIR . 'includes/controller/class-integrai-controller-boleto.php';
    require_once INTEGRAI__PLUGIN_DIR . 'includes/controller/class-integrai-controller-pix.php';
    require_once INTEGRAI__PLUGIN_DIR . 'includes/controller/class-integrai-controller-events.php';
    require_once INTEGRAI__PLUGIN_DIR . 'includes/controller/class-integrai-controller-health.php';
    require_once INTEGRAI__PLUGIN_DIR . 'includes/controller/class-integrai-controller-categories.php';

    // ATTRIBUTES
    $integrai_attributes_controller = new Integrai_Attributes_Controller();
    $integrai_attributes_controller->register_routes();

    // CONFIG
    $integrai_config_controller = new Integrai_Config_Controller();
    $integrai_config_controller->register_routes();

    // EVENTS
    $integrai_events_controller = new Integrai_Events_Controller();
    $integrai_events_controller->register_routes();

    // BOLETO
    $integrai_boleto_controller = new Integrai_Boleto_Controller();
    $integrai_boleto_controller->register_routes();

    // PIX
    $integrai_pix_controller = new Integrai_Pix_Controller();
    $integrai_pix_controller->register_routes();

    // HEALTH
    $integrai_health_controller = new Integrai_Health_Controller();
    $integrai_health_controller->register_routes();

    // CATEGORIES
    $integrai_categories_controller = new Integrai_Categories_Controller();
    $integrai_categories_controller->register_routes();
	}

	/** EVENTS */

	// CREATE_CUSTOMER
	public function woocommerce_created_customer( $customer_id, $new_customer_data = null, $password_generated = null ) {

		if ( isset($customer_id) && $this->get_config_helper()->event_is_enabled(self::CREATE_CUSTOMER) ) {
			$customer = $this->get_customer( $customer_id );

			return $this->get_api_helper()->send_event(self::CREATE_CUSTOMER, $customer);
		}
	}

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

	// CREATE_ORDER
	public function woocommerce_new_order( $order ) {
	  $order_enabled = $this->get_config_helper()->event_is_enabled(self::CREATE_ORDER);
	  $order_item_enabled = $this->get_config_helper()->event_is_enabled(self::UPDATE_ORDER_ITEM);

    if ( isset($order) && $order_enabled ) {
      $full_order = $this->get_full_order( $order->get_id() );

      if ($order_item_enabled) {
        $has_items = isset($full_order['items']) && !empty($full_order['items']);
        $has_customer = isset($full_order['customer']) && !empty($full_order['customer']);

        if ( $has_items && $has_customer ) {
          foreach ($full_order['items'] as $item) {
            $item['order_id'] = $full_order['id'];
            $item['customer'] = $full_order['customer'];
            $item['payment'] = $full_order['payment'];
            $item['shipping_method'] = $full_order['shipping_method'];

            $this->get_api_helper()->send_event(self::UPDATE_ORDER_ITEM, $item);
          }
        }
      }

      $this->get_api_helper()->send_event(self::CREATE_ORDER, $full_order);
    }
	}

	// UPDATE_ORDER
	public function woocommerce_update_order( $order_id ) {
    $order = $this->get_full_order( $order_id );

		return $this->get_api_helper()->send_event(self::UPDATE_ORDER, $order);
	}

	// UPDATE_ORDER
	public function woocommerce_order_status_cancelled( $order_id ) {
		$order = new WC_Order($order_id);
		$customer = $this->get_customer( $order->get_customer_id() );

		$payload = $order->get_data();
		$payload['customer'] = $customer;

		return $this->get_api_helper()->send_event(self::UPDATE_ORDER, $payload);
	}

	// UPDATE_ORDER
	public function woocommerce_order_refunded( $order_id, $refund_id = false ) {
		$order = $this->get_order( $order_id );
		$customer = $this->get_customer( $order['customer_id'] );
		$refund = $refund_id ? $this->get_refund( $refund_id ) : null;

		$order['refund'] = $refund;
		$order['customer'] = $customer;
    $order['items'] = $this->get_items( $order_id );

		return $this->get_api_helper()->send_event(self::REFUND_INVOICE, $order);
	}

    // SAVE PRODUCT
    public function woocommerce_save_product( $post_id, $post, $update ) {
	    $ignored_status = array(
	        "auto-draft",
	        "draft",
	        "trash",
        );

        if ($post->post_type != 'product' || in_array($post->post_status, $ignored_status)) {
            return;
        }

        $event = strpos( wp_get_raw_referer(), 'post-new' ) ? self::CREATE_PRODUCT : self::UPDATE_PRODUCT;
        $product = wc_get_product( $post );

        if (isset($product) && $this->get_config_helper()->event_is_enabled($event)) {
            $data = $this->enrichProductAttributes($product);
            $data['type'] = $product->get_type();
            $data['photos'] = $this->getProductPhotos($product);
            $data['categories'] = $this->getProductCategories($product);

            if ($product->get_type() == 'variable') {
                $variations = $product->get_children();

                $data['variations'] = array();
                foreach ($variations as $variation) {
                    $productVariation = wc_get_product( $variation );;
                    $variationData = $this->enrichProductAttributes($productVariation);
                    $variationData['photos'] = $this->getProductPhotos($productVariation);
                    $variationData['categories'] = $this->getProductCategories($productVariation);
                    array_push($data['variations'], $variationData);
                }
            }

            return $this->get_api_helper()->send_event($event, $data);
        }
    }

    private function enrichProductAttributes($product) {
        $data = $product->get_data();
        $attributesKeys = array_keys($product->get_attributes());

        foreach($attributesKeys as $attributesKey) {
            $data[str_replace('pa_', '', $attributesKey)] = $product->get_attribute($attributesKey);
        }

        return $data;
    }

    private function getProductPhotos($product) {
        $photos = array(
            wp_get_attachment_image_url( $product->get_image_id(), 'full' )
        );

        foreach ($product->get_gallery_image_ids() as $imageId) {
            $photos[] = wp_get_attachment_image_url( $imageId, 'full' );
        }

        return $photos;
    }

    private function getProductCategories($product) {
        $categoriesList = array();
        $categoryIds = $product->get_category_ids();

        if (is_array($categoryIds) && count($categoryIds) > 0) {
            foreach ($categoryIds as $categoryId) {
                $category = get_term_by( 'id', $categoryId, 'product_cat' );
                $categoriesList[] = array(
                    "id" => $category->term_id,
                    "label" => $category->name
                );
            }
        }

        return $categoriesList;
    }

    // DELETE PRODUCT
    public function woocommerce_delete_product( $post_id, $post ) {
        if ($post->post_type != 'product') {
            return;
        }

        $product = wc_get_product( $post );

        if (isset($product) && $this->get_config_helper()->event_is_enabled(self::DELETE_PRODUCT)) {
            return $this->get_api_helper()->send_event(self::DELETE_PRODUCT, $product->get_data());
        }
    }


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
		if ( !wp_next_scheduled( 'integrai_cron_resend_events' ) ) {
			wp_schedule_event( time(), 'integrai_every_minute', 'integrai_cron_resend_events' );
		}

		if ( !wp_next_scheduled( 'integrai_cron_abandoned_cart' ) ) {
			wp_schedule_event( time(), 'integrai_every_minute', 'integrai_cron_abandoned_cart' );
		}

		if ( !wp_next_scheduled( 'integrai_cron_proccess_events' ) ) {
		 	wp_schedule_event( time(), 'integrai_every_minute', 'integrai_cron_proccess_events' );
		}
	}

	public function integrai_cron_proccess_events() {
        $CronProcessEvents = new Integrai_Cron_Process_Events();
        $CronProcessEvents->execute();
    }

	public function integrai_cron_deactivation() {
		$events_timestamp = wp_next_scheduled( 'integrai_cron_resend_events' );
		wp_unschedule_event($events_timestamp, 'integrai_cron_resend_events');
	}

	// ABANDONED_CART
	public function integrai_cron_abandoned_cart() {
    Integrai_Helper::log('==> executed integrai_cron_abandoned_cart');
	  $isEnabled = $this->get_config_helper()->event_is_enabled(self::ABANDONED_CART);
	  $isEnabledCartItem = $this->get_config_helper()->event_is_enabled(self::ABANDONED_CART_ITEM);

		if ( $isEnabled ) {
			$cart_lifetime = $this->get_config_helper()->get_minutes_abandoned_cart_lifetime();
			$minutes = $cart_lifetime ? $cart_lifetime : 60;
			$from_date = date('Y-m-d H:i:s', strtotime('-' . $minutes . ' minutes'));
			$cart_created = date('Y-m-d H:i:s', strtotime("now"));

			$sessions = $this->get_customer_sessions();

			foreach ($sessions as $session) {
				$sessionCart = $session['cart'];

				if (  isset( $sessionCart ) && is_array($sessionCart) && count($sessionCart) > 0 ) {
          foreach ($sessionCart as $product) {
            $created_at = $product['created_at'];

            if ($created_at < $cart_created) {
              $cart_created = $created_at;
            }
          }

          if ($cart_created < $from_date) {
            $date = new DateTime();

            $cart['cart_id'] = $date->getTimestamp();
            $cart['created_at'] = $cart_created;
            $cart['customer'] = $session['customer'];
            $cart['cart'] = $session['cart'];
            $cart['cart_totals'] = $session['cart_totals'];
            $cart['products'] = array();
            $cart['total_items'] = 0;

            foreach ($sessionCart as $cartItem) {
              if ( isset($cartItem['product_id']) ) {
                $productItem = $this->get_product_by_id($cartItem['product_id']);
                $productItem['cart_id'] = $cart['cart_id'];
                $productItem['customer'] = $cart['customer'];
                $productItem['quantity'] = $cartItem['quantity'];
                $productItem['total_price'] = $cartItem['line_total'];
                $productItem['subtotal_price'] = $cartItem['line_subtotal'];
                $cart['total_items'] = $cart['total_items'] + $cartItem['quantity'];

                array_push($cart['products'], $productItem);

                if ( $isEnabledCartItem ) {
                  $this->get_api_helper()->send_event(self::ABANDONED_CART_ITEM, $productItem);
                }
              }
            }

            if ( !empty($cart) ) {
              $this->get_api_helper()->send_event(self::ABANDONED_CART, $cart);
            }
          }
        }
			}
		}
	}

	public function integrai_cron_resend_events() {
    $options = get_option('woocommerce_integrai-settings_settings');
		$is_enabled = $options['enabled'];

		$pending_events = $this->get_events_helper()->get_pending_events();

		if ( $is_enabled && count( $pending_events ) > 0 ) {
			foreach( $pending_events as $event ) {
				try {
					$event_id = $event->id;
					$event_name = $event->event;
					$payload = json_decode($event->payload, true);

					$this->get_api_helper()->send_event($event_name, $payload);
					$this->get_events_helper()->delete_by_id( $event_id );

				} catch (Throwable $e) {
					Integrai_helper::log('Error ao reenviar o evento', $event_name);
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

	/** CHECKOUT */
	// PAYMENT METHODS
	public function woocommerce_payment_gateways($methods) {
        if ( ! class_exists( 'Integrai_Payment_Method_MarketPlace' ) ) :
          include_once INTEGRAI__PLUGIN_DIR . 'public/class-integrai-payment-method-market-place.php';
        endif;

        if ( ! class_exists( 'Integrai_Payment_Method_Pix' ) ) :
          include_once INTEGRAI__PLUGIN_DIR . 'public/class-integrai-payment-method-pix.php';
        endif;

        if ( ! class_exists( 'Integrai_Payment_Method_Boleto' ) ) :
          include_once INTEGRAI__PLUGIN_DIR . 'public/class-integrai-payment-method-boleto.php';
        endif;

        if ( ! class_exists( 'Integrai_Payment_Method_Credit_Card' ) ) :
          include_once INTEGRAI__PLUGIN_DIR . 'public/class-integrai-payment-method-credit-card.php';
        endif;

		$methods[] = 'Integrai_Payment_Method_MarketPlace';
		$methods[] = 'Integrai_Payment_Method_Pix';
		$methods[] = 'Integrai_Payment_Method_Boleto';
		$methods[] = 'Integrai_Payment_Method_Credit_Card';

		return $methods;
	}

}