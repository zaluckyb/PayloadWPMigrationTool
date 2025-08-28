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
    const VERSION = '0.1.0';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes() {
        $namespace = self::REST_NAMESPACE;

        register_rest_route($namespace, '/site', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_site'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/post-types', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_post_types'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/taxonomies', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_taxonomies'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/terms', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_terms'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/users', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_users'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/content', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_content'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/media', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_media'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/menus', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_menus'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/comments', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_comments'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/options', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_options'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/redirects', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_redirects'),
            'permission_callback' => array($this, 'authorize_request'),
        ));

        register_rest_route($namespace, '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_health'),
            'permission_callback' => '__return_true',
        ));
    }

    public function authorize_request($request) {
        // TODO: Implement JWT validation
        return current_user_can('read');
    }

    private function filter_fields($items, $request) {
        $fields = $request->get_param('fields');
        if (empty($fields)) {
            return $items;
        }

        $fields = array_map('trim', explode(',', $fields));

        $filtered = array();
        foreach ($items as $item) {
            $filtered[] = array_intersect_key($item, array_flip($fields));
        }

        return $filtered;
    }

    private function parse_collection_params($request) {
        $args = array(
            'page' => max(1, (int)$request->get_param('page')),
            'per_page' => max(1, (int)$request->get_param('per_page')),
            'ids' => $request->get_param('ids'),
            'updated_after' => $request->get_param('updated_after'),
        );

        if (!empty($args['ids']) && !is_array($args['ids'])) {
            $args['ids'] = array_map('intval', explode(',', $args['ids']));
        }

        return $args;
    }

    public function get_site($request) {
        $data = array(
            'url' => get_home_url(),
            'permalink_structure' => get_option('permalink_structure'),
            'timezone' => get_option('timezone_string'),
        );
        $filtered = $this->filter_fields(array($data), $request);
        return $filtered ? $filtered[0] : array();
    }

    public function get_post_types($request) {
        $types = get_post_types(array(), 'objects');
        $items = array();
        foreach ($types as $type) {
            $items[] = array(
                'name' => $type->name,
                'label' => $type->label,
                'supports' => array_keys(get_all_post_type_supports($type->name)),
                'taxonomies' => $type->taxonomies,
            );
        }

        return $this->filter_fields($items, $request);
    }

    public function get_taxonomies($request) {
        $taxes = get_taxonomies(array(), 'objects');
        $items = array();
        foreach ($taxes as $tax) {
            $items[] = array(
                'name' => $tax->name,
                'label' => $tax->label,
                'hierarchical' => (bool)$tax->hierarchical,
                'object_type' => $tax->object_type,
            );
        }

        return $this->filter_fields($items, $request);
    }

    public function get_terms($request) {
        $params = $this->parse_collection_params($request);

        $args = array(
            'hide_empty' => false,
            'orderby' => 'id',
            'order' => 'ASC',
            'number' => $params['per_page'],
            'offset' => ($params['page'] - 1) * $params['per_page'],
        );

        if (!empty($params['ids'])) {
            $args['include'] = $params['ids'];
        }

        $terms = get_terms($args);
        $items = array();
        foreach ($terms as $term) {
            $items[] = array(
                'id' => (int)$term->term_id,
                'taxonomy' => $term->taxonomy,
                'slug' => $term->slug,
                'name' => $term->name,
                'parent' => (int)$term->parent,
            );
        }

        return $this->filter_fields($items, $request);
    }

    public function get_users($request) {
        $params = $this->parse_collection_params($request);

        $args = array(
            'orderby' => 'ID',
            'order' => 'ASC',
            'number' => $params['per_page'],
            'paged' => $params['page'],
        );

        if (!empty($params['ids'])) {
            $args['include'] = $params['ids'];
        }

        if (!empty($params['updated_after'])) {
            $args['date_query'][] = array(
                'after' => $params['updated_after'],
                'column' => 'user_registered',
            );
        }

        $users = get_users($args);
        $items = array();
        foreach ($users as $user) {
            $items[] = array(
                'id' => (int)$user->ID,
                'login' => $user->user_login,
                'name' => $user->display_name,
                'roles' => $user->roles,
            );
        }

        return $this->filter_fields($items, $request);
    }

    public function get_content($request) {
        $params = $this->parse_collection_params($request);

        $args = array(
            'post_type' => 'any',
            'post_status' => 'any',
            'orderby' => 'ID',
            'order' => 'ASC',
            'paged' => $params['page'],
            'posts_per_page' => $params['per_page'],
        );

        if (!empty($params['ids'])) {
            $args['post__in'] = $params['ids'];
        }

        if (!empty($params['updated_after'])) {
            $args['date_query'][] = array(
                'after' => $params['updated_after'],
                'column' => 'post_modified_gmt',
            );
        }

        $query = new WP_Query($args);

        $items = array();
        foreach ($query->posts as $post) {
            $items[] = array(
                'id' => (int)$post->ID,
                'type' => $post->post_type,
                'status' => $post->post_status,
                'slug' => $post->post_name,
                'title' => $post->post_title,
                'excerpt' => $post->post_excerpt,
                'content' => $post->post_content,
                'author' => (int)$post->post_author,
                'date' => $post->post_date_gmt,
                'modified' => $post->post_modified_gmt,
                'parent' => (int)$post->post_parent,
                'menu_order' => (int)$post->menu_order,
            );
        }

        return $this->filter_fields($items, $request);
    }

    public function get_media($request) {
        $params = $this->parse_collection_params($request);

        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'any',
            'orderby' => 'ID',
            'order' => 'ASC',
            'paged' => $params['page'],
            'posts_per_page' => $params['per_page'],
        );

        if (!empty($params['ids'])) {
            $args['post__in'] = $params['ids'];
        }

        if (!empty($params['updated_after'])) {
            $args['date_query'][] = array(
                'after' => $params['updated_after'],
                'column' => 'post_modified_gmt',
            );
        }

        $query = new WP_Query($args);
        $items = array();
        foreach ($query->posts as $post) {
            $meta = wp_get_attachment_metadata($post->ID);
            $items[] = array(
                'id' => (int)$post->ID,
                'filename' => basename(get_attached_file($post->ID)),
                'mime' => get_post_mime_type($post->ID),
                'bytes' => isset($meta['filesize']) ? (int)$meta['filesize'] : null,
                'width' => isset($meta['width']) ? (int)$meta['width'] : null,
                'height' => isset($meta['height']) ? (int)$meta['height'] : null,
                'alt' => get_post_meta($post->ID, '_wp_attachment_image_alt', true),
                'caption' => $post->post_excerpt,
                'url' => wp_get_attachment_url($post->ID),
            );
        }

        return $this->filter_fields($items, $request);
    }

    public function get_menus($request) {
        $menus = wp_get_nav_menus();
        $items = array();
        foreach ($menus as $menu) {
            $menu_items = wp_get_nav_menu_items($menu->term_id);
            $items[] = array(
                'id' => (int)$menu->term_id,
                'name' => $menu->name,
                'items' => $menu_items,
            );
        }

        return $this->filter_fields($items, $request);
    }

    public function get_comments($request) {
        $params = $this->parse_collection_params($request);

        $args = array(
            'orderby' => 'comment_ID',
            'order' => 'ASC',
            'number' => $params['per_page'],
            'offset' => ($params['page'] - 1) * $params['per_page'],
        );

        if (!empty($params['ids'])) {
            $args['comment__in'] = $params['ids'];
        }

        if (!empty($params['updated_after'])) {
            $args['date_query'][] = array(
                'after' => $params['updated_after'],
            );
        }

        $comments = get_comments($args);
        $items = array();
        foreach ($comments as $comment) {
            $items[] = array(
                'id' => (int)$comment->comment_ID,
                'post_id' => (int)$comment->comment_post_ID,
                'author' => $comment->comment_author,
                'author_email' => $comment->comment_author_email,
                'content' => $comment->comment_content,
                'date' => $comment->comment_date_gmt,
                'parent' => (int)$comment->comment_parent,
                'status' => $comment->comment_approved,
            );
        }

        return $this->filter_fields($items, $request);
    }

    public function get_options($request) {
        $options = array(
            'site_url' => get_option('siteurl'),
            'home_url' => get_option('home'),
            'blogname' => get_option('blogname'),
            'blogdescription' => get_option('blogdescription'),
            'permalink_structure' => get_option('permalink_structure'),
        );
        $filtered = $this->filter_fields(array($options), $request);
        return $filtered ? $filtered[0] : array();
    }

    public function get_redirects($request) {
        // Placeholder - implement detection of redirect plugins if present
        $data = array();
        return $this->filter_fields($data, $request);
    }

    public function get_health($request) {
        return array(
            'status' => 'ready',
            'version' => self::VERSION,
            'scopes' => array('content', 'taxonomies', 'media', 'menus'),
        );
    }
}

new Payload_Export_Plugin();
