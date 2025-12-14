<?php
if (!defined('ABSPATH')) exit;

final class QEC_Plugin
{
    private static $instance = null;

    public static function instance()
    {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }

    private function __construct()
    {
        require_once QEC_PLUGIN_DIR . 'includes/class-qec-cpt.php';
        require_once QEC_PLUGIN_DIR . 'includes/class-qec-admin.php';
        require_once QEC_PLUGIN_DIR . 'includes/class-qec-form.php';

        QEC_CPT::init();
        QEC_Form::init();

        if (is_admin()) QEC_Admin::init();
    }

    public static function activate()
    {
        require_once QEC_PLUGIN_DIR . 'includes/class-qec-cpt.php';

        QEC_CPT::register();
        flush_rewrite_rules();

        if (!wp_next_scheduled('qec_daily_purge')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'qec_daily_purge');
        }

        if (get_option('qec_options', null) === null) {
            add_option('qec_options', self::default_options());
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('qec_daily_purge');
        flush_rewrite_rules();
    }

    public static function default_options()
    {
        return [
            'retention_days' => 365,
            'rate_limit_seconds' => 30,
            'min_submit_seconds' => 4,
            'store_ip' => 0,
            'store_user_agent' => 0,
            'consent_required' => 1,
            'use_css' => 1,
            'wrapper_class' => '',
            'input_class' => '',
            'button_class' => '',
            'message_class' => '',
            'consent_label' => __('I agree to receive emails. You can unsubscribe anytime.', 'quick-email-capture'),
            'success_message' => __('Thanks! You have been subscribed.', 'quick-email-capture'),
            'error_invalid' => __('Invalid submission.', 'quick-email-capture'),
            'error_wait' => __('Please wait a moment before submitting.', 'quick-email-capture'),
            'error_required' => __('Please provide a valid name and email.', 'quick-email-capture'),
            'error_consent' => __('Please agree to receive emails.', 'quick-email-capture'),
            'button_label' => __('Subscribe', 'quick-email-capture'),
            'name_label' => __('Name', 'quick-email-capture'),
            'email_label' => __('Email', 'quick-email-capture'),
            'name_placeholder' => __('Your name', 'quick-email-capture'),
            'email_placeholder' => __('you@example.com', 'quick-email-capture'),

            'ui_border_color' => '#1d2327',
            'ui_border_width' => 1,
            'ui_radius' => 12,
            'ui_button_bg' => '#1d2327',
            'ui_button_text' => '#ffffff',
            'ui_success_border' => '#00a32a',
            'ui_error_border' => '#d63638',
            'ui_input_height' => 44,
        ];
    }

    public static function opts()
    {
        $defaults = self::default_options();
        $o = get_option('qec_options', []);
        return wp_parse_args(is_array($o) ? $o : [], $defaults);
    }
}
