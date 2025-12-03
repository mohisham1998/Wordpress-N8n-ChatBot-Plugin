<?php
/**
 * Database Handler for AutoMize Chatbot
 *
 * Handles all database table operations - create, upgrade, delete.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_Database
 *
 * Manages database schema and operations.
 */
class AutoMize_Chatbot_Database {

    /**
     * Database version.
     *
     * @var string
     */
    const DB_VERSION = '7.0';

    /**
     * Sessions table name (without prefix).
     *
     * @var string
     */
    const SESSIONS_TABLE = 'automize_chat_sessions';

    /**
     * Messages table name (without prefix).
     *
     * @var string
     */
    const MESSAGES_TABLE = 'automize_chat_messages';

    /**
     * Get the full sessions table name with prefix.
     *
     * @return string
     */
    public static function get_sessions_table() {
        global $wpdb;
        return $wpdb->prefix . self::SESSIONS_TABLE;
    }

    /**
     * Get the full messages table name with prefix.
     *
     * @return string
     */
    public static function get_messages_table() {
        global $wpdb;
        return $wpdb->prefix . self::MESSAGES_TABLE;
    }

    /**
     * Create database tables.
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sessions_table = self::get_sessions_table();
        $messages_table = self::get_messages_table();

        // Sessions table with location fields.
        $sql_sessions = "CREATE TABLE IF NOT EXISTS $sessions_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(100) NOT NULL,
            visitor_name VARCHAR(255) DEFAULT NULL,
            visitor_email VARCHAR(255) DEFAULT NULL,
            visitor_phone VARCHAR(50) DEFAULT NULL,
            visitor_ip VARCHAR(45) DEFAULT NULL,
            visitor_country VARCHAR(100) DEFAULT NULL,
            visitor_country_code VARCHAR(5) DEFAULT NULL,
            visitor_city VARCHAR(100) DEFAULT NULL,
            visitor_region VARCHAR(100) DEFAULT NULL,
            visitor_latitude DECIMAL(10, 8) DEFAULT NULL,
            visitor_longitude DECIMAL(11, 8) DEFAULT NULL,
            location_source ENUM('ip', 'gps', 'manual') DEFAULT 'ip',
            user_agent TEXT DEFAULT NULL,
            page_url TEXT DEFAULT NULL,
            status ENUM('active', 'completed', 'lead', 'abandoned') DEFAULT 'active',
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            ended_at DATETIME DEFAULT NULL,
            last_message_at DATETIME DEFAULT NULL,
            messages_count INT DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY status (status),
            KEY started_at (started_at),
            KEY last_message_at (last_message_at),
            KEY visitor_country_code (visitor_country_code)
        ) $charset_collate;";

        // Messages table.
        $sql_messages = "CREATE TABLE IF NOT EXISTS $messages_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(100) NOT NULL,
            sender ENUM('user', 'bot') NOT NULL,
            message LONGTEXT NOT NULL,
            quick_replies LONGTEXT DEFAULT NULL,
            payload VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY sender (sender),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_sessions );
        dbDelta( $sql_messages );

        update_option( 'automize_chat_db_version', self::DB_VERSION );
    }

    /**
     * Add location columns to existing table (for upgrades).
     */
    public static function add_location_columns() {
        global $wpdb;
        $sessions_table = self::get_sessions_table();

        // Check if columns exist.
        $columns = $wpdb->get_results( "SHOW COLUMNS FROM $sessions_table" );
        $existing_columns = array_map( function( $col ) { return $col->Field; }, $columns );

        $columns_to_add = array(
            'visitor_country'      => "ALTER TABLE $sessions_table ADD COLUMN visitor_country VARCHAR(100) DEFAULT NULL AFTER visitor_ip",
            'visitor_country_code' => "ALTER TABLE $sessions_table ADD COLUMN visitor_country_code VARCHAR(5) DEFAULT NULL AFTER visitor_country",
            'visitor_city'         => "ALTER TABLE $sessions_table ADD COLUMN visitor_city VARCHAR(100) DEFAULT NULL AFTER visitor_country_code",
            'visitor_region'       => "ALTER TABLE $sessions_table ADD COLUMN visitor_region VARCHAR(100) DEFAULT NULL AFTER visitor_city",
            'visitor_latitude'     => "ALTER TABLE $sessions_table ADD COLUMN visitor_latitude DECIMAL(10, 8) DEFAULT NULL AFTER visitor_region",
            'visitor_longitude'    => "ALTER TABLE $sessions_table ADD COLUMN visitor_longitude DECIMAL(11, 8) DEFAULT NULL AFTER visitor_latitude",
            'location_source'      => "ALTER TABLE $sessions_table ADD COLUMN location_source ENUM('ip', 'gps', 'manual') DEFAULT 'ip' AFTER visitor_longitude",
        );

        foreach ( $columns_to_add as $column => $sql ) {
            if ( ! in_array( $column, $existing_columns, true ) ) {
                $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            }
        }

        // Add index for country_code if not exists.
        $indexes = $wpdb->get_results( "SHOW INDEX FROM $sessions_table WHERE Key_name = 'visitor_country_code'" );
        if ( empty( $indexes ) ) {
            $wpdb->query( "ALTER TABLE $sessions_table ADD INDEX visitor_country_code (visitor_country_code)" );
        }
    }

    /**
     * Drop tables on plugin uninstall.
     */
    public static function drop_tables() {
        global $wpdb;
        $sessions_table = self::get_sessions_table();
        $messages_table = self::get_messages_table();

        $wpdb->query( "DROP TABLE IF EXISTS $messages_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query( "DROP TABLE IF EXISTS $sessions_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        delete_option( 'automize_chat_db_version' );
    }

    /**
     * Check if database needs upgrade.
     *
     * @return bool
     */
    public static function needs_upgrade() {
        $current_version = get_option( 'automize_chat_db_version', '1.0' );
        return version_compare( $current_version, self::DB_VERSION, '<' );
    }

    /**
     * Run database upgrade.
     *
     * @param string $from_version Current version to upgrade from.
     */
    public function upgrade_tables( $from_version = '1.0' ) {
        // Always run create_tables to update schema.
        self::create_tables();
        
        // Run version-specific upgrades.
        if ( version_compare( $from_version, '5.0', '<' ) ) {
            self::add_location_columns();
        }
    }

    /**
     * Run database upgrade (static version).
     */
    public static function maybe_upgrade() {
        if ( self::needs_upgrade() ) {
            self::create_tables();
            self::add_location_columns();
        }
    }
}
