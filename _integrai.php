<?php
/**
 * Plugin Name: Integrai
 * Plugin URI: https://integrai.com.br
 * Description: Integração com os principais meios de pagamento e cálculo de frete para a sua plataforma de e-commerce WP WooCommerce.
 * Version: 1.0.0
 * Author: Integrai
 * Author URI: https://integrai.com.br
 * License: GPLv2 or later
 * Text Domain: integrai
 *
 * Class WC_Integrai
 *
 * @package Integrai
 *
*/

/** TODO:
 * 1. Criar as tabelas de Config e Eventos ao ativar o componente
 * 2. Ao Ativar, verificar se as tabelas já existe. Se sim, atualizar. Se não, criar.
 * 3. Ao desinstalar, remover tabelas e registros
 * 4. Mapear os eventos para comunicar a API quando acontecerem
 * 5. Verificar Cron Job para chamar os eventos que n tiveram sucesso
 * 6.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Checks if WooCommerce is installed and activated
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

// When plugin is activated
register_activation_hook( __FILE__, 'integrai_init' );

// Include the main WooCommerce class.
function integrai_init() {
  // echo 'INIT INTEGRAI';

  config();
  load_classes();
}

function config() {
  define( 'INTEGRAI__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

function load_classes() {
  if ( ! class_exists( 'Integrai', false ) ) include_once INTEGRAI__PLUGIN_DIR . '/includes/class-integrai.php';
  if ( ! class_exists( 'Integrai_Activator', false ) ) include_once INTEGRAI__PLUGIN_DIR . '/includes/class-integrai-install.php';

  Integrai_Activator::init();
}
