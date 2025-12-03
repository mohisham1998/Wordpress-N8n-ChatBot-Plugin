<?php
/**
 * Message Repository for AutoMize Chatbot
 *
 * Handles all message-related database operations.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_Message_Repository
 *
 * CRUD operations for chat messages.
 */
class AutoMize_Chatbot_Message_Repository {

    /**
     * Get the messages table name.
     *
     * @return string
     */
    private function get_table() {
        return AutoMize_Chatbot_Database::get_messages_table();
    }

    /**
     * Session repository.
     *
     * @var AutoMize_Chatbot_Session_Repository|null
     */
    private $sessions = null;

    /**
     * Set session repository.
     *
     * @param AutoMize_Chatbot_Session_Repository $sessions Session repository.
     */
    public function set_session_repository( $sessions ) {
        $this->sessions = $sessions;
    }

    /**
     * Save a new message.
     *
     * @param string      $session_id    Session ID.
     * @param string      $sender        Sender type (user/bot).
     * @param string      $message       Message content.
     * @param array|null  $quick_replies Quick replies.
     * @param string|null $payload       Payload data.
     * @return int|false
     */
    public function save( $session_id, $sender, $message, $quick_replies = null, $payload = null ) {
        global $wpdb;

        // Ensure session exists if we have session repo.
        if ( $this->sessions ) {
            $this->sessions->start_session( $session_id );
        }

        // Save message.
        $result = $wpdb->insert(
            $this->get_table(),
            array(
                'session_id'    => $session_id,
                'sender'        => $sender,
                'message'       => $message,
                'quick_replies' => $quick_replies ? ( is_string( $quick_replies ) ? $quick_replies : wp_json_encode( $quick_replies, JSON_UNESCAPED_UNICODE ) ) : null,
                'payload'       => $payload,
                'created_at'    => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $result ) {
            // Update message count.
            if ( $this->sessions ) {
                $this->sessions->increment_message_count( $session_id );
            }
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get all messages for a session.
     *
     * @param string $session_id Session ID.
     * @return array
     */
    public function get_by_session( $session_id ) {
        global $wpdb;

        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->get_table()} WHERE session_id = %s ORDER BY created_at ASC",
                $session_id
            )
        );

        // Decode quick_replies.
        foreach ( $messages as &$msg ) {
            if ( $msg->quick_replies ) {
                $msg->quick_replies = json_decode( $msg->quick_replies, true );
            }
        }

        return $messages;
    }

    /**
     * Get last message for a session.
     *
     * @param string $session_id Session ID.
     * @return object|null
     */
    public function get_last( $session_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->get_table()} WHERE session_id = %s ORDER BY created_at DESC LIMIT 1",
                $session_id
            )
        );
    }

    /**
     * Get messages since a given timestamp.
     *
     * @param string $session_id Session ID.
     * @param string $since      Timestamp.
     * @return array
     */
    public function get_since( $session_id, $since ) {
        global $wpdb;

        if ( empty( $since ) ) {
            return array();
        }

        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->get_table()} 
                WHERE session_id = %s AND created_at > %s
                ORDER BY created_at ASC",
                $session_id,
                $since
            )
        );

        // Decode quick_replies.
        foreach ( $messages as &$msg ) {
            if ( $msg->quick_replies ) {
                $msg->quick_replies = json_decode( $msg->quick_replies, true );
            }
        }

        return $messages;
    }

    /**
     * Delete messages for a session.
     *
     * @param string $session_id Session ID.
     * @return int|false
     */
    public function delete_by_session( $session_id ) {
        global $wpdb;

        return $wpdb->delete(
            $this->get_table(),
            array( 'session_id' => $session_id ),
            array( '%s' )
        );
    }

    /**
     * Extract contact information from messages.
     *
     * @param array $messages Array of message objects.
     * @return array
     */
    public static function extract_contact_info( $messages ) {
        $email = null;
        $phone = null;

        $email_regex = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
        $phone_regex = '/(?:\+?[0-9]{1,4}[\s-]?)?(?:\(?[0-9]{2,4}\)?[\s-]?)?[0-9]{6,10}/';

        foreach ( $messages as $msg ) {
            $text = isset( $msg->message ) ? $msg->message : '';

            if ( 'user' === $msg->sender ) {
                // Extract email.
                if ( ! $email && preg_match( $email_regex, $text, $matches ) ) {
                    $email = $matches[0];
                }

                // Extract phone.
                if ( ! $phone && preg_match_all( $phone_regex, $text, $matches ) ) {
                    foreach ( $matches[0] as $potential_phone ) {
                        $digits = preg_replace( '/\D/', '', $potential_phone );
                        if ( strlen( $digits ) >= 8 && strlen( $digits ) <= 15 ) {
                            $phone = $potential_phone;
                            break;
                        }
                    }
                }
            }
        }

        return array(
            'name'  => null,
            'email' => $email,
            'phone' => $phone,
        );
    }
}
