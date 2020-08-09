<?php

define ( 'WCCF_NAME', 'Woocommerce Plugin Example' ) ;
define ( 'WCCF_REQUIRED_PHP_VERSION', '5.4' ); // because of get_called_class()
define ( 'WCCF_REQUIRED_WP_VERSION', '4.6' );  // because of esc_textarea()
define ( 'WCCF_REQUIRED_WC_VERSION', '2.6' );  // because of Shipping Class system

class Integrai_Helper {
  /**
   * Checks if the system requirements are met
   *
   * @return bool True if system requirements are met, false if not
   */
  static public function check_dependencies() {
    global $wp_version ;
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' ) ;  // to get is_plugin_active() early

    if ( version_compare ( PHP_VERSION, WCCF_REQUIRED_PHP_VERSION, '<' ) ) {
        return false ;
    }

    if ( version_compare ( $wp_version, WCCF_REQUIRED_WP_VERSION, '<' ) ) {
        return false ;
    }

    if ( ! is_plugin_active ( 'woocommerce/woocommerce.php' ) ) {
        return false ;
    }

    $woocommer_data = get_plugin_data(WP_PLUGIN_DIR .'/woocommerce/woocommerce.php', false, false);

    if (version_compare ($woocommer_data['Version'] , WCCF_REQUIRED_WC_VERSION, '<')){
        return false;
    }

    return true;
  }

  static public function call_error () {
    global $wp_version ;

    require_once( plugin_dir_path ( __FILE__ ) . '/admin/partials/requirements-error.php' ) ;
  }

  public static function register_action() {
    if ( wccf_requirements_met() ) {
      require_once( __DIR__ . '/classes/wpps-module.php' );
      require_once( __DIR__ . '/classes/wordpress-plugin-skeleton.php' );
      require_once( __DIR__ . '/includes/admin-notice-helper/admin-notice-helper.php' );
      require_once( __DIR__ . '/classes/wpps-custom-post-type.php' );
      require_once( __DIR__ . '/classes/wpps-cpt-example.php' );
      require_once( __DIR__ . '/classes/wpps-settings.php' );
      require_once( __DIR__ . '/classes/wpps-cron.php' );
      require_once( __DIR__ . '/classes/wpps-instance-class.php' );

      if ( class_exists( 'WordPress_Plugin_Skeleton' ) ) {
        $GLOBALS['wccf'] = WordPress_Plugin_Skeleton::get_instance();
        register_activation_hook(   __FILE__, array( $GLOBALS['wccf'], 'activate' ) );
        register_deactivation_hook( __FILE__, array( $GLOBALS['wccf'], 'deactivate' ) );
      }
    } else {
      add_action( 'admin_notices', 'check_dependencies' );
    }
  }

  public static function log($log, $prefix = '') {
        if (WP_DEBUG === true) {
            if (is_array($log) || is_object($log)) {
                error_log($prefix . print_r($log, true));
            } else {
                error_log($prefix . $log);
            }
        }
    }
}
