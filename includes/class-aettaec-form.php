<?php
if (!defined('ABSPATH')) exit;

class AETTAEC_Form
{
    public static function init()
    {
        add_shortcode('aetta_email_capture', [__CLASS__, 'render_shortcode']);
        add_action('admin_post_aettaec_submit', [__CLASS__, 'process_form_submission']);
        add_action('admin_post_nopriv_aettaec_submit', [__CLASS__, 'process_form_submission']);
    }

    public static function process_form_submission()
    {
        if (ob_get_level()) ob_end_clean();

        $nonce_value = isset($_POST['aettaec_nonce']) ? sanitize_key(wp_unslash($_POST['aettaec_nonce'])) : '';
        if (!wp_verify_nonce($nonce_value, 'aettaec_submit')) {
            wp_die('Security check failed.');
        }

        $options = AETTAEC_Plugin::get_plugin_options();
        $redirect_url = wp_get_referer() ?: home_url('/');
        $redirect_url = remove_query_arg(['aetta_success', 'aetta_err'], $redirect_url);

        $submission_result = self::execute_submission_logic($options, $_POST);

        if ($submission_result === true) {
            self::execute_safe_redirect(add_query_arg('aetta_success', '1', $redirect_url));
        } else {
            self::execute_safe_redirect(add_query_arg('aetta_err', urlencode($submission_result), $redirect_url));
        }
    }

    private static function execute_safe_redirect($location)
    {
        if (!headers_sent()) {
            wp_safe_redirect($location);
        }
        echo "<script>window.location.href='" . esc_url($location) . "';</script>";
        exit;
    }

    public static function render_shortcode($attributes = [])
    {
        $is_success = isset($_GET['aetta_success']) ? true : false;
        $error_msg  = isset($_GET['aetta_err']) ? sanitize_text_field(wp_unslash($_GET['aetta_err'])) : '';

        $options = AETTAEC_Plugin::get_plugin_options();
        if ((int)$options['use_css'] === 1) {
            wp_enqueue_style('aettaec-form', AETTAEC_PLUGIN_URL . 'assets/css/form.css', [], AETTAEC_VERSION);
        }

        $css_variables = [
            '--aettaec-border-color:' . $options['ui_border_color'],
            '--aettaec-border-width:' . (int)$options['ui_border_width'] . 'px',
            '--aettaec-radius:' . (int)$options['ui_radius'] . 'px',
            '--aettaec-input-height:' . (int)$options['ui_input_height'] . 'px',
            '--aettaec-button-bg:' . $options['ui_button_bg'],
            '--aettaec-button-text:' . $options['ui_button_text'],
            '--aettaec-success-border:' . $options['ui_success_border'],
            '--aettaec-error-border:' . $options['ui_error_border']
        ];
        $style_attr = implode(';', $css_variables);

        $html = '';
        if ($is_success) {
            $html .= '<div class="aettaec-msg aettaec-success" style="' . esc_attr($style_attr) . '"><strong>' . esc_html__('Success!', 'aetta-email-capture') . '</strong> — ' . esc_html($options['success_message']) . '</div>';
        }
        if ($error_msg) {
            $msg = ($error_msg === 'invalid') ? $options['error_invalid'] : urldecode($error_msg);
            $html .= '<div class="aettaec-msg aettaec-error" style="' . esc_attr($style_attr) . '"><strong>' . esc_html__('Error:', 'aetta-email-capture') . '</strong> ' . esc_html($msg) . '</div>';
        }

        $html .= '<form class="aettaec-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="' . esc_attr($style_attr) . '">';
        $html .= wp_nonce_field('aettaec_submit', 'aettaec_nonce', true, false);
        $html .= '<input type="hidden" name="action" value="aettaec_submit">';

        $html .= '<label for="aettaec_name">' . esc_html($options['name_label']) . '</label>';
        $html .= '<input id="aettaec_name" name="aettaec_name" type="text" required placeholder="' . esc_attr($options['name_placeholder']) . '">';

        $html .= '<label for="aettaec_email">' . esc_html($options['email_label']) . '</label>';
        $html .= '<input id="aettaec_email" name="aettaec_email" type="email" required placeholder="' . esc_attr($options['email_placeholder']) . '">';

        if ((int)$options['consent_required'] === 1) {
            $html .= '<div class="aettaec-consent"><input id="aettaec_consent" name="aettaec_consent" type="checkbox" value="1"><label for="aettaec_consent">' . esc_html($options['consent_label']) . '</label></div>';
        }

        $html .= '<div style="display:none !important;"><input name="aettaec_hp" type="text" tabindex="-1" autocomplete="off"></div>';
        $html .= '<input type="hidden" name="aettaec_ts" value="' . time() . '">';
        $html .= '<button type="submit">' . esc_html($options['button_label']) . '</button></form>';

        return $html;
    }

    private static function execute_submission_logic($options, $data)
    {
        if (!empty($data['aettaec_hp'])) return $options['error_invalid'];

        $ts = isset($data['aettaec_ts']) ? absint($data['aettaec_ts']) : 0;
        if ((time() - $ts) < (int)$options['min_submit_seconds']) return $options['error_wait'];

        $name = isset($data['aettaec_name']) ? sanitize_text_field(wp_unslash($data['aettaec_name'])) : '';
        $email = isset($data['aettaec_email']) ? sanitize_email(wp_unslash($data['aettaec_email'])) : '';

        if ($name === '' || !is_email($email)) return $options['error_required'];
        if ((int)$options['consent_required'] === 1 && empty($data['aettaec_consent'])) return $options['error_consent'];

        $pid = wp_insert_post([
            'post_type' => AETTAEC_CPT::POST_TYPE,
            'post_status' => 'private',
            'post_title' => $email,
        ]);

        if (is_wp_error($pid)) return $options['error_invalid'];

        update_post_meta($pid, '_aettaec_name', $name);
        update_post_meta($pid, '_aettaec_email', $email);
        update_post_meta($pid, '_aettaec_consent', !empty($data['aettaec_consent']) ? 1 : 0);

        return true;
    }
}
