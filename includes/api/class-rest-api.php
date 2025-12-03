<?php
/**
 * REST API Handler for AutoMize Chatbot
 *
 * Handles all REST API endpoints.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_REST_API
 *
 * Registers and handles REST API endpoints.
 */
class AutoMize_Chatbot_REST_API {

    /**
     * REST namespace.
     *
     * @var string
     */
    const NAMESPACE = 'automize-chat/v1';

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
     * Register REST routes.
     */
    public function register_routes() {
        register_rest_route(
            self::NAMESPACE,
            '/message',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'save_message' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            self::NAMESPACE,
            '/webhook',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_webhook' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Save message from REST API.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function save_message( $request ) {
        $params = $request->get_json_params();

        $session_id    = isset( $params['session_id'] ) ? sanitize_text_field( $params['session_id'] ) : '';
        $sender        = isset( $params['sender'] ) ? sanitize_text_field( $params['sender'] ) : '';
        $message       = isset( $params['message'] ) ? sanitize_textarea_field( $params['message'] ) : '';
        $quick_replies = isset( $params['quick_replies'] ) ? $params['quick_replies'] : null;
        $payload       = isset( $params['payload'] ) ? sanitize_text_field( $params['payload'] ) : null;

        if ( empty( $session_id ) || empty( $message ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => 'Missing required fields',
                ),
                400
            );
        }

        $message_id = $this->messages->save( $session_id, $sender, $message, $quick_replies, $payload );

        return new WP_REST_Response(
            array(
                'success'    => true,
                'message_id' => $message_id,
            ),
            200
        );
    }

    /**
     * Handle webhook from n8n.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function handle_webhook( $request ) {
        $params = $request->get_json_params();

        // Save bot message.
        if ( isset( $params['session_id'] ) && isset( $params['response'] ) ) {
            $session_id = sanitize_text_field( $params['session_id'] );
            $response   = $params['response'];

            // Extract message from response.
            $message       = '';
            $quick_replies = null;

            if ( is_array( $response ) ) {
                $message = isset( $response['text'] ) ? $response['text'] : '';
                if ( isset( $response['question'] ) && ! empty( $response['question'] ) ) {
                    $message .= "\n" . $response['question'];
                }
                $quick_replies = isset( $response['quick_replies'] ) ? $response['quick_replies'] : null;
            } else {
                $message = $response;
            }

            if ( ! empty( $message ) ) {
                $this->messages->save( $session_id, 'bot', $message, $quick_replies );
            }

            // Update visitor info if provided.
            if ( isset( $params['visitor_info'] ) ) {
                $info = $params['visitor_info'];
                $this->sessions->update_visitor_info(
                    $session_id,
                    isset( $info['name'] ) ? $info['name'] : null,
                    isset( $info['email'] ) ? $info['email'] : null,
                    isset( $info['phone'] ) ? $info['phone'] : null,
                    true // Set as lead.
                );
            }
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
}
