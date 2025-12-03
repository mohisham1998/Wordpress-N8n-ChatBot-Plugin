<?php
/**
 * Session Repository for AutoMize Chatbot
 *
 * Handles all session-related database operations.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_Session_Repository
 *
 * CRUD operations for chat sessions.
 */
class AutoMize_Chatbot_Session_Repository {

    /**
     * Get the sessions table name.
     *
     * @return string
     */
    private function get_table() {
        return AutoMize_Chatbot_Database::get_sessions_table();
    }

    /**
     * Start new session or get existing one.
     *
     * @param string $session_id  Session ID.
     * @param bool   $with_geolocation Whether to capture geolocation.
     * @return string Session ID.
     */
    public function start_session( $session_id, $with_geolocation = true ) {
        global $wpdb;

        // Check if session exists.
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->get_table()} WHERE session_id = %s",
                $session_id
            )
        );

        if ( ! $exists ) {
            $visitor_ip  = $this->get_visitor_ip();
            $insert_data = array(
                'session_id'      => $session_id,
                'visitor_ip'      => $visitor_ip,
                'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'], 0, 500 ) ) : '',
                'page_url'        => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : '',
                'started_at'      => current_time( 'mysql' ),
                'last_message_at' => current_time( 'mysql' ),
            );

            $insert_format = array( '%s', '%s', '%s', '%s', '%s', '%s' );

            // Get geolocation from IP.
            if ( $with_geolocation ) {
                $geo = AutoMize_Chatbot_Geolocation::get_location_from_ip( $visitor_ip );
                if ( $geo ) {
                    $insert_data['visitor_country']      = $geo['country'];
                    $insert_data['visitor_country_code'] = $geo['country_code'];
                    $insert_data['visitor_city']         = $geo['city'];
                    $insert_data['visitor_region']       = $geo['region'];
                    $insert_data['visitor_latitude']     = $geo['latitude'];
                    $insert_data['visitor_longitude']    = $geo['longitude'];
                    $insert_data['location_source']      = 'ip';
                    $insert_format = array_merge( $insert_format, array( '%s', '%s', '%s', '%s', '%f', '%f', '%s' ) );
                }
            }

            $wpdb->insert( $this->get_table(), $insert_data, $insert_format );
        }

        return $session_id;
    }

    /**
     * Get a single session.
     *
     * @param string $session_id Session ID.
     * @return object|null
     */
    public function get_session( $session_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->get_table()} WHERE session_id = %s",
                $session_id
            )
        );
    }

    /**
     * Get sessions with filtering and pagination.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public function get_sessions( $args = array() ) {
        global $wpdb;

        $defaults = array(
            'status'    => '',
            'search'    => '',
            'date_from' => '',
            'date_to'   => '',
            'limit'     => 20,
            'offset'    => 0,
            'orderby'   => 'last_message_at',
            'order'     => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        // Build WHERE clause.
        $where_clauses = array( '1=1' );
        $where_values  = array();

        if ( ! empty( $args['status'] ) ) {
            $where_clauses[] = 'status = %s';
            $where_values[]  = $args['status'];
        }

        if ( ! empty( $args['search'] ) ) {
            $search_term     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where_clauses[] = '(session_id LIKE %s OR visitor_name LIKE %s OR visitor_email LIKE %s OR visitor_phone LIKE %s)';
            $where_values[]  = $search_term;
            $where_values[]  = $search_term;
            $where_values[]  = $search_term;
            $where_values[]  = $search_term;
        }

        if ( ! empty( $args['date_from'] ) ) {
            $where_clauses[] = 'DATE(started_at) >= %s';
            $where_values[]  = $args['date_from'];
        }

        if ( ! empty( $args['date_to'] ) ) {
            $where_clauses[] = 'DATE(started_at) <= %s';
            $where_values[]  = $args['date_to'];
        }

        $where_sql = implode( ' AND ', $where_clauses );

        // Validate orderby.
        $allowed_orderby = array( 'id', 'started_at', 'last_message_at', 'messages_count', 'status' );
        $orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'last_message_at';
        $order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        // Build query.
        $sql           = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->get_table()} WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $where_values[] = intval( $args['limit'] );
        $where_values[] = intval( $args['offset'] );

        $results = $wpdb->get_results( $wpdb->prepare( $sql, $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total   = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

        return array(
            'sessions' => $results,
            'total'    => intval( $total ),
            'pages'    => ceil( $total / $args['limit'] ),
        );
    }

    /**
     * Get sessions updated since a given timestamp.
     *
     * @param string $since Timestamp.
     * @return array
     */
    public function get_updated_sessions( $since ) {
        global $wpdb;

        if ( empty( $since ) ) {
            $since = gmdate( 'Y-m-d H:i:s', strtotime( '-1 minute' ) );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->get_table()} 
                WHERE last_message_at > %s OR started_at > %s
                ORDER BY COALESCE(last_message_at, started_at) DESC
                LIMIT 50",
                $since,
                $since
            )
        );
    }

    /**
     * Update session status.
     *
     * @param string $session_id Session ID.
     * @param string $status     New status.
     * @return int|false
     */
    public function update_status( $session_id, $status ) {
        global $wpdb;

        $data   = array( 'status' => $status );
        $format = array( '%s' );

        if ( in_array( $status, array( 'completed', 'abandoned' ), true ) ) {
            $data['ended_at'] = current_time( 'mysql' );
            $format[]         = '%s';
        }

        return $wpdb->update(
            $this->get_table(),
            $data,
            array( 'session_id' => $session_id ),
            $format,
            array( '%s' )
        );
    }

    /**
     * Update session location data.
     *
     * @param string $session_id    Session ID.
     * @param array  $location_data Location data.
     * @return int|false
     */
    public function update_location( $session_id, $location_data ) {
        global $wpdb;

        $data   = array();
        $format = array();

        $field_map = array(
            'country'      => array( 'visitor_country', '%s' ),
            'country_code' => array( 'visitor_country_code', '%s' ),
            'city'         => array( 'visitor_city', '%s' ),
            'region'       => array( 'visitor_region', '%s' ),
            'latitude'     => array( 'visitor_latitude', '%f' ),
            'longitude'    => array( 'visitor_longitude', '%f' ),
            'source'       => array( 'location_source', '%s' ),
        );

        foreach ( $field_map as $key => $config ) {
            if ( isset( $location_data[ $key ] ) ) {
                $data[ $config[0] ] = 'country_code' === $key
                    ? strtoupper( sanitize_text_field( $location_data[ $key ] ) )
                    : ( 'source' === $key
                        ? ( in_array( $location_data[ $key ], array( 'ip', 'gps', 'manual' ), true ) ? $location_data[ $key ] : 'ip' )
                        : ( is_numeric( $location_data[ $key ] ) ? floatval( $location_data[ $key ] ) : sanitize_text_field( $location_data[ $key ] ) )
                    );
                $format[] = $config[1];
            }
        }

        if ( ! empty( $data ) ) {
            return $wpdb->update(
                $this->get_table(),
                $data,
                array( 'session_id' => $session_id ),
                $format,
                array( '%s' )
            );
        }

        return false;
    }

    /**
     * Update visitor info.
     *
     * @param string      $session_id      Session ID.
     * @param string|null $name            Visitor name.
     * @param string|null $email           Visitor email.
     * @param string|null $phone           Visitor phone.
     * @param bool        $set_lead_status Whether to set lead status.
     * @return int|false
     */
    public function update_visitor_info( $session_id, $name = null, $email = null, $phone = null, $set_lead_status = false ) {
        global $wpdb;

        $data   = array();
        $format = array();

        if ( $name ) {
            $data['visitor_name'] = sanitize_text_field( $name );
            $format[]             = '%s';
        }
        if ( $email ) {
            $data['visitor_email'] = sanitize_email( $email );
            $format[]              = '%s';
        }
        if ( $phone ) {
            $data['visitor_phone'] = sanitize_text_field( $phone );
            $format[]              = '%s';
        }

        if ( ! empty( $data ) ) {
            if ( $set_lead_status ) {
                $data['status'] = 'lead';
                $format[]       = '%s';
            }

            return $wpdb->update(
                $this->get_table(),
                $data,
                array( 'session_id' => $session_id ),
                $format,
                array( '%s' )
            );
        }

        return false;
    }

    /**
     * Increment message count for a session.
     *
     * @param string $session_id Session ID.
     */
    public function increment_message_count( $session_id ) {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->get_table()} 
                SET messages_count = messages_count + 1, 
                    last_message_at = %s 
                WHERE session_id = %s",
                current_time( 'mysql' ),
                $session_id
            )
        );
    }

    /**
     * Delete a session and its messages.
     *
     * @param string $session_id Session ID.
     * @return int|false
     */
    public function delete( $session_id ) {
        global $wpdb;

        // Delete messages first.
        $wpdb->delete(
            AutoMize_Chatbot_Database::get_messages_table(),
            array( 'session_id' => $session_id ),
            array( '%s' )
        );

        // Delete session.
        return $wpdb->delete(
            $this->get_table(),
            array( 'session_id' => $session_id ),
            array( '%s' )
        );
    }

    /**
     * Delete multiple sessions.
     *
     * @param array $session_ids Array of session IDs.
     * @return int|false
     */
    public function delete_many( $session_ids ) {
        global $wpdb;

        if ( empty( $session_ids ) || ! is_array( $session_ids ) ) {
            return false;
        }

        $placeholders  = implode( ',', array_fill( 0, count( $session_ids ), '%s' ) );
        $messages_table = AutoMize_Chatbot_Database::get_messages_table();

        // Delete messages.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $messages_table WHERE session_id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $session_ids
            )
        );

        // Delete sessions.
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->get_table()} WHERE session_id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $session_ids
            )
        );
    }

    /**
     * Get total sessions count.
     *
     * @param string $status Status filter.
     * @param string $search Search term.
     * @return int
     */
    public function get_total_count( $status = '', $search = '' ) {
        global $wpdb;

        $where_clauses = array( '1=1' );
        $where_values  = array();

        if ( ! empty( $status ) ) {
            $where_clauses[] = 'status = %s';
            $where_values[]  = $status;
        }

        if ( ! empty( $search ) ) {
            $search_term     = '%' . $wpdb->esc_like( $search ) . '%';
            $where_clauses[] = '(session_id LIKE %s OR visitor_name LIKE %s OR visitor_email LIKE %s)';
            $where_values[]  = $search_term;
            $where_values[]  = $search_term;
            $where_values[]  = $search_term;
        }

        $where_sql = implode( ' AND ', $where_clauses );

        if ( ! empty( $where_values ) ) {
            $sql = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->get_table()} WHERE $where_sql", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $where_values
            );
        } else {
            $sql = "SELECT COUNT(*) FROM {$this->get_table()} WHERE $where_sql";
        }

        return intval( $wpdb->get_var( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Get visitor IP address.
     *
     * @return string
     */
    private function get_visitor_ip() {
        $ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                return $ip;
            }
        }

        return 'unknown';
    }

    /**
     * Get status label in Arabic.
     *
     * @param string $status Status key.
     * @return string
     */
    public static function get_status_label( $status ) {
        $labels = array(
            'active'    => 'نشط',
            'completed' => 'مكتمل',
            'lead'      => 'عميل محتمل',
            'abandoned' => 'متروك',
        );

        return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
    }

    /**
     * Get country flag HTML.
     *
     * @param string $country_code Country code.
     * @return string
     */
    public static function get_country_flag( $country_code ) {
        if ( empty( $country_code ) || 2 !== strlen( $country_code ) ) {
            return '<span class="flag-icon">🌍</span>';
        }

        $country_code = strtolower( $country_code );
        $flag_url     = "https://flagcdn.com/24x18/{$country_code}.png";
        $flag_url_2x  = "https://flagcdn.com/48x36/{$country_code}.png";

        return sprintf(
            '<img src="%s" srcset="%s 2x" width="24" height="18" alt="%s" class="country-flag-img">',
            esc_url( $flag_url ),
            esc_url( $flag_url_2x ),
            esc_attr( strtoupper( $country_code ) )
        );
    }
}
