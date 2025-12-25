<?php
/*
Plugin Name: Aetta Email Capture
Plugin URI: https://github.com/aetta/aetta-email-capture
Description: Simple, fast and lightweight email capture. No bloat.
Version: 1.0.1
Requires at least: 6.0
Requires PHP: 7.4
Author: aetta
Author URI: https://github.com/aetta
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: aetta-email-capture
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('AETTAEC_VERSION', '1.0.1');
define('AETTAEC_PLUGIN_FILE', __FILE__);
define('AETTAEC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AETTAEC_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once AETTAEC_PLUGIN_DIR . 'includes/class-aettaec-plugin.php';

register_activation_hook(__FILE__, ['AETTAEC_Plugin', 'activate_plugin']);

add_action('plugins_loaded', function () {
    AETTAEC_Plugin::instance();
});
