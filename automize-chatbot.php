<?php
/**
 * Plugin Name: Automize Branded Chatbot
 * Description: Modern animated chatbot with AutoMize branding - Full animation support with gradient backgrounds, typing indicators, and smooth transitions
 * Version: 7.0
 * Author: Mohamed Hisham - Automize
 * Text Domain: automize-chatbot
 * Domain Path: /languages
 *
 * @package AutoMize_Chatbot
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ==========================================
// Constants
// ==========================================
define( 'AUTOMIZE_CHAT_VERSION', '7.0' );
define( 'AUTOMIZE_CHAT_DB_VERSION', '7.0' );
define( 'AUTOMIZE_CHAT_PATH', plugin_dir_path( __FILE__ ) );
define( 'AUTOMIZE_CHAT_URL', plugin_dir_url( __FILE__ ) );
define( 'AUTOMIZE_CHAT_BASENAME', plugin_basename( __FILE__ ) );

// ==========================================
// Autoloader
// ==========================================
require_once AUTOMIZE_CHAT_PATH . 'includes/core/class-autoloader.php';
AutoMize_Chatbot_Autoloader::register();

// ==========================================
// Main Plugin Class
// ==========================================
final class AutoMize_Chatbot {

    /**
     * Plugin instance.
     *
     * @var AutoMize_Chatbot|null
     */
    private static $instance = null;

    /**
     * Database handler.
     *
     * @var AutoMize_Chatbot_Database
     */
    private $database;

    /**
     * Session repository.
     *
     * @var AutoMize_Chatbot_Session_Repository
     */
    private $sessions;

    /**
     * Message repository.
     *
     * @var AutoMize_Chatbot_Message_Repository
     */
    private $messages;

    /**
     * Statistics handler.
     *
     * @var AutoMize_Chatbot_Statistics
     */
    private $statistics;

    /**
     * Geolocation handler.
     *
     * @var AutoMize_Chatbot_Geolocation
     */
    private $geolocation;

    /**
     * Get the singleton instance.
     *
     * @return AutoMize_Chatbot
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor - use get_instance() instead.
     */
    private function __construct() {
        $this->init_components();
        $this->init_hooks();
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserialization.
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }

    /**
     * Initialize core components.
     */
    private function init_components() {
        // Core services
        $this->database    = new AutoMize_Chatbot_Database();
        $this->geolocation = new AutoMize_Chatbot_Geolocation();
        
        // Repositories (depend on database)
        $this->sessions  = new AutoMize_Chatbot_Session_Repository();
        $this->messages  = new AutoMize_Chatbot_Message_Repository();
        $this->statistics = new AutoMize_Chatbot_Statistics( $this->sessions, $this->messages );
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        // Init action
        add_action( 'init', array( $this, 'on_init' ) );
        
        // Initialize admin or frontend based on context
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
        
        // Register REST API
        add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );
        
        // Register AJAX handlers
        add_action( 'wp_ajax_automize_save_message', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_nopriv_automize_save_message', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_automize_start_session', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_nopriv_automize_start_session', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_automize_update_location', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_nopriv_automize_update_location', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_automize_update_session_status', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_nopriv_automize_update_session_status', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_automize_get_chats_list', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_automize_get_chat_updates', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_automize_get_chat', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_automize_delete_chats', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_automize_export_chats', array( $this, 'get_ajax_handler' ) );
        add_action( 'wp_ajax_automize_update_chat_status', array( $this, 'get_ajax_handler' ) );
    }

    /**
     * Init action callback.
     */
    public function on_init() {
        // Load text domain
        load_plugin_textdomain( 'automize-chatbot', false, dirname( AUTOMIZE_CHAT_BASENAME ) . '/languages' );
    }

    /**
     * Plugins loaded callback - initialize admin or frontend.
     */
    public function on_plugins_loaded() {
        // Check and update database if needed
        $this->maybe_update_database();
        
        // Initialize admin
        if ( is_admin() ) {
            new AutoMize_Chatbot_Admin_Controller(
                $this->sessions,
                $this->messages,
                $this->statistics,
                $this->geolocation
            );
        }
        
        // Initialize frontend (always, for chatbot widget)
        new AutoMize_Chatbot_Frontend( $this->sessions, $this->geolocation );
    }

    /**
     * Register REST API routes.
     */
    public function register_rest_api() {
        $rest_api = new AutoMize_Chatbot_REST_API( $this->sessions, $this->messages );
        $rest_api->register_routes();
    }

    /**
     * Get the AJAX handler instance.
     *
     * @return AutoMize_Chatbot_AJAX
     */
    public function get_ajax_handler() {
        static $ajax = null;
        if ( null === $ajax ) {
            $ajax = new AutoMize_Chatbot_AJAX(
                $this->sessions,
                $this->messages,
                $this->statistics,
                $this->geolocation
            );
        }
        
        // Determine which action to handle
        $action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : '';
        
        switch ( $action ) {
            case 'automize_save_message':
                $ajax->save_message();
                break;
            case 'automize_start_session':
                $ajax->start_session();
                break;
            case 'automize_update_location':
                $ajax->update_location();
                break;
            case 'automize_update_session_status':
                $ajax->update_session_status();
                break;
            case 'automize_get_chats_list':
                $ajax->get_chats_list();
                break;
            case 'automize_get_chat_updates':
                $ajax->get_chat_updates();
                break;
            case 'automize_get_chat':
                $ajax->get_chat();
                break;
            case 'automize_delete_chats':
                $ajax->delete_chats();
                break;
            case 'automize_export_chats':
                $ajax->export_chats();
                break;
            case 'automize_update_chat_status':
                $ajax->update_chat_status();
                break;
        }
    }

    /**
     * Check if database needs updating and run updates.
     */
    private function maybe_update_database() {
        $current_version = get_option( 'automize_chat_db_version', '1.0' );
        
        if ( version_compare( $current_version, AUTOMIZE_CHAT_DB_VERSION, '<' ) ) {
            $this->database->upgrade_tables( $current_version );
            update_option( 'automize_chat_db_version', AUTOMIZE_CHAT_DB_VERSION );
        }
    }

    // ==========================================
    // Accessor Methods
    // ==========================================

    /**
     * Get database handler.
     *
     * @return AutoMize_Chatbot_Database
     */
    public function database() {
        return $this->database;
    }

    /**
     * Get session repository.
     *
     * @return AutoMize_Chatbot_Session_Repository
     */
    public function sessions() {
        return $this->sessions;
    }

    /**
     * Get message repository.
     *
     * @return AutoMize_Chatbot_Message_Repository
     */
    public function messages() {
        return $this->messages;
    }

    /**
     * Get statistics handler.
     *
     * @return AutoMize_Chatbot_Statistics
     */
    public function statistics() {
        return $this->statistics;
    }

    /**
     * Get geolocation handler.
     *
     * @return AutoMize_Chatbot_Geolocation
     */
    public function geolocation() {
        return $this->geolocation;
    }
}

