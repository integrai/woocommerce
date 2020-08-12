<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://integrai.com.br
 * @since             1.0.0
 * @package           Integrai
 *
 * @integrai
 * Plugin Name:       Integrai
 * Plugin URI:        https://integrai.com.br/
 * Description: Integração com os principais meios de pagamento e cálculo de frete para a sua plataforma de e-commerce WP WooCommerce.
 * Version:           1.0.0
 * Author:            Integrai
 * Author URI:        https://integrai.com.br/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       integrai
 * Domain Path:       /languages
 */

/** GERAL
 * 1. Banco Dados [OK]
 * 2. Logs
 * 3. Requests com API
 * 4. Injeção de configs
 * 5. Retentativa (Backup) de Eventos
 * 6. Events
 * 7. Shipping (Verificar como o woocommerce chama)
 * 8. Checkout
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGIN_NAME_VERSION', '1.0.0' );
if ( ! defined( 'INTEGRAI__PLUGIN_DIR' ) ) define( 'INTEGRAI__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-integrai-activator.php
 */
function activate_integrai() {
	require_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-activator.php';
	Integrai_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-integrai-deactivator.php
 */
function deactivate_integrai() {
	require_once INTEGRAI__PLUGIN_DIR . 'includes/class-integrai-deactivator.php';
	Integrai_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_integrai' );
register_deactivation_hook( __FILE__, 'deactivate_integrai' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require INTEGRAI__PLUGIN_DIR . 'includes/class-integrai.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_integrai() {

	$plugin = new Integrai();
	$plugin->run();

}
run_integrai();
