<?php
if ( ! class_exists( 'Integrai_Shipping_Methods' ) ) :
  class Integrai_Shipping_Methods extends WC_Shipping_Method {
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct() {
      $this->id                 = 'integrai_shipping_method'; // Id for your shipping method. Should be uunique.
      $this->method_title       = __( 'Integrai' );  // Title shown in admin

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

      $rate = $shipping_helper->quote($package);

      Integrai_Helper::log($rate, '$rate: ');

      $this->add_rate( $rate );
    }
  }
endif;
