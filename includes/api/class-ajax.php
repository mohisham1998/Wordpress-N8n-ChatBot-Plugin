<?php
/**
 * AJAX Handler for AutoMize Chatbot
 *
 * Handles all AJAX requests.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_AJAX
 *
 * Handles AJAX endpoints.
 */
class AutoMize_Chatbot_AJAX {

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
     * Save message from frontend.
     */
    public function save_message() {
        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

        // Validate session_id format.
        if ( empty( $session_id ) || 0 !== strpos( $session_id, 'chat_' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid session ID' ) );
            return;
        }

        $sender        = isset( $_POST['sender'] ) ? sanitize_text_field( wp_unslash( $_POST['sender'] ) ) : 'user';
        $message       = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
        $quick_replies = isset( $_POST['quick_replies'] ) ? json_decode( stripslashes( $_POST['quick_replies'] ), true ) : null;
        $payload       = isset( $_POST['payload'] ) ? sanitize_text_field( wp_unslash( $_POST['payload'] ) ) : null;

        if ( empty( $message ) ) {
            wp_send_json_error( array( 'message' => 'Message is empty' ) );
            return;
        }

        // Validate sender.
        if ( ! in_array( $sender, array( 'user', 'bot' ), true ) ) {
            $sender = 'user';
        }

        $message_id = $this->messages->save( $session_id, $sender, $message, $quick_replies, $payload );

        if ( $message_id ) {
            wp_send_json_success(
                array(
                    'message_id' => $message_id,
                    'session_id' => $session_id,
                )
            );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to save message' ) );
        }
    }

    /**
     * Start a new session.
     */
    public function start_session() {
        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

        if ( empty( $session_id ) || 0 !== strpos( $session_id, 'chat_' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid session ID' ) );
            return;
        }

        $result = $this->sessions->start_session( $session_id );

        wp_send_json_success( array( 'session_id' => $result ) );
    }

    /**
     * Update session location.
     */
    public function update_location() {
        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

        if ( empty( $session_id ) || 0 !== strpos( $session_id, 'chat_' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid session ID' ) );
            return;
        }

        $location_data = array();

        // Get location from GPS coordinates.
        if ( isset( $_POST['latitude'] ) && isset( $_POST['longitude'] ) ) {
            $lat = floatval( $_POST['latitude'] );
            $lon = floatval( $_POST['longitude'] );

            $location_data['latitude']  = $lat;
            $location_data['longitude'] = $lon;
            $location_data['source']    = 'gps';

            // Reverse geocode.
            $geo = AutoMize_Chatbot_Geolocation::reverse_geocode( $lat, $lon );
            if ( $geo ) {
                $location_data = array_merge( $location_data, $geo );
            }
        }

        // Or accept direct location data.
        $direct_fields = array( 'country', 'country_code', 'city', 'region' );
        foreach ( $direct_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $location_data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
            }
        }

        if ( ! empty( $location_data ) ) {
            $result = $this->sessions->update_location( $session_id, $location_data );

            if ( false !== $result ) {
                wp_send_json_success(
                    array(
                        'message'  => 'Location updated',
                        'location' => $location_data,
                    )
                );
            } else {
                wp_send_json_error( array( 'message' => 'Failed to update location' ) );
            }
        } else {
            wp_send_json_error( array( 'message' => 'No location data provided' ) );
        }
    }

    /**
     * Update session status.
     */
    public function update_session_status() {
        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
        $status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

        if ( empty( $session_id ) || 0 !== strpos( $session_id, 'chat_' ) ) {
            wp_send_json_error( array( 'message' => 'Invalid session ID' ) );
            return;
        }

        $valid_statuses = array( 'active', 'completed', 'lead', 'abandoned' );
        if ( ! in_array( $status, $valid_statuses, true ) ) {
            wp_send_json_error( array( 'message' => 'Invalid status' ) );
            return;
        }

        $result = $this->sessions->update_status( $session_id, $status );

        if ( false !== $result ) {
            wp_send_json_success(
                array(
                    'message' => 'Status updated',
                    'status'  => $status,
                )
            );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update status' ) );
        }
    }

    /**
     * Get chats list for admin.
     */
    public function get_chats_list() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
            return;
        }

        check_ajax_referer( 'automize_chat_nonce', 'nonce' );

        $page     = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $per_page = isset( $_POST['per_page'] ) ? intval( $_POST['per_page'] ) : 20;
        $status   = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
        $search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

        $result   = $this->sessions->get_sessions(
            array(
                'limit'  => $per_page,
                'offset' => ( $page - 1 ) * $per_page,
                'status' => $status,
                'search' => $search,
            )
        );

        $sessions = $result['sessions'];
        $total    = $result['total'];

        // Format data for frontend.
        $formatted_sessions = array();
        foreach ( $sessions as $session ) {
            $last_message = $this->messages->get_last( $session->session_id );
            $country_flag = AutoMize_Chatbot_Session_Repository::get_country_flag( isset( $session->visitor_country_code ) ? $session->visitor_country_code : '' );

            $formatted_sessions[] = array(
                'session_id'           => $session->session_id,
                'session_id_short'     => substr( $session->session_id, 0, 12 ) . '...',
                'visitor_city'         => isset( $session->visitor_city ) ? $session->visitor_city : '',
                'visitor_country'      => isset( $session->visitor_country ) ? $session->visitor_country : '',
                'visitor_country_code' => isset( $session->visitor_country_code ) ? $session->visitor_country_code : '',
                'country_flag'         => $country_flag,
                'messages_count'       => $session->messages_count,
                'last_message'         => $last_message ? mb_substr( $last_message->message, 0, 50 ) . ( mb_strlen( $last_message->message ) > 50 ? '...' : '' ) : '',
                'last_message_full'    => $last_message ? $last_message->message : '',
                'status'               => $session->status,
                'status_label'         => AutoMize_Chatbot_Session_Repository::get_status_label( $session->status ),
                'started_at'           => $session->started_at,
                'last_message_at'      => $session->last_message_at,
                'updated_at'           => isset( $session->updated_at ) ? $session->updated_at : $session->started_at,
            );
        }

        wp_send_json_success(
            array(
                'sessions'    => $formatted_sessions,
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $per_page,
                'total_pages' => ceil( $total / $per_page ),
            )
        );
    }

