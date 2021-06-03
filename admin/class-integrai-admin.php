<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://integrai.com.br
 * @since      1.0.0
 *
 * @package    Integrai
 * @subpackage Integrai/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Integrai
 * @subpackage Integrai/admin
 * @author     Integrai <contato@integrai.com.br>
 */
class Integrai_Admin {

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

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $integrai       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $integrai, $version ) {

		$this->integrai = $integrai;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->integrai, plugin_dir_url( __FILE__ ) . 'css/integrai-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->integrai, plugin_dir_url( __FILE__ ) . 'js/integrai-admin.js', array( 'jquery' ), $this->version, false );

	}

	private function check_woocommerce() {
		return ( class_exists( 'woocommerce' ) );
	}

	private function dangerAlert() {
        require_once INTEGRAI__PLUGIN_DIR . '/includes/model/class-integrai-model-config.php';

        $Config = new Integrai_Model_Config();
		return $Config->get_danger_alert();
	}

	public function admin_notices() {
		if ( !$this->check_woocommerce() ) {
			?>
				<div class="notice notice-error is-dismissible">
					<p><a href="https://wordpress.org/plugins/woocommerce/" target="__blank">WooCommerce</a> precisa estar instalado e ativado para usar o plugin <b>Integrai</b>.</p>
				</div>
			<?php
		}

        if ( $this->dangerAlert() ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>ATENÇÃO: </strong> <?php echo $this->dangerAlert() ?></p>
            </div>
            <?php
        }
	}

	public function woocommerce_integrations( $integrations ) {
		require_once INTEGRAI__PLUGIN_DIR . 'admin/wc-config/class-wc-integration-integrai-settings-integration.php';

		array_unshift($integrations, 'WC_Integration_Integrai_Settings_Integration');

		return $integrations;
	}
}