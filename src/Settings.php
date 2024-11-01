<?php

namespace Tiqbiz\Api;

class Settings extends Api
{

    public function __construct()
    {
        parent::__construct();

        add_action('admin_menu', array($this, 'settingsPage'));
        add_action('admin_init', array($this, 'settingsInit'));

        add_filter('plugin_action_links_' . TIQBIZ_API_PLUGIN_BASE, array($this, 'settingsLink'));

    }

    public function settingsPage()
    {
        add_options_page(
            'Tiqbiz API Settings',
            'Tiqbiz API Settings',
            'manage_options',
            'tiqbiz-api-settings',
            array($this, 'renderSettingsPage')
        );
    }

    public function settingsLink($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=tiqbiz-api-settings') . '">Settings</a>';

        array_unshift($links, $settings_link);

        return $links;
    }

    public function renderSettingsPage()
    {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>

            <h2>Tiqbiz API Settings</h2>

            <form method="post" action="options.php" id="tiqbiz_api_settings">

            <?php
                settings_fields('tiqbiz_api_settings_group');
                do_settings_sections('tiqbiz-api-settings');
                submit_button('Update');
            ?>

                <dl>
                    <dt>Wordpress Version</dt>
                    <dd><?php echo get_bloginfo('version'); ?></dd>
                    <dt>Plugin Version</dt>
                    <dd><?php echo $this->getPluginVersion(); ?></dd>
                    <dt>EventON Version</dt>
                    <dd><?php echo $this->getPluginVersion(TIQBIZ_API_EVENT_PLUGIN_EVENTON); ?></dd>
                    <dt>CalPress Version</dt>
                    <dd><?php echo $this->getPluginVersion(TIQBIZ_API_EVENT_PLUGIN_CALPRESS); ?></dd>
                    <dt>CalPress Pro Version</dt>
                    <dd><?php echo $this->getPluginVersion(TIQBIZ_API_EVENT_PLUGIN_CALPRESS_PRO); ?></dd>
                    <dt>PHP Version</dt>
                    <dd><?php echo phpversion(); ?></dd>
                </dl>
            </form>
        </div>
        <?php
    }

    public function settingsInit()
    {
        register_setting(
            'tiqbiz_api_settings_group',
            'tiqbiz_api_settings',
            array($this, 'sanitize')
        );

        add_settings_section(
            'api_settings',
            'API Settings',
            array($this, 'settingsPreamble'),
            'tiqbiz-api-settings'
        );

        add_settings_field(
            'email',
            'Email',
            array($this, 'emailField'),
            'tiqbiz-api-settings',
            'api_settings'
        );

        add_settings_field(
            'password',
            'Password',
            array($this, 'passwordField'),
            'tiqbiz-api-settings',
            'api_settings'
        );

        add_settings_field(
            'boxes',
            'Synced Tiqbiz Boxes',
            array($this, 'boxesField'),
            'tiqbiz-api-settings',
            'api_settings'
        );

        add_settings_field(
            'timeout',
            'API Request Timeout',
            array($this, 'timeoutField'),
            'tiqbiz-api-settings',
            'api_settings'
        );
    }

    public function sanitize($input)
    {
        delete_option('tiqbiz_api_token');

        $clean_input = array();

        if (isset($input['email'])) {
            $clean_input['email'] = sanitize_text_field($input['email']);

            $this->email = $clean_input['email'];
        }

        if (isset($input['password'])) {
            $clean_input['password'] = sanitize_text_field($input['password']);

            $this->password = $clean_input['password'];
        }

        if (isset($input['timeout'])) {
            $clean_input['timeout'] = absint($input['timeout']);

            $this->timeout = $clean_input['timeout'];
        }

        try {
            $clean_input['business_id'] = $this->getBusiness()->id;
            $clean_input['boxes'] = $this->getBoxes();
        } catch (\Exception $e) {
            $clean_input['business_id'] = null;
            $clean_input['boxes'] = array();
        }

        // clean up pre-v6 API meta values
        delete_post_meta_by_key('_tiqbiz_api_id');

        return $clean_input;
    }

    public function settingsPreamble()
    {
        $this->checkSettings();

        echo 'Enter your settings below - these will be provided by the Tiqbiz team.';
    }

    public function emailField()
    {
        echo sprintf(
            '<input type="email" id="email" name="tiqbiz_api_settings[email]" value="%s" size="50">',
            esc_attr($this->email)
        );
    }

    public function passwordField()
    {
        echo sprintf(
            '<input type="text" id="password" name="tiqbiz_api_settings[password]" value="%s" size="50">',
            esc_attr($this->password)
        );
    }

    public function boxesField() {
        if ($this->boxes) {
            echo '<ul id="boxes">';

            foreach ($this->boxes as $box) {
                echo '<li>';

                if ($box['group']) {
                    echo '[', $box['group'], '] ';
                }

                echo $box['name'];

                if ($box['description']) {
                    echo ' - ', $box['description'];
                }

                echo '</li>';
            }

            echo '</ul>';
        } else {
            echo 'None, yet.';
        }
    }

    public function timeoutField()
    {
        echo sprintf(
            '<input type="number" id="timeout" name="tiqbiz_api_settings[timeout]" value="%s" max="100"> seconds' .
            '<p><em>Please don\'t change this unless directed to do so by the Tiqbiz team</em></p>',
            esc_attr($this->timeout)
        );
    }

    private function checkSettings()
    {
        if (!$this->email || !$this->password) {
            return;
        }

        try {
            $name = $this->getBusiness()->name;
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        if (isset($name)) {
            ?>
            <div class="updated">
                <p>Tiqbiz API plugin set up correctly for <?php echo $name; ?>.</p>
            </div>
            <?php
        } else {
            ?>
            <div class="error">
                <p>There seems to be a problem with the email or password (or with communicating with the Tiqbiz server).</p>
            <?php

            if (isset($error)) {
                ?>
                <p class="dampen">
                    Error message (this may help the Tiqbiz team solve technical issues):<br>
                    <em><?php echo $error; ?></em>
                </p>
                <?php
            }

            ?>
                </div>
            <?php
        }
    }

}
