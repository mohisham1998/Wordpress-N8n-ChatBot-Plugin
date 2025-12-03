<?php
/**
 * Admin Controller for AutoMize Chatbot
 *
 * Main admin controller that initializes admin functionality.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_Admin_Controller
 *
 * Controls admin functionality.
 */
class AutoMize_Chatbot_Admin_Controller {

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
     * Chats page instance.
     *
     * @var AutoMize_Chatbot_Admin_Page_Chats
     */
    private $page_chats;

    /**
     * Stats page instance.
     *
     * @var AutoMize_Chatbot_Admin_Page_Stats
     */
    private $page_stats;

    /**
     * Constructor.
     *
     * @param AutoMize_Chatbot_Session_Repository $sessions    Session repository.
     * @param AutoMize_Chatbot_Message_Repository $messages    Message repository.
     * @param AutoMize_Chatbot_Statistics         $statistics  Statistics handler.
     * @param AutoMize_Chatbot_Geolocation        $geolocation Geolocation handler.
     */
    public function __construct( $sessions, $messages, $statistics, $geolocation ) {
        $this->sessions    = $sessions;
        $this->messages    = $messages;
        $this->statistics  = $statistics;
        $this->geolocation = $geolocation;

        // Initialize page controllers.
        $this->page_chats = new AutoMize_Chatbot_Admin_Page_Chats( $sessions, $messages, $statistics, $geolocation );
        $this->page_stats = new AutoMize_Chatbot_Admin_Page_Stats( $statistics );
        
        // Register hooks.
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Add admin menu pages.
     */
    public function add_admin_menu() {
        // Main menu.
        add_menu_page(
            'محادثات أوتومايز',
            'محادثات الشات',
            'manage_options',
            'automize-chats',
            array( $this->page_chats, 'render' ),
            'dashicons-format-chat',
            30
        );

        // Submenu - All Chats.
        add_submenu_page(
            'automize-chats',
            'جميع المحادثات',
            'جميع المحادثات',
            'manage_options',
            'automize-chats',
            array( $this->page_chats, 'render' )
        );

        // Submenu - Statistics.
        add_submenu_page(
            'automize-chats',
            'الإحصائيات',
            'الإحصائيات',
            'manage_options',
            'automize-chat-stats',
            array( $this->page_stats, 'render' )
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        // Only load on plugin pages.
        if ( false === strpos( $hook, 'automize-chat' ) ) {
            return;
        }

        // Ensure dashicons are loaded.
        wp_enqueue_style( 'dashicons' );

        // CSS.
        wp_enqueue_style(
            'automize-admin-chat',
            AUTOMIZE_CHAT_URL . 'assets/admin/css/admin-chat.css',
            array( 'dashicons' ),
            AUTOMIZE_CHAT_VERSION
        );

        // JavaScript.
        wp_enqueue_script(
            'automize-admin-chat',
            AUTOMIZE_CHAT_URL . 'assets/admin/js/admin-chat.js',
            array( 'jquery' ),
            AUTOMIZE_CHAT_VERSION,
            true
        );

        // Localize script.
        wp_localize_script(
            'automize-admin-chat',
            'automizeChat',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'automize_chat_nonce' ),
                'strings' => array(
                    'confirmDelete' => 'هل أنت متأكد من حذف المحادثات المحددة؟',
                    'noSelection'   => 'الرجاء تحديد محادثة واحدة على الأقل',
                    'loading'       => 'جاري التحميل...',
                    'error'         => 'حدث خطأ، يرجى المحاولة مرة أخرى',
                    'deleted'       => 'تم الحذف بنجاح',
                    'user'          => 'المستخدم',
                    'bot'           => 'البوت',
                ),
            )
        );
    }
}
