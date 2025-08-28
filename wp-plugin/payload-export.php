<?php
/**
 * Plugin Name: Payload Export
 * Description: Exposes a read-only REST API for migrating content to Payload CMS.
 * Version: 0.1.0
 * Author: AI Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

class Payload_Export_Plugin {
    const REST_NAMESPACE = 'payload-export/v1';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        register_rest_route(self::REST_NAMESPACE, '/site', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_site'),
            'permission_callback' => array($this, 'authorize_request'),
        ));
    }

    public function authorize_request($request) {
        // TODO: Implement JWT validation
        return current_user_can('read');
    }

    public function get_site($request) {
        return array(
            'url' => get_home_url(),
            'timezone' => get_option('timezone_string'),
        );
    }
}

new Payload_Export_Plugin();
