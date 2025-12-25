<?php
if (!defined('ABSPATH')) exit;

class AETTAEC_Admin
{
    public static function init()
    {
        add_action('add_meta_boxes', [__CLASS__, 'register_signup_meta_boxes']);
        add_filter('manage_' . AETTAEC_CPT::POST_TYPE . '_posts_columns', [__CLASS__, 'define_signup_columns']);
        add_action('manage_' . AETTAEC_CPT::POST_TYPE . '_posts_custom_column', [__CLASS__, 'render_signup_column_content'], 10, 2);
        add_action('admin_menu', [__CLASS__, 'register_admin_menu_pages']);
        add_action('admin_init', [__CLASS__, 'register_plugin_settings']);
        add_action('admin_init', [__CLASS__, 'add_privacy_policy_information']);
        add_action('admin_post_aettaec_export_csv', [__CLASS__, 'process_csv_export']);
        add_filter('wp_privacy_personal_data_exporters', [__CLASS__, 'register_privacy_exporter']);
        add_filter('wp_privacy_personal_data_erasers', [__CLASS__, 'register_privacy_eraser']);
    }

    public static function register_signup_meta_boxes()
    {
        add_meta_box(
            'aettaec_meta',
            __('Signup Details', 'aetta-email-capture'),
            [__CLASS__, 'render_signup_meta_box_content'],
            AETTAEC_CPT::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render_signup_meta_box_content($post)
    {
        $subscriber_name = get_post_meta($post->ID, '_aettaec_name', true);
        $subscriber_email = get_post_meta($post->ID, '_aettaec_email', true);
        $ip_address = get_post_meta($post->ID, '_aettaec_ip', true);
        $created_at = get_post_meta($post->ID, '_aettaec_created_gmt', true);
        $has_consent = (int)get_post_meta($post->ID, '_aettaec_consent', true);
        $source_url = get_post_meta($post->ID, '_aettaec_source_url', true);
        $source_ref = get_post_meta($post->ID, '_aettaec_source_ref', true);
        $user_agent = get_post_meta($post->ID, '_aettaec_ua', true);

        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Name', 'aetta-email-capture') . '</th><td>' . esc_html($subscriber_name) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Email', 'aetta-email-capture') . '</th><td>' . esc_html($subscriber_email) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Created (GMT)', 'aetta-email-capture') . '</th><td>' . esc_html($created_at) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Consent', 'aetta-email-capture') . '</th><td>' . ($has_consent ? esc_html__('Yes', 'aetta-email-capture') : esc_html__('No', 'aetta-email-capture')) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Source URL', 'aetta-email-capture') . '</th><td>' . ($source_url ? '<a href="' . esc_url($source_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($source_url) . '</a>' : '') . '</td></tr>';
        echo '<tr><th>' . esc_html__('Referrer', 'aetta-email-capture') . '</th><td>' . esc_html($source_ref) . '</td></tr>';
        echo '<tr><th>' . esc_html__('IP', 'aetta-email-capture') . '</th><td>' . esc_html($ip_address) . '</td></tr>';
        echo '<tr><th>' . esc_html__('User Agent', 'aetta-email-capture') . '</th><td>' . esc_html($user_agent) . '</td></tr>';
        echo '</table>';
    }

    public static function define_signup_columns($columns)
    {
        $custom_columns = [];
        $custom_columns['cb'] = $columns['cb'];
        $custom_columns['title'] = __('Email', 'aetta-email-capture');
        $custom_columns['aettaec_name'] = __('Name', 'aetta-email-capture');
        $custom_columns['aettaec_created'] = __('Created', 'aetta-email-capture');
        $custom_columns['aettaec_source'] = __('Source', 'aetta-email-capture');
        $custom_columns['aettaec_consent'] = __('Consent', 'aetta-email-capture');
        return $custom_columns;
    }

    public static function render_signup_column_content($column_name, $post_id)
    {
        if ($column_name === 'aettaec_name') echo esc_html(get_post_meta($post_id, '_aettaec_name', true));
        if ($column_name === 'aettaec_created') echo esc_html(get_post_meta($post_id, '_aettaec_created_gmt', true));
        if ($column_name === 'aettaec_source') echo esc_html(get_post_meta($post_id, '_aettaec_source_url', true));
        if ($column_name === 'aettaec_consent') echo (get_post_meta($post_id, '_aettaec_consent', true) ? esc_html__('Yes', 'aetta-email-capture') : esc_html__('No', 'aetta-email-capture'));
    }

