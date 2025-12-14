<?php
if (!defined('ABSPATH')) exit;

class QEC_CPT
{
    const POST_TYPE = 'qec_signup';

    public static function init()
    {
        add_action('init', [__CLASS__, 'register']);
        add_action('qec_daily_purge', [__CLASS__, 'run_purge']);
    }

    public static function register()
    {
        $labels = [
            'name' => __('Quick Email Capture', 'quick-email-capture'),
            'singular_name' => __('Signup', 'quick-email-capture'),
            'menu_name' => __('Quick Email Capture', 'quick-email-capture'),
            'add_new_item' => __('Add Signup', 'quick-email-capture'),
            'edit_item' => __('View Signup', 'quick-email-capture'),
        ];

        register_post_type(self::POST_TYPE, [
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'exclude_from_search' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-email',
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'show_in_rest' => false,
        ]);
    }

    public static function run_purge()
    {
        $opts = QEC_Plugin::opts();
        $days = max(1, (int)$opts['retention_days']);
        $threshold = time() - ($days * DAY_IN_SECONDS);

        $q = new WP_Query([
            'post_type' => self::POST_TYPE,
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'date_query' => [[
                'column' => 'post_date_gmt',
                'before' => gmdate('Y-m-d H:i:s', $threshold),
            ]],
        ]);

        foreach ($q->posts as $pid) {
            wp_delete_post($pid, true);
        }
    }
}
