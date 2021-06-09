<?php

class Integrai_Admin {
	private $integrai;
	private $version;

	public function __construct( $integrai, $version ) {
		$this->integrai = $integrai;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->integrai, plugin_dir_url( __FILE__ ) . 'css/integrai-admin.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
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
					<p>
                        <a href="https://wordpress.org/plugins/woocommerce/" target="__blank">WooCommerce</a>
                        precisa estar instalado e ativado para usar o plugin <b>Integrai</b>.
                    </p>
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