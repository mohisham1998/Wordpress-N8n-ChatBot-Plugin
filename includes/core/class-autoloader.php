<?php
/**
 * Autoloader for AutoMize Chatbot Plugin
 *
 * WordPress-style autoloader for all plugin classes.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_Autoloader
 *
 * Handles automatic loading of plugin classes.
 */
class AutoMize_Chatbot_Autoloader {

    /**
     * Class prefix for the plugin.
     *
     * @var string
     */
    private static $prefix = 'AutoMize_Chatbot_';

    /**
     * Class to file mapping for specific directories.
     *
     * @var array
     */
    private static $class_map = array(
        // Core classes
        'AutoMize_Chatbot_Database'            => 'includes/core/class-database.php',
        'AutoMize_Chatbot_Session_Repository'  => 'includes/core/class-session-repository.php',
        'AutoMize_Chatbot_Message_Repository'  => 'includes/core/class-message-repository.php',
        'AutoMize_Chatbot_Statistics'          => 'includes/core/class-statistics.php',
        'AutoMize_Chatbot_Geolocation'         => 'includes/core/class-geolocation.php',
        
        // Admin classes
        'AutoMize_Chatbot_Admin_Controller'    => 'includes/admin/class-admin-controller.php',
        'AutoMize_Chatbot_Admin_Page_Chats'    => 'includes/admin/class-admin-page-chats.php',
        'AutoMize_Chatbot_Admin_Page_Stats'    => 'includes/admin/class-admin-page-stats.php',
        
        // Frontend classes
        'AutoMize_Chatbot_Frontend'            => 'includes/frontend/class-frontend.php',
        
        // API classes
        'AutoMize_Chatbot_REST_API'            => 'includes/api/class-rest-api.php',
        'AutoMize_Chatbot_AJAX'                => 'includes/api/class-ajax.php',
    );

    /**
     * Register the autoloader.
     */
    public static function register() {
        spl_autoload_register( array( __CLASS__, 'autoload' ) );
    }

    /**
     * Autoload callback.
     *
     * @param string $class The fully-qualified class name.
     */
    public static function autoload( $class ) {
        // Check if the class uses our prefix.
        if ( strpos( $class, self::$prefix ) !== 0 && $class !== 'AutoMize_Chatbot' ) {
            return;
        }

        // Check if we have a direct mapping.
        if ( isset( self::$class_map[ $class ] ) ) {
            $file = AUTOMIZE_CHAT_PATH . self::$class_map[ $class ];
            if ( file_exists( $file ) ) {
                require_once $file;
                return;
            }
        }

        // Try to auto-detect the file location.
        $relative_class = str_replace( self::$prefix, '', $class );
        
        // Convert class name to file name (underscores to hyphens, lowercase).
        $file_name = 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';
        
        // Try each directory.
        $directories = array(
            'includes/core/',
            'includes/admin/',
            'includes/frontend/',
            'includes/api/',
        );
        
        foreach ( $directories as $dir ) {
            $file = AUTOMIZE_CHAT_PATH . $dir . $file_name;
            if ( file_exists( $file ) ) {
                require_once $file;
                return;
            }
        }
    }
}
