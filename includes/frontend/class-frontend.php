<?php
/**
 * Frontend Controller for AutoMize Chatbot
 *
 * Handles frontend chatbot display and assets.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_Frontend
 *
 * Controls frontend chatbot functionality.
 */
class AutoMize_Chatbot_Frontend {

    /**
     * Session repository.
     *
     * @var AutoMize_Chatbot_Session_Repository
     */
    private $sessions;

    /**
     * Geolocation handler.
     *
     * @var AutoMize_Chatbot_Geolocation
     */
    private $geolocation;

    /**
     * Constructor.
     *
     * @param AutoMize_Chatbot_Session_Repository $sessions    Session repository.
     * @param AutoMize_Chatbot_Geolocation        $geolocation Geolocation handler.
     */
    public function __construct( $sessions, $geolocation ) {
        $this->sessions    = $sessions;
        $this->geolocation = $geolocation;

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_chatbot' ) );
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_assets() {
        // Don't load in admin.
        if ( is_admin() ) {
            return;
        }

        // CSS.
        wp_enqueue_style(
            'automize-chatbot-css',
            AUTOMIZE_CHAT_URL . 'assets/frontend/css/chatbot.css',
            array(),
            AUTOMIZE_CHAT_VERSION
        );

        // JavaScript.
        wp_enqueue_script(
            'automize-chatbot-js',
            AUTOMIZE_CHAT_URL . 'assets/frontend/js/chatbot.js',
            array(),
            AUTOMIZE_CHAT_VERSION,
            true
        );

        // Localize script.
        wp_localize_script(
            'automize-chatbot-js',
            'automizeChatConfig',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'restUrl'   => rest_url( 'automize-chat/v1/' ),
                'nonce'     => wp_create_nonce( 'automize_chat_frontend' ),
                'sessionId' => $this->get_or_create_session_id(),
            )
        );
    }

    /**
     * Render chatbot HTML.
     */
    public function render_chatbot() {
        // Don't render in admin.
        if ( is_admin() ) {
            return;
        }

        // Load template.
        include AUTOMIZE_CHAT_PATH . 'templates/chatbot-widget.php';
    }

    /**
     * Get or create session ID.
     *
     * @return string
     */
    private function get_or_create_session_id() {
        if ( isset( $_COOKIE['automize_chat_session'] ) ) {
            return sanitize_text_field( wp_unslash( $_COOKIE['automize_chat_session'] ) );
        }

        return 'chat_' . bin2hex( random_bytes( 16 ) );
    }
}
