<?php
if (!defined('ABSPATH')) exit;

class QEC_Admin
{
    public static function init()
    {
        add_action('add_meta_boxes', [__CLASS__, 'meta_boxes']);
        add_filter('manage_' . QEC_CPT::POST_TYPE . '_posts_columns', [__CLASS__, 'columns']);
        add_action('manage_' . QEC_CPT::POST_TYPE . '_posts_custom_column', [__CLASS__, 'column_values'], 10, 2);
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_init', [__CLASS__, 'privacy_policy_content']);
        add_action('admin_post_qec_export_csv', [__CLASS__, 'export_csv']);
        add_filter('wp_privacy_personal_data_exporters', [__CLASS__, 'register_exporter']);
        add_filter('wp_privacy_personal_data_erasers', [__CLASS__, 'register_eraser']);
    }

    public static function meta_boxes()
    {
        add_meta_box('qec_meta', __('Signup Details', 'quick-email-capture'), [__CLASS__, 'meta_render'], QEC_CPT::POST_TYPE, 'normal', 'high');
    }

    public static function meta_render($post)
    {
        $name = get_post_meta($post->ID, '_qec_name', true);
        $email = get_post_meta($post->ID, '_qec_email', true);
        $ip = get_post_meta($post->ID, '_qec_ip', true);
        $created = get_post_meta($post->ID, '_qec_created_gmt', true);
        $consent = (int)get_post_meta($post->ID, '_qec_consent', true);
        $source_url = get_post_meta($post->ID, '_qec_source_url', true);
        $source_ref = get_post_meta($post->ID, '_qec_source_ref', true);
        $ua = get_post_meta($post->ID, '_qec_ua', true);

        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Name', 'quick-email-capture') . '</th><td>' . esc_html($name) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Email', 'quick-email-capture') . '</th><td>' . esc_html($email) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Created (GMT)', 'quick-email-capture') . '</th><td>' . esc_html($created) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Consent', 'quick-email-capture') . '</th><td>' . ($consent ? esc_html__('Yes', 'quick-email-capture') : esc_html__('No', 'quick-email-capture')) . '</td></tr>';
        echo '<tr><th>' . esc_html__('Source URL', 'quick-email-capture') . '</th><td>' . ($source_url ? '<a href="' . esc_url($source_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($source_url) . '</a>' : '') . '</td></tr>';
        echo '<tr><th>' . esc_html__('Referrer', 'quick-email-capture') . '</th><td>' . esc_html($source_ref) . '</td></tr>';
        echo '<tr><th>' . esc_html__('IP', 'quick-email-capture') . '</th><td>' . esc_html($ip) . '</td></tr>';
        echo '<tr><th>' . esc_html__('User Agent', 'quick-email-capture') . '</th><td>' . esc_html($ua) . '</td></tr>';
        echo '</table>';
    }

    public static function columns($cols)
    {
        $new = [];
        $new['cb'] = $cols['cb'];
        $new['title'] = __('Email', 'quick-email-capture');
        $new['qec_name'] = __('Name', 'quick-email-capture');
        $new['qec_created'] = __('Created', 'quick-email-capture');
        $new['qec_source'] = __('Source', 'quick-email-capture');
        $new['qec_consent'] = __('Consent', 'quick-email-capture');
        return $new;
    }

    public static function column_values($col, $post_id)
    {
        if ($col === 'qec_name') echo esc_html(get_post_meta($post_id, '_qec_name', true));
        if ($col === 'qec_created') echo esc_html(get_post_meta($post_id, '_qec_created_gmt', true));
        if ($col === 'qec_source') echo esc_html(get_post_meta($post_id, '_qec_source_url', true));
        if ($col === 'qec_consent') echo (get_post_meta($post_id, '_qec_consent', true) ? esc_html__('Yes', 'quick-email-capture') : esc_html__('No', 'quick-email-capture'));
    }

    public static function menu()
    {
        add_submenu_page(
            'edit.php?post_type=' . QEC_CPT::POST_TYPE,
            __('Settings', 'quick-email-capture'),
            __('Settings', 'quick-email-capture'),
            'manage_options',
            'qec-settings',
            [__CLASS__, 'settings_page']
        );

        add_submenu_page(
            'edit.php?post_type=' . QEC_CPT::POST_TYPE,
            __('Export CSV', 'quick-email-capture'),
            __('Export CSV', 'quick-email-capture'),
            'manage_options',
            'qec-export',
            [__CLASS__, 'export_page']
        );

        add_submenu_page(
            'edit.php?post_type=' . QEC_CPT::POST_TYPE,
            __('Maintenance', 'quick-email-capture'),
            __('Maintenance', 'quick-email-capture'),
            'manage_options',
            'qec-maintenance',
            [__CLASS__, 'maintenance_page']
        );
    }

    public static function register_settings()
    {
        register_setting('qec_settings', 'qec_options', [
            'type' => 'array',
            'sanitize_callback' => [__CLASS__, 'sanitize_options'],
            'default' => QEC_Plugin::default_options(),
        ]);
    }

    private static function sanitize_hex_color_loose($value, $fallback)
    {
        $v = trim((string)$value);
        if ($v === '') return $fallback;
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $v)) return $fallback;
        return strtolower($v);
    }

    public static function sanitize_options($input)
    {
        $o = QEC_Plugin::opts();

        $o['retention_days'] = max(1, (int)($input['retention_days'] ?? $o['retention_days']));
        $o['rate_limit_seconds'] = max(0, (int)($input['rate_limit_seconds'] ?? $o['rate_limit_seconds']));
        $o['min_submit_seconds'] = max(0, (int)($input['min_submit_seconds'] ?? $o['min_submit_seconds']));
        $o['store_ip'] = !empty($input['store_ip']) ? 1 : 0;
        $o['store_user_agent'] = !empty($input['store_user_agent']) ? 1 : 0;
        $o['consent_required'] = !empty($input['consent_required']) ? 1 : 0;
        $o['use_css'] = !empty($input['use_css']) ? 1 : 0;

        $o['wrapper_class'] = sanitize_text_field($input['wrapper_class'] ?? $o['wrapper_class']);
        $o['input_class'] = sanitize_text_field($input['input_class'] ?? $o['input_class']);
        $o['button_class'] = sanitize_text_field($input['button_class'] ?? $o['button_class']);
        $o['message_class'] = sanitize_text_field($input['message_class'] ?? $o['message_class']);

        $o['consent_label'] = sanitize_text_field($input['consent_label'] ?? $o['consent_label']);
        $o['success_message'] = sanitize_text_field($input['success_message'] ?? $o['success_message']);
        $o['error_invalid'] = sanitize_text_field($input['error_invalid'] ?? $o['error_invalid']);
        $o['error_wait'] = sanitize_text_field($input['error_wait'] ?? $o['error_wait']);
        $o['error_required'] = sanitize_text_field($input['error_required'] ?? $o['error_required']);
        $o['error_consent'] = sanitize_text_field($input['error_consent'] ?? $o['error_consent']);

        $o['button_label'] = sanitize_text_field($input['button_label'] ?? $o['button_label']);
        $o['name_label'] = sanitize_text_field($input['name_label'] ?? $o['name_label']);
        $o['email_label'] = sanitize_text_field($input['email_label'] ?? $o['email_label']);
        $o['name_placeholder'] = sanitize_text_field($input['name_placeholder'] ?? $o['name_placeholder']);
        $o['email_placeholder'] = sanitize_text_field($input['email_placeholder'] ?? $o['email_placeholder']);

        $o['ui_border_color'] = self::sanitize_hex_color_loose($input['ui_border_color'] ?? $o['ui_border_color'], $o['ui_border_color']);
        $o['ui_button_bg'] = self::sanitize_hex_color_loose($input['ui_button_bg'] ?? $o['ui_button_bg'], $o['ui_button_bg']);
        $o['ui_button_text'] = self::sanitize_hex_color_loose($input['ui_button_text'] ?? $o['ui_button_text'], $o['ui_button_text']);
        $o['ui_success_border'] = self::sanitize_hex_color_loose($input['ui_success_border'] ?? $o['ui_success_border'], $o['ui_success_border']);
        $o['ui_error_border'] = self::sanitize_hex_color_loose($input['ui_error_border'] ?? $o['ui_error_border'], $o['ui_error_border']);

        $o['ui_border_width'] = min(10, max(0, (int)($input['ui_border_width'] ?? $o['ui_border_width'])));
        $o['ui_radius'] = min(60, max(0, (int)($input['ui_radius'] ?? $o['ui_radius'])));
        $o['ui_input_height'] = min(80, max(30, (int)($input['ui_input_height'] ?? $o['ui_input_height'])));

        return $o;
    }

    public static function settings_page()
    {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Unauthorized', 'quick-email-capture'));
        $o = QEC_Plugin::opts();

        echo '<div class="wrap"><h1>' . esc_html__('Quick Email Capture — Settings', 'quick-email-capture') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('qec_settings');

        echo '<h2>' . esc_html__('Behavior', 'quick-email-capture') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Retention days', 'quick-email-capture') . '</th><td><input type="number" min="1" name="qec_options[retention_days]" value="' . esc_attr($o['retention_days']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Rate limit (seconds)', 'quick-email-capture') . '</th><td><input type="number" min="0" name="qec_options[rate_limit_seconds]" value="' . esc_attr($o['rate_limit_seconds']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Minimum submit time (seconds)', 'quick-email-capture') . '</th><td><input type="number" min="0" name="qec_options[min_submit_seconds]" value="' . esc_attr($o['min_submit_seconds']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Store IP address', 'quick-email-capture') . '</th><td><label><input type="checkbox" name="qec_options[store_ip]" value="1" ' . checked(1, (int)$o['store_ip'], false) . '> ' . esc_html__('Enabled', 'quick-email-capture') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Store user agent', 'quick-email-capture') . '</th><td><label><input type="checkbox" name="qec_options[store_user_agent]" value="1" ' . checked(1, (int)$o['store_user_agent'], false) . '> ' . esc_html__('Enabled', 'quick-email-capture') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Consent required', 'quick-email-capture') . '</th><td><label><input type="checkbox" name="qec_options[consent_required]" value="1" ' . checked(1, (int)$o['consent_required'], false) . '> ' . esc_html__('Enabled', 'quick-email-capture') . '</label></td></tr>';
        echo '</table>';

        echo '<h2>' . esc_html__('Text', 'quick-email-capture') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Consent label', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[consent_label]" value="' . esc_attr($o['consent_label']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Success message', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[success_message]" value="' . esc_attr($o['success_message']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Button label', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[button_label]" value="' . esc_attr($o['button_label']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Name label', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[name_label]" value="' . esc_attr($o['name_label']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Email label', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[email_label]" value="' . esc_attr($o['email_label']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Name placeholder', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[name_placeholder]" value="' . esc_attr($o['name_placeholder']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Email placeholder', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[email_placeholder]" value="' . esc_attr($o['email_placeholder']) . '"></td></tr>';
        echo '</table>';

        echo '<h2>' . esc_html__('Styling', 'quick-email-capture') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Use built-in CSS', 'quick-email-capture') . '</th><td><label><input type="checkbox" name="qec_options[use_css]" value="1" ' . checked(1, (int)$o['use_css'], false) . '> ' . esc_html__('Enabled', 'quick-email-capture') . '</label></td></tr>';
        echo '<tr><th>' . esc_html__('Extra wrapper class', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[wrapper_class]" value="' . esc_attr($o['wrapper_class']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Extra input class', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[input_class]" value="' . esc_attr($o['input_class']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Extra button class', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[button_class]" value="' . esc_attr($o['button_class']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Extra message class', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[message_class]" value="' . esc_attr($o['message_class']) . '"></td></tr>';
        echo '</table>';

        echo '<h2>' . esc_html__('Theme Controls', 'quick-email-capture') . '</h2>';
        echo '<table class="form-table">';
        echo '<tr><th>' . esc_html__('Border color', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[ui_border_color]" value="' . esc_attr($o['ui_border_color']) . '" placeholder="#1d2327"></td></tr>';
        echo '<tr><th>' . esc_html__('Border width (px)', 'quick-email-capture') . '</th><td><input type="number" min="0" max="10" name="qec_options[ui_border_width]" value="' . esc_attr($o['ui_border_width']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Radius (px)', 'quick-email-capture') . '</th><td><input type="number" min="0" max="60" name="qec_options[ui_radius]" value="' . esc_attr($o['ui_radius']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Input height (px)', 'quick-email-capture') . '</th><td><input type="number" min="30" max="80" name="qec_options[ui_input_height]" value="' . esc_attr($o['ui_input_height']) . '"></td></tr>';
        echo '<tr><th>' . esc_html__('Button background', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[ui_button_bg]" value="' . esc_attr($o['ui_button_bg']) . '" placeholder="#1d2327"></td></tr>';
        echo '<tr><th>' . esc_html__('Button text color', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[ui_button_text]" value="' . esc_attr($o['ui_button_text']) . '" placeholder="#ffffff"></td></tr>';
        echo '<tr><th>' . esc_html__('Success border color', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[ui_success_border]" value="' . esc_attr($o['ui_success_border']) . '" placeholder="#00a32a"></td></tr>';
        echo '<tr><th>' . esc_html__('Error border color', 'quick-email-capture') . '</th><td><input type="text" class="regular-text" name="qec_options[ui_error_border]" value="' . esc_attr($o['ui_error_border']) . '" placeholder="#d63638"></td></tr>';
        echo '</table>';

        echo '<div class="notice notice-info"><p><strong>' . esc_html__('CSS hooks', 'quick-email-capture') . '</strong></p>';
        echo '<p><code>.qec-form</code> <code>.qec-msg</code> <code>.qec-success</code> <code>.qec-error</code> <code>.qec-consent</code></p>';
        echo '</div>';

        submit_button();
        echo '</form></div>';
    }

    public static function export_page()
    {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Unauthorized', 'quick-email-capture'));
        $url = wp_nonce_url(admin_url('admin-post.php?action=qec_export_csv'), 'qec_export_csv');

        echo '<div class="wrap"><h1>' . esc_html__('Export CSV', 'quick-email-capture') . '</h1>';
        echo '<p><a class="button button-primary" href="' . esc_url($url) . '">' . esc_html__('Download CSV', 'quick-email-capture') . '</a></p>';
        echo '</div>';
    }

    private static function csv_safe($v)
    {
        $v = (string)$v;
        if ($v !== '' && in_array($v[0], ['=', '+', '-', '@'], true)) return "'" . $v;
        return $v;
    }

    public static function export_csv()
    {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Unauthorized', 'quick-email-capture'));
        check_admin_referer('qec_export_csv');

        $q = new WP_Query([
            'post_type' => QEC_CPT::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename=quick-email-capture-' . gmdate('Ymd') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Name', 'Email', 'Created (GMT)', 'Source URL', 'Referrer', 'Consent', 'IP', 'User Agent']);

        foreach ($q->posts as $pid) {
            fputcsv($out, [
                self::csv_safe(get_post_meta($pid, '_qec_name', true)),
                self::csv_safe(get_post_meta($pid, '_qec_email', true)),
                self::csv_safe(get_post_meta($pid, '_qec_created_gmt', true)),
                self::csv_safe(get_post_meta($pid, '_qec_source_url', true)),
                self::csv_safe(get_post_meta($pid, '_qec_source_ref', true)),
                get_post_meta($pid, '_qec_consent', true) ? 'yes' : 'no',
                self::csv_safe(get_post_meta($pid, '_qec_ip', true)),
                self::csv_safe(get_post_meta($pid, '_qec_ua', true)),
            ]);
        }

        exit;
    }

    public static function maintenance_page()
    {
        if (!current_user_can('manage_options')) wp_die(esc_html__('Unauthorized', 'quick-email-capture'));

        if (isset($_POST['qec_purge_now'])) {
            check_admin_referer('qec_purge_now');
            QEC_CPT::run_purge();
            echo '<div class="notice notice-success"><p>' . esc_html__('Purge completed.', 'quick-email-capture') . '</p></div>';
        }

        $o = QEC_Plugin::opts();
        $counts = wp_count_posts(QEC_CPT::POST_TYPE);

        echo '<div class="wrap"><h1>' . esc_html__('Maintenance', 'quick-email-capture') . '</h1>';
        /* translators: %d: number of retention days */
        echo '<p>' . sprintf(esc_html__('Purges signups older than %d days.', 'quick-email-capture'), (int)$o['retention_days']) . '</p>';
        echo '<p><strong>' . esc_html__('Totals', 'quick-email-capture') . '</strong> — ' . esc_html__('Private', 'quick-email-capture') . ': ' . (int)($counts->private ?? 0) . '</p>';

        echo '<form method="post">';
        wp_nonce_field('qec_purge_now');
        echo '<p><button class="button button-secondary" name="qec_purge_now" value="1">' . esc_html__('Run purge now', 'quick-email-capture') . '</button></p>';
        echo '</form></div>';
    }

    public static function privacy_policy_content()
    {
        if (!function_exists('wp_add_privacy_policy_content')) return;

        $content = '<p>' . esc_html__('Quick Email Capture stores the data you submit through its signup form.', 'quick-email-capture') . '</p>';
        $content .= '<p>' . esc_html__('Stored fields include name and email. Optionally, the site administrator can enable storing IP address and user agent.', 'quick-email-capture') . '</p>';
        $content .= '<p>' . esc_html__('This data is stored as private entries in the WordPress database and can be exported as CSV by administrators.', 'quick-email-capture') . '</p>';

        wp_add_privacy_policy_content(__('Quick Email Capture', 'quick-email-capture'), wp_kses_post($content));
    }

    public static function register_exporter($exporters)
    {
        $exporters['quick-email-capture'] = [
            'exporter_friendly_name' => __('Quick Email Capture', 'quick-email-capture'),
            'callback' => [__CLASS__, 'exporter_callback'],
        ];
        return $exporters;
    }

    public static function exporter_callback($email_address, $page = 1)
    {
        $page = max(1, (int)$page);
        $per_page = 50;

        $q = new WP_Query([
            'post_type' => QEC_CPT::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => [[
                'key' => '_qec_email',
                'value' => $email_address,
                'compare' => '=',
            ]],
            'fields' => 'ids',
        ]);

        $data = [];
        foreach ($q->posts as $pid) {
            $item = [
                ['name' => 'name', 'value' => (string)get_post_meta($pid, '_qec_name', true)],
                ['name' => 'email', 'value' => (string)get_post_meta($pid, '_qec_email', true)],
                ['name' => 'created_gmt', 'value' => (string)get_post_meta($pid, '_qec_created_gmt', true)],
                ['name' => 'source_url', 'value' => (string)get_post_meta($pid, '_qec_source_url', true)],
                ['name' => 'referrer', 'value' => (string)get_post_meta($pid, '_qec_source_ref', true)],
                ['name' => 'consent', 'value' => (string)get_post_meta($pid, '_qec_consent', true)],
                ['name' => 'ip', 'value' => (string)get_post_meta($pid, '_qec_ip', true)],
                ['name' => 'user_agent', 'value' => (string)get_post_meta($pid, '_qec_ua', true)],
            ];

            $data[] = [
                'group_id' => 'quick-email-capture',
                'group_label' => __('Quick Email Capture', 'quick-email-capture'),
                'item_id' => 'qec-' . $pid,
                'data' => $item,
            ];
        }

        $done = ($q->max_num_pages <= $page);

        return [
            'data' => $data,
            'done' => $done,
        ];
    }

    public static function register_eraser($erasers)
    {
        $erasers['quick-email-capture'] = [
            'eraser_friendly_name' => __('Quick Email Capture', 'quick-email-capture'),
            'callback' => [__CLASS__, 'eraser_callback'],
        ];
        return $erasers;
    }

    public static function eraser_callback($email_address, $page = 1)
    {
        $page = max(1, (int)$page);
        $per_page = 50;

        $q = new WP_Query([
            'post_type' => QEC_CPT::POST_TYPE,
            'post_status' => 'any',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => [[
                'key' => '_qec_email',
                'value' => $email_address,
                'compare' => '=',
            ]],
            'fields' => 'ids',
        ]);

        $removed = false;
        foreach ($q->posts as $pid) {
            wp_delete_post($pid, true);
            $removed = true;
        }

        $done = ($q->max_num_pages <= $page);

        return [
            'items_removed' => $removed,
            'items_retained' => false,
            'messages' => [],
            'done' => $done,
        ];
    }
}
