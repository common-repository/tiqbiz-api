<?php

/**
 * Plugin Name: Tiqbiz API
 * Plugin URI: http://www.tiqbiz.com/
 * Description: Integrates your WordPress site with the Tiqbiz API
 * Version: 2.0.10
 * Author: Tiqbiz
 * Author URI: http://www.tiqbiz.com/
 * License: CC BY-SA 4.0
 */

defined('ABSPATH') or exit(1);

if (!is_admin()) {
    return;
}

define('TIQBIZ_API_PLUGIN_PATH', __FILE__);
define('TIQBIZ_API_PLUGIN_BASE', plugin_basename(TIQBIZ_API_PLUGIN_PATH));

define('TIQBIZ_API_EVENT_PLUGIN_EVENTON', 'eventON/eventon.php');
define('TIQBIZ_API_EVENT_PLUGIN_CALPRESS', 'calpress-event-calendar/calpress.php');
define('TIQBIZ_API_EVENT_PLUGIN_CALPRESS_PRO', 'calpress-pro/calpress-pro.php');

spl_autoload_register(function($class) {
    if (strpos($class, 'Tiqbiz\Api') === 0) {
        $class_segments = explode('\\', $class);
        $include = array_pop($class_segments) . '.php';

        require_once plugin_dir_path(TIQBIZ_API_PLUGIN_PATH) . 'src/' . $include;
    }

    if (strpos($class, 'League\HTMLToMarkdown') === 0) {
        $class_segments = array_slice(explode('\\', $class), 2);
        $include = implode('/', $class_segments) . '.php';

        require_once plugin_dir_path(TIQBIZ_API_PLUGIN_PATH) . 'src/html-to-markdown/src/' . $include;
    }
});

use Tiqbiz\Api\Assets;
use Tiqbiz\Api\Posts;
use Tiqbiz\Api\Calpress;
use Tiqbiz\Api\Eventon;
use Tiqbiz\Api\Settings;

new Assets();
new Posts();
new Calpress();
new Eventon();
new Settings();