    /**
     * Get chat updates for real-time polling.
     */
    public function get_chat_updates() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
            return;
        }

        check_ajax_referer( 'automize_chat_nonce', 'nonce' );

        $last_check         = isset( $_POST['last_check'] ) ? sanitize_text_field( wp_unslash( $_POST['last_check'] ) ) : '';
        $current_session_id = isset( $_POST['current_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['current_session_id'] ) ) : '';

        // Get updated sessions.
        $updated_sessions = $this->sessions->get_updated_sessions( $last_check );

        // Get new messages if modal is open.
        $new_messages = array();
        if ( ! empty( $current_session_id ) ) {
            $new_messages = $this->messages->get_since( $current_session_id, $last_check );
        }

        // Format sessions.
        $formatted_sessions = array();
        foreach ( $updated_sessions as $session ) {
            $last_message = $this->messages->get_last( $session->session_id );
            $country_flag = AutoMize_Chatbot_Session_Repository::get_country_flag( isset( $session->visitor_country_code ) ? $session->visitor_country_code : '' );

            $formatted_sessions[] = array(
                'session_id'           => $session->session_id,
                'session_id_short'     => substr( $session->session_id, 0, 12 ) . '...',
                'visitor_city'         => isset( $session->visitor_city ) ? $session->visitor_city : '',
                'visitor_country'      => isset( $session->visitor_country ) ? $session->visitor_country : '',
                'visitor_country_code' => isset( $session->visitor_country_code ) ? $session->visitor_country_code : '',
                'country_flag'         => $country_flag,
                'messages_count'       => $session->messages_count,
                'last_message'         => $last_message ? mb_substr( $last_message->message, 0, 50 ) . ( mb_strlen( $last_message->message ) > 50 ? '...' : '' ) : '',
                'last_message_full'    => $last_message ? $last_message->message : '',
                'status'               => $session->status,
                'status_label'         => AutoMize_Chatbot_Session_Repository::get_status_label( $session->status ),
                'started_at'           => $session->started_at,
                'last_message_at'      => $session->last_message_at,
                'updated_at'           => isset( $session->updated_at ) ? $session->updated_at : $session->started_at,
            );
        }

        wp_send_json_success(
            array(
                'updated_sessions' => $formatted_sessions,
                'new_messages'     => $new_messages,
                'server_time'      => current_time( 'mysql' ),
            )
        );
    }

    /**
     * Get single chat with messages.
     */
    public function get_chat() {
        check_ajax_referer( 'automize_chat_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'غير مصرح' ) );
            return;
        }

        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';

        if ( empty( $session_id ) ) {
            wp_send_json_error( array( 'message' => 'معرف الجلسة مطلوب' ) );
            return;
        }

        $session  = $this->sessions->get_session( $session_id );
        $messages = $this->messages->get_by_session( $session_id );

        if ( ! $session ) {
            wp_send_json_error( array( 'message' => 'المحادثة غير موجودة' ) );
            return;
        }

        // Extract contact info if not already saved.
        if ( empty( $session->visitor_email ) || empty( $session->visitor_phone ) ) {
            $extracted = AutoMize_Chatbot_Message_Repository::extract_contact_info( $messages );
            if ( ! empty( $extracted['email'] ) || ! empty( $extracted['phone'] ) ) {
                $this->sessions->update_visitor_info(
                    $session_id,
                    null,
                    empty( $session->visitor_email ) ? $extracted['email'] : null,
                    empty( $session->visitor_phone ) ? $extracted['phone'] : null
                );
                if ( empty( $session->visitor_email ) && ! empty( $extracted['email'] ) ) {
                    $session->visitor_email = $extracted['email'];
                }
                if ( empty( $session->visitor_phone ) && ! empty( $extracted['phone'] ) ) {
                    $session->visitor_phone = $extracted['phone'];
                }
            }
        }

        wp_send_json_success(
            array(
                'session'  => $session,
                'messages' => $messages,
            )
        );
    }

    /**
     * Delete chats.
     */
    public function delete_chats() {
        check_ajax_referer( 'automize_chat_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'غير مصرح' ) );
            return;
        }

        $session_ids = isset( $_POST['session_ids'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['session_ids'] ) ) : array();

        if ( empty( $session_ids ) ) {
            wp_send_json_error( array( 'message' => 'لم يتم تحديد محادثات' ) );
            return;
        }

        $deleted = $this->sessions->delete_many( $session_ids );

        wp_send_json_success(
            array(
                'message' => sprintf( 'تم حذف %d محادثة', count( $session_ids ) ),
                'deleted' => $deleted,
            )
        );
    }

    /**
     * Export chats to CSV.
     */
    public function export_chats() {
        check_ajax_referer( 'automize_chat_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'غير مصرح' ) );
            return;
        }

        $csv_data = $this->statistics->export_sessions_csv();

        wp_send_json_success( array( 'data' => $csv_data ) );
    }

    /**
     * Update chat status.
     */
    public function update_chat_status() {
        check_ajax_referer( 'automize_chat_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'غير مصرح' ) );
            return;
        }

        $session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
        $status     = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

        $valid_statuses = array( 'active', 'completed', 'lead', 'abandoned' );

        if ( empty( $session_id ) || ! in_array( $status, $valid_statuses, true ) ) {
            wp_send_json_error( array( 'message' => 'بيانات غير صالحة' ) );
            return;
        }

        $this->sessions->update_status( $session_id, $status );

        wp_send_json_success(
            array(
                'message' => 'تم تحديث الحالة',
                'status'  => $status,
                'label'   => AutoMize_Chatbot_Session_Repository::get_status_label( $status ),
            )
        );
    }
}
