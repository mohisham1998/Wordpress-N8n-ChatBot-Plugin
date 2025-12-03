<?php
/**
 * Admin Page - Chats for AutoMize Chatbot
 *
 * Renders the main chats admin page.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_Admin_Page_Chats
 *
 * Chats listing page.
 */
class AutoMize_Chatbot_Admin_Page_Chats {

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
    }

    /**
     * Render the chats page.
     */
    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'ليس لديك صلاحية للوصول إلى هذه الصفحة' );
        }

        // Get parameters.
        $current_page  = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $per_page      = 20;
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $search        = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $date_from     = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to       = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

        $result = $this->sessions->get_sessions(
            array(
                'status'    => $status_filter,
                'search'    => $search,
                'date_from' => $date_from,
                'date_to'   => $date_to,
                'limit'     => $per_page,
                'offset'    => ( $current_page - 1 ) * $per_page,
            )
        );

        $sessions    = $result['sessions'];
        $total_items = $result['total'];
        $total_pages = $result['pages'];

        $stats = $this->statistics->get_stats();
        
        // For template access.
        $geolocation = $this->geolocation;

        // Load template.
        include AUTOMIZE_CHAT_PATH . 'templates/admin-chats.php';
    }

    /**
     * Convert datetime to "time ago" format in Arabic.
     *
     * @param string $datetime DateTime string.
     * @return string
     */
    public static function time_ago( $datetime ) {
        $time = strtotime( $datetime );
        $now  = current_time( 'timestamp' );
        $diff = $now - $time;

        if ( $diff < 60 ) {
            return 'الآن';
        } elseif ( $diff < 3600 ) {
            $mins = floor( $diff / 60 );
            return 'منذ ' . $mins . ' دقيقة';
        } elseif ( $diff < 86400 ) {
            $hours = floor( $diff / 3600 );
            return 'منذ ' . $hours . ' ساعة';
        } elseif ( $diff < 604800 ) {
            $days = floor( $diff / 86400 );
            return 'منذ ' . $days . ' يوم';
        } else {
            return gmdate( 'd/m/Y', $time ) . ' ' . gmdate( 'g:i A', $time );
        }
    }

    /**
     * Format date only (DD/MM/YYYY).
     *
     * @param string $datetime DateTime string.
     * @return string
     */
    public static function format_date_only( $datetime ) {
        if ( empty( $datetime ) ) {
            return '';
        }
        $time = strtotime( $datetime );
        if ( false === $time ) {
            return '';
        }
        return gmdate( 'd/m/Y', $time );
    }

    /**
     * Format time only (h:mm AM/PM).
     *
     * @param string $datetime DateTime string.
     * @return string
     */
    public static function format_time_only( $datetime ) {
        if ( empty( $datetime ) ) {
            return '';
        }
        $time = strtotime( $datetime );
        if ( false === $time ) {
            return '';
        }
        return gmdate( 'g:i A', $time );
    }
}
