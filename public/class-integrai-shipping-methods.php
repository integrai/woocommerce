<?php
if ( ! class_exists( 'Integrai_Shipping_Methods' ) ) :
  class Integrai_Shipping_Methods extends WC_Shipping_Method {
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct( $instance_id = 0 ) {
      $this->id                 = 'integrai_shipping_method'; // Id for your shipping method. Should be uunique.
      $this->instance_id 	      = absint( $instance_id );
      $this->title              = __( 'Integrai', 'woocommerce-integrai-settings' );  // Title shown in admin
      $this->method_title       = __( 'Integrai', 'woocommerce-integrai-settings' );  // Title shown in admin
      $this->method_description = __( 'Método de entrega da Integrai. Permite fazer cotação de frete com plataformas como Frenet e Intelipost.', 'woocommerce-integrai-settings' );  // Title shown in admin
      $this->supports           = array(
        'shipping-zones',
        'instance-settings'
      );

      $this->load_dependencies();

      $this->init();
    }

    private function load_dependencies() {

      include_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-shipping-helper.php';

    }

    private function get_shipping_helper() {
      return new Integrai_Shipping_Helper();
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init() {
      // Load the settings API
      $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
      $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

      // Define user set variables.
      $this->enabled          = $this->get_option( 'enabled' );
      $this->api_key          = $this->get_option( 'apiKey' );
      $this->secret_key       = $this->get_option( 'secretKey' );

      // Save settings in admin if you have any defined
      add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package = array() ) {

      $shipping_helper = $this->get_shipping_helper();

      $rate = $shipping_helper->quote( $package );

      $this->add_rate( $rate );
    }
  }
endif;
