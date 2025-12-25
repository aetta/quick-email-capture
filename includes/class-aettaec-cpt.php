<?php
if (!defined('ABSPATH')) exit;

class AETTAEC_CPT
{
    const POST_TYPE = 'aettaec_signup';

    public static function init()
    {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('aettaec_daily_purge_event', [__CLASS__, 'execute_daily_purge']);
    }

    public static function register_post_type()
    {
        $labels = [
            'name' => __('Aetta Email Capture', 'aetta-email-capture'),
            'singular_name' => __('Signup', 'aetta-email-capture'),
            'menu_name' => __('Aetta Email Capture', 'aetta-email-capture'),
            'add_new_item' => __('Add Signup', 'aetta-email-capture'),
            'edit_item' => __('View Signup', 'aetta-email-capture'),
        ];

        register_post_type(self::POST_TYPE, [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-email',
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ]);
    }

    public static function execute_daily_purge()
    {
        $options = AETTAEC_Plugin::get_plugin_options();
        $retention_days = max(1, (int)$options['retention_days']);
        $time_threshold = time() - ($retention_days * DAY_IN_SECONDS);

        $expired_entries = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'date_query' => [
                [
                    'before' => gmdate('Y-m-d', $time_threshold),
                    'inclusive' => true,
                ],
            ],
        ]);

        if ($expired_entries->posts) {
            foreach ($expired_entries->posts as $entry_id) {
                wp_delete_post($entry_id, true);
            }
        }
    }
}
