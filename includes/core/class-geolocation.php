<?php
/**
 * Geolocation Handler for AutoMize Chatbot
 *
 * Handles IP geolocation and reverse geocoding.
 *
 * @package AutoMize_Chatbot
 * @since 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AutoMize_Chatbot_Geolocation
 *
 * Provides geolocation services.
 */
class AutoMize_Chatbot_Geolocation {

    /**
     * Get geolocation from IP address.
     *
     * @param string $ip IP address.
     * @return array|null
     */
    public static function get_location_from_ip( $ip ) {
        // Skip for local/private IPs.
        if ( 'unknown' === $ip || '127.0.0.1' === $ip || 0 === strpos( $ip, '192.168.' ) || 0 === strpos( $ip, '10.' ) ) {
            return null;
        }

        // Use ip-api.com (free, no API key required, 45 requests/minute limit).
        $response = wp_remote_get(
            "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,regionName,city,lat,lon",
            array( 'timeout' => 5 )
        );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $data && isset( $data['status'] ) && 'success' === $data['status'] ) {
            return array(
                'country'      => isset( $data['country'] ) ? $data['country'] : null,
                'country_code' => isset( $data['countryCode'] ) ? $data['countryCode'] : null,
                'city'         => isset( $data['city'] ) ? $data['city'] : null,
                'region'       => isset( $data['regionName'] ) ? $data['regionName'] : null,
                'latitude'     => isset( $data['lat'] ) ? $data['lat'] : null,
                'longitude'    => isset( $data['lon'] ) ? $data['lon'] : null,
                'source'       => 'ip',
            );
        }

        return null;
    }

    /**
     * Reverse geocode coordinates to get address.
     *
     * @param float $lat Latitude.
     * @param float $lon Longitude.
     * @return array|null
     */
    public static function reverse_geocode( $lat, $lon ) {
        // Use Nominatim (OpenStreetMap) - free, no API key.
        $url = sprintf(
            'https://nominatim.openstreetmap.org/reverse?format=json&lat=%f&lon=%f&zoom=10&accept-language=en',
            $lat,
            $lon
        );

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 10,
                'headers' => array(
                    'User-Agent' => 'AutoMize-Chatbot/7.0 (https://automize.sa)',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $data && isset( $data['address'] ) ) {
            $address = $data['address'];

            // Nominatim returns different fields based on location.
            $city         = isset( $address['city'] ) ? $address['city'] :
                           ( isset( $address['town'] ) ? $address['town'] :
                           ( isset( $address['village'] ) ? $address['village'] :
                           ( isset( $address['municipality'] ) ? $address['municipality'] :
                           ( isset( $address['county'] ) ? $address['county'] : null ) ) ) );
            $region       = isset( $address['state'] ) ? $address['state'] :
                           ( isset( $address['province'] ) ? $address['province'] :
                           ( isset( $address['region'] ) ? $address['region'] : null ) );
            $country      = isset( $address['country'] ) ? $address['country'] : null;
            $country_code = isset( $address['country_code'] ) ? strtoupper( $address['country_code'] ) : null;

            return array(
                'city'         => $city,
                'region'       => $region,
                'country'      => $country,
                'country_code' => $country_code,
            );
        }

        return null;
    }
}
