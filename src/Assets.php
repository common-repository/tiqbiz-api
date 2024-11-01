<?php

namespace Tiqbiz\Api;

class Assets extends Api
{

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'addStylesheets'));
        add_action('admin_enqueue_scripts', array($this, 'addScripts'));

        add_action('eventon_duplicate_product', array($this, 'removePostMeta'));
        add_action('dp_duplicate_page', array($this, 'removePostMeta'));
        add_action('dp_duplicate_post', array($this, 'removePostMeta'));
    }

    public function addStylesheets()
    {
        wp_register_style('tiqbiz-api-stylesheet', plugin_dir_url(TIQBIZ_API_PLUGIN_PATH) . 'assets/css/style.css', array(), $this->getPluginVersion());
        wp_enqueue_style('tiqbiz-api-stylesheet');
    }

    public function addScripts()
    {
        wp_register_script('tiqbiz-api-script', plugin_dir_url(TIQBIZ_API_PLUGIN_PATH) . 'assets/js/script.js', array(), $this->getPluginVersion(), true);
        wp_enqueue_script('tiqbiz-api-script');
    }

    public function removePostMeta($post_id)
    {
        delete_post_meta($post_id, '_tiqbiz_api_post_id');
    }

}
