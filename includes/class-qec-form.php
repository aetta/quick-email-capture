<?php
if (!defined('ABSPATH')) exit;

class QEC_Form
{
    public static function init()
    {
        add_shortcode('quick_email_capture', [__CLASS__, 'shortcode']);
        add_action('admin_post_qec_submit', [__CLASS__, 'handle_post']);
        add_action('admin_post_nopriv_qec_submit', [__CLASS__, 'handle_post']);
    }

    public static function handle_post()
    {
        $opts = QEC_Plugin::opts();

        $redirect_to = isset($_POST['qec_redirect_to'])
            ? esc_url_raw(wp_unslash($_POST['qec_redirect_to']))
            : home_url('/');

        if ($redirect_to === '') $redirect_to = home_url('/');

        $err = self::handle_submit($opts);

        $url = remove_query_arg(['qec_thanks', 'qec_err'], $redirect_to);

        if ($err === '') {
            wp_safe_redirect(add_query_arg('qec_thanks', '1', $url));
            exit;
        }

        wp_safe_redirect(add_query_arg('qec_err', rawurlencode($err), $url));
        exit;
    }

    public static function shortcode($atts = [])
    {
        $opts = QEC_Plugin::opts();

        if ((int)$opts['use_css'] === 1) {
            wp_enqueue_style('qec-form', QEC_PLUGIN_URL . 'assets/css/form.css', [], QEC_VERSION);
        }

        $thanks = (isset($_GET['qec_thanks']) && $_GET['qec_thanks'] === '1');
        $err = '';
        if (isset($_GET['qec_err'])) $err = sanitize_text_field(wp_unslash($_GET['qec_err']));

        $wrapper_extra = trim((string)$opts['wrapper_class']);
        $input_extra = trim((string)$opts['input_class']);
        $button_extra = trim((string)$opts['button_class']);
        $msg_extra = trim((string)$opts['message_class']);

        $style_vars = [];
        $style_vars[] = '--qec-border-color:' . (string)$opts['ui_border_color'];
        $style_vars[] = '--qec-border-width:' . (int)$opts['ui_border_width'] . 'px';
        $style_vars[] = '--qec-radius:' . (int)$opts['ui_radius'] . 'px';
        $style_vars[] = '--qec-input-height:' . (int)$opts['ui_input_height'] . 'px';
        $style_vars[] = '--qec-button-bg:' . (string)$opts['ui_button_bg'];
        $style_vars[] = '--qec-button-text:' . (string)$opts['ui_button_text'];
        $style_vars[] = '--qec-success-border:' . (string)$opts['ui_success_border'];
        $style_vars[] = '--qec-error-border:' . (string)$opts['ui_error_border'];
        $style_attr = implode(';', $style_vars);

        $html = '';

        if ($thanks) {
            $html .= '<div class="qec-msg qec-success' . ($msg_extra ? ' ' . esc_attr($msg_extra) : '') . '" style="' . esc_attr($style_attr) . '"><strong>' . esc_html__('Success', 'quick-email-capture') . '</strong> — ' . esc_html($opts['success_message']) . '</div>';
        }

        if ($err !== '') {
            $html .= '<div class="qec-msg qec-error' . ($msg_extra ? ' ' . esc_attr($msg_extra) : '') . '" style="' . esc_attr($style_attr) . '"><strong>' . esc_html__('Error', 'quick-email-capture') . ':</strong> ' . esc_html($err) . '</div>';
        }

        $action = esc_url(admin_url('admin-post.php'));
        $redirect_to = wp_get_referer() ?: self::current_url();

        $html .= '<form class="qec-form' . ($wrapper_extra ? ' ' . esc_attr($wrapper_extra) : '') . '" method="post" action="' . $action . '" novalidate style="' . esc_attr($style_attr) . '">';
        $html .= wp_nonce_field('qec_submit', 'qec_nonce', true, false);

        $html .= '<input type="hidden" name="action" value="qec_submit">';
        $html .= '<input type="hidden" name="qec_redirect_to" value="' . esc_attr($redirect_to) . '">';

        $html .= '<label for="qec_name">' . esc_html($opts['name_label']) . '</label>';
        $html .= '<input class="' . esc_attr($input_extra) . '" id="qec_name" name="qec_name" type="text" autocomplete="name" maxlength="150" required aria-required="true" placeholder="' . esc_attr($opts['name_placeholder']) . '">';

        $html .= '<label for="qec_email">' . esc_html($opts['email_label']) . '</label>';
        $html .= '<input class="' . esc_attr($input_extra) . '" id="qec_email" name="qec_email" type="email" autocomplete="email" maxlength="191" required aria-required="true" placeholder="' . esc_attr($opts['email_placeholder']) . '">';

        if ((int)$opts['consent_required'] === 1) {
            $html .= '<div class="qec-consent">';
            $html .= '<input id="qec_consent" name="qec_consent" type="checkbox" value="1" required aria-required="true">';
            $html .= '<label for="qec_consent">' . esc_html($opts['consent_label']) . '</label>';
            $html .= '</div>';
        } else {
            $html .= '<input type="hidden" name="qec_consent" value="1">';
        }

        $html .= '<div class="qec-hp" aria-hidden="true">';
        $html .= '<label for="qec_hp">' . esc_html__('Leave this field empty', 'quick-email-capture') . '</label>';
        $html .= '<input id="qec_hp" name="qec_hp" type="text" autocomplete="off">';
        $html .= '</div>';

        $html .= '<input type="hidden" name="qec_ts" value="' . esc_attr(time()) . '">';
        $html .= '<button class="' . esc_attr($button_extra) . '" type="submit">' . esc_html($opts['button_label']) . '</button>';
        $html .= '</form>';

        return $html;
    }

