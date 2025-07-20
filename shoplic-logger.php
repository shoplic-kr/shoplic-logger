<?php
/**
 * Plugin Name: 쇼플릭 로거
 * Plugin URI: https://shoplic.kr
 * Description: 파일에 로그를 남깁니다. AI에게 로그 데이터를 넘길때 사용할 수 있습니다.
 * Version: 1.0.0
 * Author: shoplic
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants only if not already defined
if ( ! defined( 'SL_VERSION' ) ) {
    define( 'SL_VERSION', '1.0.0' );
}
if ( ! defined( 'SL_LOG_DIR' ) ) {
    define( 'SL_LOG_DIR', WP_CONTENT_DIR . '/sl-logs' );
}
if ( ! defined( 'SL_PLUGIN_DIR' ) ) {
    define( 'SL_PLUGIN_DIR', dirname( __FILE__ ) . '/shoplic-logger' );
}

// Load required files
require_once SL_PLUGIN_DIR . '/inc/class-sl-logger.php';
require_once SL_PLUGIN_DIR . '/inc/class-sl-admin-viewer.php';
require_once SL_PLUGIN_DIR . '/inc/class-sl-ajax-handler.php';
require_once SL_PLUGIN_DIR . '/inc/class-sl-debug-settings.php';
require_once SL_PLUGIN_DIR . '/inc/class-sl-sysinfo-reporter.php';
require_once SL_PLUGIN_DIR . '/inc/class-wpconfigtransformer.php';
require_once SL_PLUGIN_DIR . '/inc/helpers.php';

// Initialize the plugin
if ( ! function_exists( 'sl_init_plugin' ) ) {
    function sl_init_plugin() {
        // Create log directory if it doesn't exist
        if ( ! file_exists( SL_LOG_DIR ) ) {
            wp_mkdir_p( SL_LOG_DIR );
            
            // Add .htaccess for security
            $htaccess = SL_LOG_DIR . '/.htaccess';
            if ( ! file_exists( $htaccess ) ) {
                file_put_contents( $htaccess, 'deny from all' );
            }
        }
        
        // Schedule cleanup if not already scheduled
        if ( ! wp_next_scheduled( 'sl_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'sl_cleanup_logs' );
        }
        
        // Initialize components if in admin
        if ( is_admin() ) {
            new SL_Admin_Viewer();
            new SL_Ajax_Handler();
        }
    }
}

// Hook initialization
add_action( 'init', 'sl_init_plugin' );

// Schedule cleanup
add_action( 'sl_cleanup_logs', array( 'SL', 'cleanup_old_logs' ) );

// For plugin activation (when used as regular plugin)
register_activation_hook( __FILE__, 'sl_init_plugin' );

// For plugin deactivation
if ( ! function_exists( 'sl_deactivate_plugin' ) ) {
    function sl_deactivate_plugin() {
        // Remove scheduled cleanup
        wp_clear_scheduled_hook( 'sl_cleanup_logs' );
    }
}
register_deactivation_hook( __FILE__, 'sl_deactivate_plugin' );