    public static function register_admin_menu_pages()
    {
        $base_slug = 'edit.php?post_type=' . AETTAEC_CPT::POST_TYPE;

        add_submenu_page($base_slug, __('Settings', 'aetta-email-capture'), __('Settings', 'aetta-email-capture'), 'manage_options', 'aettaec-settings', [__CLASS__, 'render_settings_page']);
        add_submenu_page($base_slug, __('Export CSV', 'aetta-email-capture'), __('Export CSV', 'aetta-email-capture'), 'manage_options', 'aettaec-export', [__CLASS__, 'render_export_page']);
        add_submenu_page($base_slug, __('Maintenance', 'aetta-email-capture'), __('Maintenance', 'aetta-email-capture'), 'manage_options', 'aettaec-maintenance', [__CLASS__, 'render_maintenance_page']);
    }

    public static function register_plugin_settings()
    {
        register_setting('aettaec_settings', 'aettaec_options', [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_plugin_options'],
            'default' => AETTAEC_Plugin::get_plugin_options(),
        ]);
    }

    private static function validate_hex_color($color_value, $fallback_color)
    {
        $clean_value = trim((string)$color_value);
        if ($clean_value === '') return $fallback_color;
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $clean_value)) return $fallback_color;
        return strtolower($clean_value);
    }

    public static function sanitize_plugin_options($input)
    {
        $current_options = AETTAEC_Plugin::get_plugin_options();

        $current_options['retention_days'] = max(1, (int)($input['retention_days'] ?? $current_options['retention_days']));
        $current_options['min_submit_seconds'] = max(0, (int)($input['min_submit_seconds'] ?? $current_options['min_submit_seconds']));
        $current_options['consent_required'] = !empty($input['consent_required']) ? 1 : 0;
        $current_options['use_css'] = !empty($input['use_css']) ? 1 : 0;

        $current_options['consent_label'] = sanitize_text_field($input['consent_label'] ?? $current_options['consent_label']);
        $current_options['success_message'] = sanitize_text_field($input['success_message'] ?? $current_options['success_message']);
        $current_options['error_invalid'] = sanitize_text_field($input['error_invalid'] ?? $current_options['error_invalid']);
        $current_options['error_wait'] = sanitize_text_field($input['error_wait'] ?? $current_options['error_wait']);
        $current_options['error_required'] = sanitize_text_field($input['error_required'] ?? $current_options['error_required']);
        $current_options['error_consent'] = sanitize_text_field($input['error_consent'] ?? $current_options['error_consent']);

        $current_options['button_label'] = sanitize_text_field($input['button_label'] ?? $current_options['button_label']);
        $current_options['name_label'] = sanitize_text_field($input['name_label'] ?? $current_options['name_label']);
        $current_options['email_label'] = sanitize_text_field($input['email_label'] ?? $current_options['email_label']);
        $current_options['name_placeholder'] = sanitize_text_field($input['name_placeholder'] ?? $current_options['name_placeholder']);
        $current_options['email_placeholder'] = sanitize_text_field($input['email_placeholder'] ?? $current_options['email_placeholder']);

        $current_options['ui_border_color'] = self::validate_hex_color($input['ui_border_color'] ?? $current_options['ui_border_color'], $current_options['ui_border_color']);
        $current_options['ui_button_bg'] = self::validate_hex_color($input['ui_button_bg'] ?? $current_options['ui_button_bg'], $current_options['ui_button_bg']);
        $current_options['ui_button_text'] = self::validate_hex_color($input['ui_button_text'] ?? $current_options['ui_button_text'], $current_options['ui_button_text']);
        $current_options['ui_success_border'] = self::validate_hex_color($input['ui_success_border'] ?? $current_options['ui_success_border'], $current_options['ui_success_border']);
        $current_options['ui_error_border'] = self::validate_hex_color($input['ui_error_border'] ?? $current_options['ui_error_border'], $current_options['ui_error_border']);

        $current_options['ui_border_width'] = min(10, max(0, (int)($input['ui_border_width'] ?? $current_options['ui_border_width'])));
        $current_options['ui_radius'] = min(60, max(0, (int)($input['ui_radius'] ?? $current_options['ui_radius'])));
        $current_options['ui_input_height'] = min(80, max(30, (int)($input['ui_input_height'] ?? $current_options['ui_input_height'])));

        return $current_options;
    }

    public static function render_settings_page()
    {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Unauthorized', 'aetta-email-capture'));
        $options = AETTAEC_Plugin::get_plugin_options();

        echo '<div class="wrap"><h1>' . esc_html__('Aetta Email Capture — Settings', 'aetta-email-capture') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('aettaec_settings');

        echo '<h2>' . esc_html__('Behavior', 'aetta-email-capture') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Retention days', 'aetta-email-capture') . '</th><td><input type="number" min="1" name="aettaec_options[retention_days]" value="' . esc_attr($options['retention_days']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Minimum submit time (seconds)', 'aetta-email-capture') . '</th><td><input type="number" min="0" name="aettaec_options[min_submit_seconds]" value="' . esc_attr($options['min_submit_seconds']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Consent required', 'aetta-email-capture') . '</th><td><label><input type="checkbox" name="aettaec_options[consent_required]" value="1" ' . checked(1, (int)$options['consent_required'], false) . '> ' . esc_html__('Enabled', 'aetta-email-capture') . '</label></td></tr>';
        echo '</table>';

        echo '<h2>' . esc_html__('Text', 'aetta-email-capture') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Consent label', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[consent_label]" value="' . esc_attr($options['consent_label']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Success message', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[success_message]" value="' . esc_attr($options['success_message']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Button label', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[button_label]" value="' . esc_attr($options['button_label']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Name label', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[name_label]" value="' . esc_attr($options['name_label']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Email label', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[email_label]" value="' . esc_attr($options['email_label']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Name placeholder', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[name_placeholder]" value="' . esc_attr($options['name_placeholder']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Email placeholder', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[email_placeholder]" value="' . esc_attr($options['email_placeholder']) . '"></td></tr>';
        echo '</table>';

        echo '<h2>' . esc_html__('Styling', 'aetta-email-capture') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Use built-in CSS', 'aetta-email-capture') . '</th><td><label><input type="checkbox" name="aettaec_options[use_css]" value="1" ' . checked(1, (int)$options['use_css'], false) . '> ' . esc_html__('Enabled', 'aetta-email-capture') . '</label></td></tr>';
        echo '</table>';

        echo '<h2>' . esc_html__('Theme Controls', 'aetta-email-capture') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Border color', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[ui_border_color]" value="' . esc_attr($options['ui_border_color']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Border width (px)', 'aetta-email-capture') . '</th><td><input type="number" min="0" max="10" name="aettaec_options[ui_border_width]" value="' . esc_attr($options['ui_border_width']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Radius (px)', 'aetta-email-capture') . '</th><td><input type="number" min="0" max="60" name="aettaec_options[ui_radius]" value="' . esc_attr($options['ui_radius']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Input height (px)', 'aetta-email-capture') . '</th><td><input type="number" min="30" max="80" name="aettaec_options[ui_input_height]" value="' . esc_attr($options['ui_input_height']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Button background', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[ui_button_bg]" value="' . esc_attr($options['ui_button_bg']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Button text color', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[ui_button_text]" value="' . esc_attr($options['ui_button_text']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Success border color', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[ui_success_border]" value="' . esc_attr($options['ui_success_border']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Error border color', 'aetta-email-capture') . '</th><td><input type="text" class="regular-text" name="aettaec_options[ui_error_border]" value="' . esc_attr($options['ui_error_border']) . '"></td></tr>';
        echo '</table>';

        submit_button();
        echo '</form></div>';
    }

    public static function render_export_page()
    {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Unauthorized', 'aetta-email-capture'));
        $export_url = wp_nonce_url(admin_url('admin-post.php?action=aettaec_export_csv'), 'aettaec_export_csv');

        echo '<div class="wrap"><h1>' . esc_html__('Export CSV', 'aetta-email-capture') . '</h1>';
        echo '<p><a class="button button-primary" href="' . esc_url($export_url) . '">' . esc_html__('Download CSV', 'aetta-email-capture') . '</a></p>';
        echo '</div>';
    }

    public static function process_csv_export()
    {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Unauthorized', 'aetta-email-capture'));
        check_admin_referer('aettaec_export_csv');

        $signups = new WP_Query([
            'post_type' => AETTAEC_CPT::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename=aetta-email-capture-' . gmdate('Ymd') . '.csv');

        $stream = fopen('php://output', 'w');
        fputcsv($stream, ['Name', 'Email', 'Created (GMT)', 'Source URL', 'Referrer', 'Consent', 'IP', 'User Agent']);

        foreach ($signups->posts as $signup_id) {
            fputcsv($stream, [
                get_post_meta($signup_id, '_aettaec_name', true),
                get_post_meta($signup_id, '_aettaec_email', true),
                get_post_meta($signup_id, '_aettaec_created_gmt', true),
                get_post_meta($signup_id, '_aettaec_source_url', true),
                get_post_meta($signup_id, '_aettaec_source_ref', true),
                get_post_meta($signup_id, '_aettaec_consent', true) ? 'yes' : 'no',
                get_post_meta($signup_id, '_aettaec_ip', true),
                get_post_meta($signup_id, '_aettaec_ua', true),
            ]);
        }

        exit;
    }

    public static function render_maintenance_page()
    {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Unauthorized', 'aetta-email-capture'));

        if (isset($_POST['aettaec_purge_now'])) {
            check_admin_referer('aettaec_purge_now');
            AETTAEC_CPT::execute_daily_purge();
            echo '<div class="notice notice-success"><p>' . esc_html__('Purge completed.', 'aetta-email-capture') . '</p></div>';
        }

        $options = AETTAEC_Plugin::get_plugin_options();
        $entry_counts = wp_count_posts(AETTAEC_CPT::POST_TYPE);

        echo '<div class="wrap"><h1>' . esc_html__('Maintenance', 'aetta-email-capture') . '</h1>';
        echo '<p>' . esc_html__('Purges signups older than', 'aetta-email-capture') . ' ' . (int)$options['retention_days'] . ' ' . esc_html__('days.', 'aetta-email-capture') . '</p>';
        echo '<p><strong>' . esc_html__('Totals', 'aetta-email-capture') . '</strong> — ' . esc_html__('Private', 'aetta-email-capture') . ': ' . (int)($entry_counts->private ?? 0) . '</p>';

        echo '<form method="post">';
        wp_nonce_field('aettaec_purge_now');
        echo '<p><button class="button button-secondary" name="aettaec_purge_now" value="1">' . esc_html__('Run purge now', 'aetta-email-capture') . '</button></p>';
        echo '</form></div>';
    }

    public static function add_privacy_policy_information()
    {
        if (!function_exists('wp_add_privacy_policy_content')) return;

        $policy_text = '<p>' . esc_html__('Aetta Email Capture stores the data you submit through its signup form.', 'aetta-email-capture') . '</p>';
        $policy_text .= '<p>' . esc_html__('Stored fields include name and email. Optionally, the site administrator can enable storing IP address and user agent.', 'aetta-email-capture') . '</p>';

        wp_add_privacy_policy_content(__('Aetta Email Capture', 'aetta-email-capture'), wp_kses_post($policy_text));
    }

    public static function register_privacy_exporter($exporters)
    {
        $exporters['aetta-email-capture'] = [
            'exporter_friendly_name' => __('Aetta Email Capture', 'aetta-email-capture'),
            'callback' => [__CLASS__, 'execute_privacy_export'],
        ];
        return $exporters;
    }

    private static function fetch_signup_ids_by_email($email_address, $page, $per_page, &$max_pages)
    {
        $query_args = [
            'post_type'      => AETTAEC_CPT::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => (int)$per_page,
            'paged'          => (int)$page,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'     => [
                [
                    'key'     => '_aettaec_email',
                    'value'   => sanitize_email($email_address),
                    'compare' => '='
                ]
            ]
        ];

        $query = new WP_Query($query_args);
        $max_pages = $query->max_num_pages;

        return $query->posts;
    }

    public static function execute_privacy_export($email_address, $page = 1)
    {
        $page = max(1, (int)$page);
        $per_page = 50;
        $max_pages = 0;
        $signup_ids = self::fetch_signup_ids_by_email($email_address, $page, $per_page, $max_pages);

        $export_data = [];
        foreach ($signup_ids as $signup_id) {
            $signup_id = (int)$signup_id;

            $items = [
                ['name' => 'name', 'value' => (string)get_post_meta($signup_id, '_aettaec_name', true)],
                ['name' => 'email', 'value' => (string)get_post_meta($signup_id, '_aettaec_email', true)],
                ['name' => 'created_gmt', 'value' => (string)get_post_meta($signup_id, '_aettaec_created_gmt', true)],
            ];

            $export_data[] = [
                'group_id' => 'aetta-email-capture',
                'group_label' => __('Aetta Email Capture', 'aetta-email-capture'),
                'item_id' => 'aettaec-' . $signup_id,
                'data' => $items,
            ];
        }

        return [
            'data' => $export_data,
            'done' => ($max_pages === 0 || $page >= $max_pages),
        ];
    }

    public static function register_privacy_eraser($erasers)
    {
        $erasers['aetta-email-capture'] = [
            'eraser_friendly_name' => __('Aetta Email Capture', 'aetta-email-capture'),
            'callback' => [__CLASS__, 'execute_privacy_erasure'],
        ];
        return $erasers;
    }

    public static function execute_privacy_erasure($email_address, $page = 1)
    {
        $page = max(1, (int)$page);
        $per_page = 50;
        $max_pages = 0;
        $signup_ids = self::fetch_signup_ids_by_email($email_address, $page, $per_page, $max_pages);

        $removed = false;
        foreach ($signup_ids as $signup_id) {
            wp_delete_post((int)$signup_id, true);
            $removed = true;
        }

        return [
            'items_removed' => $removed,
            'items_retained' => false,
            'messages' => [],
            'done' => ($max_pages === 0 || $page >= $max_pages),
        ];
    }
}
