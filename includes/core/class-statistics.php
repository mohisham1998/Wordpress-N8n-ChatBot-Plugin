<?php
/**
 * Statistics Handler for AutoMize Chatbot
 *
 * Handles all statistics and analytics operations.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_Statistics
 *
 * Generates statistics and analytics data.
 */
class AutoMize_Chatbot_Statistics {

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
     * Constructor.
     *
     * @param AutoMize_Chatbot_Session_Repository $sessions Session repository.
     * @param AutoMize_Chatbot_Message_Repository $messages Message repository.
     */
    public function __construct( $sessions, $messages ) {
        $this->sessions = $sessions;
        $this->messages = $messages;
    }

    /**
     * Get all statistics.
     *
     * @return array
     */
    public function get_stats() {
        global $wpdb;

        $sessions_table = AutoMize_Chatbot_Database::get_sessions_table();
        $messages_table = AutoMize_Chatbot_Database::get_messages_table();

        $stats = array(
            'total_sessions'     => 0,
            'active_sessions'    => 0,
            'completed_sessions' => 0,
            'leads'              => 0,
            'abandoned_sessions' => 0,
            'total_messages'     => 0,
            'today_sessions'     => 0,
            'this_week_sessions' => 0,
        );

        // Total sessions.
        $stats['total_sessions'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $sessions_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // By status.
        $status_counts = $wpdb->get_results( "SELECT status, COUNT(*) as count FROM $sessions_table GROUP BY status" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        foreach ( $status_counts as $row ) {
            switch ( $row->status ) {
                case 'active':
                    $stats['active_sessions'] = (int) $row->count;
                    break;
                case 'completed':
                    $stats['completed_sessions'] = (int) $row->count;
                    break;
                case 'lead':
                    $stats['leads'] = (int) $row->count;
                    break;
                case 'abandoned':
                    $stats['abandoned_sessions'] = (int) $row->count;
                    break;
            }
        }

        // Total messages.
        $stats['total_messages'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $messages_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // Today's sessions.
        $stats['today_sessions'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $sessions_table WHERE DATE(started_at) = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                current_time( 'Y-m-d' )
            )
        );

        // This week's sessions.
        $stats['this_week_sessions'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $sessions_table WHERE started_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                gmdate( 'Y-m-d', strtotime( '-7 days' ) )
            )
        );

        return $stats;
    }

    /**
     * Export sessions to CSV data.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function export_sessions_csv( $args = array() ) {
        $result = $this->sessions->get_sessions(
            array_merge(
                $args,
                array(
                    'limit'  => 10000,
                    'offset' => 0,
                )
            )
        );

        $csv_data   = array();
        $csv_data[] = array(
            'معرف الجلسة',
            'الاسم',
            'البريد الإلكتروني',
            'الهاتف',
            'الحالة',
            'عدد الرسائل',
            'تاريخ البدء',
            'آخر رسالة',
            'IP',
        );

        foreach ( $result['sessions'] as $session ) {
            $csv_data[] = array(
                $session->session_id,
                $session->visitor_name ? $session->visitor_name : '-',
                $session->visitor_email ? $session->visitor_email : '-',
                $session->visitor_phone ? $session->visitor_phone : '-',
                AutoMize_Chatbot_Session_Repository::get_status_label( $session->status ),
                $session->messages_count,
                $session->started_at,
                $session->last_message_at,
                $session->visitor_ip,
            );
        }

        return $csv_data;
    }

    /**
     * Cleanup old sessions.
     *
     * @param int $days Days to keep.
     * @return int Number of deleted sessions.
     */
    public function cleanup_old_sessions( $days = 90 ) {
        global $wpdb;

        $sessions_table = AutoMize_Chatbot_Database::get_sessions_table();
        $cutoff_date    = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $old_sessions = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT session_id FROM $sessions_table WHERE started_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $cutoff_date
            )
        );

        if ( ! empty( $old_sessions ) ) {
            return $this->sessions->delete_many( $old_sessions );
        }

        return 0;
    }
}