// ==========================================
// Plugin Activation
// ==========================================
register_activation_hook( __FILE__, function() {
    require_once AUTOMIZE_CHAT_PATH . 'includes/core/class-autoloader.php';
    AutoMize_Chatbot_Autoloader::register();
    
    $database = new AutoMize_Chatbot_Database();
    $database->create_tables();
    
    update_option( 'automize_chat_db_version', AUTOMIZE_CHAT_DB_VERSION );
    flush_rewrite_rules();
} );

// ==========================================
// Plugin Deactivation
// ==========================================
register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );

// ==========================================
// Uninstall Hook (optional - for cleanup)
// ==========================================
// Note: For complete uninstall, create uninstall.php file

// ==========================================
// Initialize Plugin
// ==========================================
add_action( 'plugins_loaded', function() {
    AutoMize_Chatbot::get_instance();
}, 5 ); // Priority 5 to run before normal plugins_loaded hooks

// ==========================================
// Helper Function to Access Plugin
// ==========================================
/**
 * Get the main plugin instance.
 *
 * @return AutoMize_Chatbot
 */
function automize_chatbot() {
    return AutoMize_Chatbot::get_instance();
}

// ==========================================
// Admin Notice on Activation
// ==========================================
add_action( 'admin_notices', function() {
    $screen = get_current_screen();
    if ( $screen && $screen->base === 'plugins' ) {
        $activated = get_transient( 'automize_chatbot_activated' );
        if ( $activated ) {
            delete_transient( 'automize_chatbot_activated' );
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>✨ Automize Branded Chatbot</strong> تم التفعيل بنجاح! 
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=automize-chats' ) ); ?>">عرض المحادثات</a>
                </p>
            </div>
            <?php
        }
    }
} );
