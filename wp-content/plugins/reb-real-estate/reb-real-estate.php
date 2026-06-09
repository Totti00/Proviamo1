<?php
/**
 * Plugin Name: REB Real Estate Brokerage
 * Description: Real-estate brokerage features: listings, search, map, roles, favorites, and contact flows.
 * Version: 1.0.0
 * Author: Totti00 Team
 */

if (!defined('ABSPATH')) {
    exit;
}

final class REB_Real_Estate {
    private static ?REB_Real_Estate $instance = null;

    public static function instance(): REB_Real_Estate {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_post_types_and_taxonomies']);
        add_action('init', [$this, 'register_roles']);
        add_action('add_meta_boxes', [$this, 'register_property_meta_box']);
        add_action('save_post_property', [$this, 'save_property_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('pre_get_posts', [$this, 'restrict_agent_queries']);
        add_filter('map_meta_cap', [$this, 'restrict_agent_capabilities'], 10, 4);
        add_filter('single_template', [$this, 'property_single_template']);
        add_filter('archive_template', [$this, 'property_archive_template']);
        add_filter('manage_property_posts_columns', [$this, 'add_property_columns']);
        add_action('manage_property_posts_custom_column', [$this, 'render_property_columns'], 10, 2);
        add_action('admin_post_reb_contact_agent', [$this, 'handle_contact_form']);
        add_action('admin_post_nopriv_reb_contact_agent', [$this, 'handle_contact_form']);
        add_action('wp_ajax_reb_toggle_favorite', [$this, 'toggle_favorite']);
        add_shortcode('reb_property_search', [$this, 'search_shortcode']);
        add_shortcode('reb_client_register', [$this, 'client_register_shortcode']);
        add_action('wp_head', [$this, 'output_property_schema']);
    }

    public function register_post_types_and_taxonomies(): void {
        register_post_type('property', [
            'labels' => [
                'name' => __('Properties', 'reb-real-estate'),
                'singular_name' => __('Property', 'reb-real-estate'),
                'add_new_item' => __('Add New Property', 'reb-real-estate'),
                'edit_item' => __('Edit Property', 'reb-real-estate'),
            ],
            'public' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'properties'],
            'supports' => ['title', 'editor', 'thumbnail', 'author', 'excerpt'],
            'show_in_rest' => true,
            'menu_icon' => 'dashicons-admin-home',
            'capability_type' => ['property', 'properties'],
            'map_meta_cap' => true,
        ]);

        register_taxonomy('property_type', 'property', [
            'labels' => [
                'name' => __('Property Types', 'reb-real-estate'),
                'singular_name' => __('Property Type', 'reb-real-estate'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'property-type'],
        ]);

        register_taxonomy('property_location', 'property', [
            'labels' => [
                'name' => __('Locations', 'reb-real-estate'),
                'singular_name' => __('Location', 'reb-real-estate'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'property-location'],
        ]);
    }

    public function register_roles(): void {
        if (!get_role('agent')) {
            add_role('agent', 'Agent', [
                'read' => true,
                'upload_files' => true,
                'edit_properties' => true,
                'edit_property' => true,
                'edit_published_properties' => true,
                'publish_properties' => true,
                'delete_property' => true,
                'delete_properties' => true,
                'delete_published_properties' => true,
            ]);
        }

        if (!get_role('client')) {
            add_role('client', 'Client', ['read' => true]);
        }

        $admin = get_role('administrator');
        if ($admin) {
            $caps = [
                'edit_property', 'read_property', 'delete_property', 'edit_properties',
                'edit_others_properties', 'publish_properties', 'read_private_properties',
                'delete_properties', 'delete_private_properties', 'delete_published_properties',
                'delete_others_properties', 'edit_private_properties', 'edit_published_properties',
            ];
            foreach ($caps as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    public function register_property_meta_box(): void {
        add_meta_box(
            'reb_property_details',
            __('Property Details', 'reb-real-estate'),
            [$this, 'render_property_meta_box'],
            'property',
            'normal',
            'high'
        );
    }

    public function render_property_meta_box(WP_Post $post): void {
        wp_nonce_field('reb_property_meta', 'reb_property_meta_nonce');

        $fields = [
            'price' => get_post_meta($post->ID, '_reb_price', true),
            'status' => get_post_meta($post->ID, '_reb_status', true) ?: 'available',
            'address' => get_post_meta($post->ID, '_reb_address', true),
            'latitude' => get_post_meta($post->ID, '_reb_latitude', true),
            'longitude' => get_post_meta($post->ID, '_reb_longitude', true),
            'features' => get_post_meta($post->ID, '_reb_features', true),
            'gallery_ids' => get_post_meta($post->ID, '_reb_gallery_ids', true),
        ];

        ?>
        <style>
            .reb-grid {display:grid;grid-template-columns:repeat(2,minmax(220px,1fr));gap:12px}
            .reb-field label{display:block;font-weight:600;margin-bottom:4px}
            .reb-field input,.reb-field select,.reb-field textarea{width:100%}
            @media (max-width: 782px){.reb-grid{grid-template-columns:1fr}}
        </style>
        <div class="reb-grid">
            <div class="reb-field">
                <label for="reb_price"><?php esc_html_e('Price', 'reb-real-estate'); ?></label>
                <input id="reb_price" name="reb_price" type="number" step="0.01" min="0" value="<?php echo esc_attr($fields['price']); ?>" />
            </div>
            <div class="reb-field">
                <label for="reb_status"><?php esc_html_e('Status', 'reb-real-estate'); ?></label>
                <select id="reb_status" name="reb_status">
                    <option value="available" <?php selected($fields['status'], 'available'); ?>><?php esc_html_e('Available', 'reb-real-estate'); ?></option>
                    <option value="sold" <?php selected($fields['status'], 'sold'); ?>><?php esc_html_e('Sold', 'reb-real-estate'); ?></option>
                </select>
            </div>
            <div class="reb-field" style="grid-column:1/-1">
                <label for="reb_address"><?php esc_html_e('Address', 'reb-real-estate'); ?></label>
                <input id="reb_address" name="reb_address" type="text" value="<?php echo esc_attr($fields['address']); ?>" />
            </div>
            <div class="reb-field">
                <label for="reb_latitude"><?php esc_html_e('Latitude', 'reb-real-estate'); ?></label>
                <input id="reb_latitude" name="reb_latitude" type="text" value="<?php echo esc_attr($fields['latitude']); ?>" />
            </div>
            <div class="reb-field">
                <label for="reb_longitude"><?php esc_html_e('Longitude', 'reb-real-estate'); ?></label>
                <input id="reb_longitude" name="reb_longitude" type="text" value="<?php echo esc_attr($fields['longitude']); ?>" />
            </div>
            <div class="reb-field" style="grid-column:1/-1">
                <label for="reb_features"><?php esc_html_e('Features (comma-separated)', 'reb-real-estate'); ?></label>
                <textarea id="reb_features" name="reb_features" rows="2"><?php echo esc_textarea($fields['features']); ?></textarea>
            </div>
            <div class="reb-field" style="grid-column:1/-1">
                <label for="reb_gallery_ids"><?php esc_html_e('Gallery Image IDs (comma-separated)', 'reb-real-estate'); ?></label>
                <input id="reb_gallery_ids" name="reb_gallery_ids" type="text" value="<?php echo esc_attr($fields['gallery_ids']); ?>" />
                <p><button type="button" class="button" id="reb-select-gallery"><?php esc_html_e('Select Gallery Images', 'reb-real-estate'); ?></button></p>
            </div>
        </div>
        <?php
    }

    public function save_property_meta(int $post_id): void {
        if (!isset($_POST['reb_property_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['reb_property_meta_nonce'])), 'reb_property_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $price = isset($_POST['reb_price']) ? (float) wp_unslash($_POST['reb_price']) : 0;
        $status = isset($_POST['reb_status']) ? sanitize_text_field(wp_unslash($_POST['reb_status'])) : 'available';
        $address = isset($_POST['reb_address']) ? sanitize_text_field(wp_unslash($_POST['reb_address'])) : '';
        $latitude = isset($_POST['reb_latitude']) ? sanitize_text_field(wp_unslash($_POST['reb_latitude'])) : '';
        $longitude = isset($_POST['reb_longitude']) ? sanitize_text_field(wp_unslash($_POST['reb_longitude'])) : '';
        $features = isset($_POST['reb_features']) ? sanitize_text_field(wp_unslash($_POST['reb_features'])) : '';
        $gallery_ids = isset($_POST['reb_gallery_ids']) ? sanitize_text_field(wp_unslash($_POST['reb_gallery_ids'])) : '';

        update_post_meta($post_id, '_reb_price', max(0, $price));
        update_post_meta($post_id, '_reb_status', in_array($status, ['available', 'sold'], true) ? $status : 'available');
        update_post_meta($post_id, '_reb_address', $address);
        update_post_meta($post_id, '_reb_latitude', $latitude);
        update_post_meta($post_id, '_reb_longitude', $longitude);
        update_post_meta($post_id, '_reb_features', $features);
        update_post_meta($post_id, '_reb_gallery_ids', preg_replace('/[^0-9,]/', '', $gallery_ids));
    }

    public function enqueue_admin_assets(string $hook): void {
        if (!in_array($hook, ['post-new.php', 'post.php'], true)) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'property') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script('reb-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], '1.0.0', true);
    }

    public function enqueue_frontend_assets(): void {
        wp_enqueue_style('reb-style', plugin_dir_url(__FILE__) . 'assets/style.css', [], '1.0.0');
        wp_enqueue_script('reb-front', plugin_dir_url(__FILE__) . 'assets/front.js', [], '1.0.0', true);

        wp_localize_script('reb-front', 'rebData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('reb_favorite_nonce'),
            'saveFavoriteText' => __('Save to favorites', 'reb-real-estate'),
            'removeFavoriteText' => __('Remove from favorites', 'reb-real-estate'),
        ]);

        wp_enqueue_style('reb-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
        wp_enqueue_script('reb-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
    }

    public function restrict_agent_queries(WP_Query $query): void {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $user = wp_get_current_user();
        if (!in_array('agent', (array) $user->roles, true)) {
            return;
        }

        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'property') {
            $query->set('author', $user->ID);
        }
    }

    public function restrict_agent_capabilities(array $caps, string $cap, int $user_id, array $args): array {
        $agent_caps = ['edit_post', 'delete_post', 'read_post'];
        if (!in_array($cap, $agent_caps, true) || empty($args[0])) {
            return $caps;
        }

        $post = get_post((int) $args[0]);
        if (!$post || $post->post_type !== 'property') {
            return $caps;
        }

        $user = get_userdata($user_id);
        if (!$user || !in_array('agent', (array) $user->roles, true)) {
            return $caps;
        }

        if ((int) $post->post_author !== $user_id) {
            return ['do_not_allow'];
        }

        return $caps;
    }

    public function property_single_template(string $template): string {
        if (is_singular('property')) {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/single-property.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public function property_archive_template(string $template): string {
        if (is_post_type_archive('property')) {
            $plugin_template = plugin_dir_path(__FILE__) . 'templates/archive-property.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public function add_property_columns(array $columns): array {
        $columns['reb_status'] = __('Status', 'reb-real-estate');
        $columns['reb_price'] = __('Price', 'reb-real-estate');
        return $columns;
    }

    public function render_property_columns(string $column, int $post_id): void {
        if ($column === 'reb_status') {
            echo esc_html(ucfirst((string) get_post_meta($post_id, '_reb_status', true)));
        }

        if ($column === 'reb_price') {
            $price = (float) get_post_meta($post_id, '_reb_price', true);
            echo esc_html('$' . number_format_i18n($price, 0));
        }
    }

    public function search_shortcode(): string {
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/archive-property.php';
        return (string) ob_get_clean();
    }

    public function client_register_shortcode(): string {
        $message = '';

        if (isset($_POST['reb_register_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['reb_register_nonce'])), 'reb_register')) {
            $username = sanitize_user((string) wp_unslash($_POST['username'] ?? ''));
            $email = sanitize_email((string) wp_unslash($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $user_id = wp_create_user($username, $password, $email);
            if (is_wp_error($user_id)) {
                $message = '<p class="reb-notice error">' . esc_html($user_id->get_error_message()) . '</p>';
            } else {
                wp_update_user(['ID' => $user_id, 'role' => 'client']);
                $message = '<p class="reb-notice success">' . esc_html__('Registration completed. You can now log in.', 'reb-real-estate') . '</p>';
            }
        }

        ob_start();
        ?>
        <div class="reb-register-form">
            <?php echo wp_kses_post($message); ?>
            <form method="post">
                <?php wp_nonce_field('reb_register', 'reb_register_nonce'); ?>
                <label><?php esc_html_e('Username', 'reb-real-estate'); ?><input required type="text" name="username"></label>
                <label><?php esc_html_e('Email', 'reb-real-estate'); ?><input required type="email" name="email"></label>
                <label><?php esc_html_e('Password', 'reb-real-estate'); ?><input required type="password" name="password"></label>
                <button type="submit"><?php esc_html_e('Create Client Account', 'reb-real-estate'); ?></button>
            </form>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public static function get_filtered_properties(int $paged = 1): WP_Query {
        $args = [
            'post_type' => 'property',
            'post_status' => 'publish',
            'posts_per_page' => 9,
            'paged' => max(1, $paged),
            'meta_query' => ['relation' => 'AND'],
            'tax_query' => ['relation' => 'AND'],
        ];

        $min = self::request_float('price_min');
        $max = self::request_float('price_max');

        if ($min > 0) {
            $args['meta_query'][] = [
                'key' => '_reb_price',
                'value' => $min,
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }

        if ($max > 0) {
            $args['meta_query'][] = [
                'key' => '_reb_price',
                'value' => $max,
                'compare' => '<=',
                'type' => 'NUMERIC',
            ];
        }

        if (self::request_text('include_sold') === '') {
            $args['meta_query'][] = [
                'key' => '_reb_status',
                'value' => 'sold',
                'compare' => '!=',
            ];
        }

        $property_type = self::request_text('property_type');
        if ($property_type !== '') {
            $args['tax_query'][] = [
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => $property_type,
            ];
        }

        $property_location = self::request_text('property_location');
        if ($property_location !== '') {
            $args['tax_query'][] = [
                'taxonomy' => 'property_location',
                'field' => 'slug',
                'terms' => $property_location,
            ];
        }

        return new WP_Query($args);
    }

    public static function get_gallery_ids(int $post_id): array {
        $gallery = (string) get_post_meta($post_id, '_reb_gallery_ids', true);
        if ($gallery === '') {
            return [];
        }
        $ids = array_filter(array_map('absint', explode(',', $gallery)));
        return array_values($ids);
    }

    public static function is_favorite(int $post_id, int $user_id): bool {
        $favs = get_user_meta($user_id, 'reb_favorites', true);
        if (!is_array($favs)) {
            return false;
        }
        return in_array($post_id, array_map('intval', $favs), true);
    }

    public static function request_text(string $key): string {
        return isset($_GET[$key]) ? sanitize_text_field(wp_unslash((string) $_GET[$key])) : '';
    }

    public static function request_float(string $key): float {
        return (float) self::request_text($key);
    }

    public function toggle_favorite(): void {
        check_ajax_referer('reb_favorite_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Please log in as client.', 'reb-real-estate')], 403);
        }

        $user = wp_get_current_user();
        if (!in_array('client', (array) $user->roles, true)) {
            wp_send_json_error(['message' => __('Only client accounts can use favorites.', 'reb-real-estate')], 403);
        }

        $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
        if (!$post_id || get_post_type($post_id) !== 'property') {
            wp_send_json_error(['message' => __('Invalid property.', 'reb-real-estate')], 400);
        }

        $favorites = get_user_meta($user->ID, 'reb_favorites', true);
        $favorites = is_array($favorites) ? array_map('intval', $favorites) : [];

        if (in_array($post_id, $favorites, true)) {
            $favorites = array_values(array_diff($favorites, [$post_id]));
            $state = false;
        } else {
            $favorites[] = $post_id;
            $favorites = array_values(array_unique($favorites));
            $state = true;
        }

        update_user_meta($user->ID, 'reb_favorites', $favorites);
        wp_send_json_success(['favorite' => $state]);
    }

    public function handle_contact_form(): void {
        if (!isset($_POST['reb_contact_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['reb_contact_nonce'])), 'reb_contact')) {
            wp_die(esc_html__('Invalid request.', 'reb-real-estate'));
        }

        $property_id = isset($_POST['property_id']) ? absint(wp_unslash($_POST['property_id'])) : 0;
        $name = sanitize_text_field((string) wp_unslash($_POST['name'] ?? ''));
        $email = sanitize_email((string) wp_unslash($_POST['email'] ?? ''));
        $message = sanitize_textarea_field((string) wp_unslash($_POST['message'] ?? ''));

        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'property') {
            wp_die(esc_html__('Property not found.', 'reb-real-estate'));
        }

        $author_email = get_the_author_meta('user_email', (int) $property->post_author);
        $to = $author_email ?: get_option('admin_email');
        $subject = sprintf(__('New inquiry for %s', 'reb-real-estate'), $property->post_title);
        $body = sprintf("Name: %s\nEmail: %s\n\nMessage:\n%s", $name, $email, $message);

        $sent = wp_mail($to, $subject, $body);
        $redirect = add_query_arg('reb_contact', $sent ? 'sent' : 'failed', get_permalink($property_id));
        wp_safe_redirect($redirect);
        exit;
    }

    public function output_property_schema(): void {
        if (!is_singular('property')) {
            return;
        }

        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Residence',
            'name' => get_the_title($post_id),
            'description' => wp_strip_all_tags((string) get_post_field('post_content', $post_id)),
            'url' => get_permalink($post_id),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => get_post_meta($post_id, '_reb_address', true),
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => (float) get_post_meta($post_id, '_reb_price', true),
                'priceCurrency' => 'USD',
                'availability' => get_post_meta($post_id, '_reb_status', true) === 'sold' ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock',
            ],
        ];

        $json = wp_json_encode(
            $schema,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        if ($json) {
            $script = sprintf('<script type="application/ld+json">%s</script>', $json);
            echo wp_kses($script, ['script' => ['type' => true]]);
        }
    }
}

register_activation_hook(__FILE__, static function (): void {
    REB_Real_Estate::instance()->register_post_types_and_taxonomies();
    REB_Real_Estate::instance()->register_roles();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, static function (): void {
    flush_rewrite_rules();
});

REB_Real_Estate::instance();