    private static function handle_submit($opts)
    {
        $nonce = isset($_POST['qec_nonce']) ? sanitize_text_field(wp_unslash($_POST['qec_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'qec_submit')) return $opts['error_invalid'];

        $hp = isset($_POST['qec_hp']) ? trim((string)wp_unslash($_POST['qec_hp'])) : '';
        if ($hp !== '') return $opts['error_invalid'];

        $ts = isset($_POST['qec_ts']) ? (int)wp_unslash($_POST['qec_ts']) : 0;
        $min = max(0, (int)$opts['min_submit_seconds']);
        if ($min > 0 && (time() - $ts) < $min) return $opts['error_wait'];

        $name = isset($_POST['qec_name']) ? sanitize_text_field(wp_unslash($_POST['qec_name'])) : '';
        $email = isset($_POST['qec_email']) ? sanitize_email(wp_unslash($_POST['qec_email'])) : '';
        $consent = (isset($_POST['qec_consent']) && wp_unslash($_POST['qec_consent']) === '1');

        if ($name === '' || !is_email($email)) return $opts['error_required'];
        if ((int)$opts['consent_required'] === 1 && !$consent) return $opts['error_consent'];

        $ip = self::ip();
        $rate_seconds = max(0, (int)$opts['rate_limit_seconds']);
        if ($rate_seconds > 0) {
            $rate_key = 'qec_rate_' . md5(($ip !== '' ? $ip : '') . '|' . $email);
            if (get_transient($rate_key)) return $opts['error_wait'];
            set_transient($rate_key, 1, $rate_seconds);
        }

        $existing = get_posts([
            'post_type' => QEC_CPT::POST_TYPE,
            'post_status' => 'any',
            'meta_key' => '_qec_email',
            'meta_value' => $email,
            'fields' => 'ids',
            'posts_per_page' => 1,
        ]);

        if ($existing) return '';

        $now_gmt = current_time('mysql', true);

        $pid = wp_insert_post([
            'post_type' => QEC_CPT::POST_TYPE,
            'post_status' => 'private',
            'post_title' => $email,
            'post_date_gmt' => $now_gmt,
            'post_date' => get_date_from_gmt($now_gmt),
        ], true);

        if (is_wp_error($pid)) return $opts['error_invalid'];

        update_post_meta($pid, '_qec_name', $name);
        update_post_meta($pid, '_qec_email', $email);
        update_post_meta($pid, '_qec_consent', $consent ? 1 : 0);
        update_post_meta($pid, '_qec_created_gmt', $now_gmt);
        update_post_meta($pid, '_qec_source_url', self::current_url());

        $ref = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';
        update_post_meta($pid, '_qec_source_ref', $ref);

        if ((int)$opts['store_ip'] === 1) update_post_meta($pid, '_qec_ip', $ip);

        if ((int)$opts['store_user_agent'] === 1 && !empty($_SERVER['HTTP_USER_AGENT'])) {
            $ua = sanitize_text_field(substr((string)wp_unslash($_SERVER['HTTP_USER_AGENT']), 0, 255));
            update_post_meta($pid, '_qec_ua', $ua);
        }

        return '';
    }

    private static function current_url()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string)wp_unslash($_SERVER['REQUEST_URI']) : '/';
        $uri = preg_replace('/[\r\n]/', '', $uri);
        $uri = '/' . ltrim($uri, '/');
        return esc_url_raw(home_url($uri));
    }

    private static function ip()
    {
        $ip = !empty($_SERVER['REMOTE_ADDR']) ? (string)wp_unslash($_SERVER['REMOTE_ADDR']) : '';
        return sanitize_text_field($ip);
    }
}
