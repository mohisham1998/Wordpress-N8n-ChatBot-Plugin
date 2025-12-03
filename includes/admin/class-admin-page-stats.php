<?php
/**
 * Admin Page - Stats for AutoMize Chatbot
 *
 * Renders the statistics admin page.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_Admin_Page_Stats
 *
 * Statistics page.
 */
class AutoMize_Chatbot_Admin_Page_Stats {

    /**
     * Statistics handler.
     *
     * @var AutoMize_Chatbot_Statistics
     */
    private $statistics;

    /**
     * Constructor.
     *
     * @param AutoMize_Chatbot_Statistics $statistics Statistics handler.
     */
    public function __construct( $statistics ) {
        $this->statistics = $statistics;
    }

    /**
     * Render the stats page.
     */
    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'ليس لديك صلاحية للوصول إلى هذه الصفحة' );
        }

        $stats = $this->statistics->get_stats();

        // Load template.
        include AUTOMIZE_CHAT_PATH . 'templates/admin-stats.php';
    }
}
