<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

$aettaec_signups_to_delete = get_posts([
    'post_type' => 'aettaec_signup',
    'post_status' => 'any',
    'numberposts' => -1,
    'fields' => 'ids',
]);

foreach ($aettaec_signups_to_delete as $aettaec_target_id) {
    wp_delete_post($aettaec_target_id, true);
}

delete_option('aettaec_options');
wp_clear_scheduled_hook('aettaec_daily_purge_event');
