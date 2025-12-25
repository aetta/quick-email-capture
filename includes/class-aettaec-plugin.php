<?php
if (!defined('ABSPATH')) exit;

final class AETTAEC_Plugin
{
    private static $plugin_instance = null;

    public static function instance()
    {
        if (self::$plugin_instance === null) self::$plugin_instance = new self();
        return self::$plugin_instance;
    }

    private function __construct()
    {
        require_once AETTAEC_PLUGIN_DIR . 'includes/class-aettaec-cpt.php';
        require_once AETTAEC_PLUGIN_DIR . 'includes/class-aettaec-admin.php';
        require_once AETTAEC_PLUGIN_DIR . 'includes/class-aettaec-form.php';

        AETTAEC_CPT::init();
        AETTAEC_Form::init();

        if (is_admin()) AETTAEC_Admin::init();
    }

    public static function activate_plugin()
    {
        require_once AETTAEC_PLUGIN_DIR . 'includes/class-aettaec-cpt.php';
        AETTAEC_CPT::register_post_type();
        flush_rewrite_rules();

        if (!wp_next_scheduled('aettaec_daily_purge_event')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'aettaec_daily_purge_event');
        }
    }

    public static function get_plugin_options()
    {
        $default_settings = [
            'retention_days' => 30,
            'min_submit_seconds' => 3,
            'use_css' => 1,
            'consent_required' => 1,
            'consent_label' => __('I agree to receive emails.', 'aetta-email-capture'),
            'success_message' => __('Success! Subscription complete.', 'aetta-email-capture'),
            'error_invalid' => __('Invalid submission.', 'aetta-email-capture'),
            'error_wait' => __('Please wait a moment.', 'aetta-email-capture'),
            'error_required' => __('Fill in valid name and email.', 'aetta-email-capture'),
            'error_consent' => __('You must accept the terms.', 'aetta-email-capture'),
            'button_label' => __('Subscribe', 'aetta-email-capture'),
            'name_label' => __('Name', 'aetta-email-capture'),
            'email_label' => __('Email', 'aetta-email-capture'),
            'name_placeholder' => __('Your name', 'aetta-email-capture'),
            'email_placeholder' => __('you@example.com', 'aetta-email-capture'),
            'ui_border_color' => '#1d2327',
            'ui_border_width' => 1,
            'ui_radius' => 12,
            'ui_button_bg' => '#1d2327',
            'ui_button_text' => '#ffffff',
            'ui_success_border' => '#00a32a',
            'ui_error_border' => '#d63638',
            'ui_input_height' => 44,
        ];

        return array_merge($default_settings, get_option('aettaec_options', []));
    }
}
