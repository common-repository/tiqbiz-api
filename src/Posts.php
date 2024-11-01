<?php

namespace Tiqbiz\Api;

use League\HTMLToMarkdown\HtmlConverter;

use DateTime;
use DateTimeZone;

class Posts extends Api
{

    protected $post_type = 'post';
    protected $notify_on_publish = true;

    public function __construct()
    {
        parent::__construct();

        add_action('init', array($this, 'sessionStart'));
        add_action('wp_login', array($this, 'sessionDestroy'));
        add_action('wp_logout', array($this, 'sessionDestroy'));

        add_action('admin_notices', array($this, 'runQueue'));

        add_action('wp_ajax_tiqbiz_api_sync', array($this, 'syncProxy'));
        add_action('wp_ajax_tiqbiz_api_post_id_callback', array($this, 'updatePostTiqbizId'));

        add_action('add_meta_boxes', array($this, 'addPostBoxesMetaBox'));
        add_action('add_meta_boxes', array($this, 'addNotificationMetaBox'));
        add_action('save_post', array($this, 'syncPost'));
    }

    public function sessionStart()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public function sessionDestroy()
    {
        session_destroy();
    }

    public function runQueue()
    {
        $queue = $this->getQueue();

        if (!$queue) {
            return;
        }

        wp_localize_script('tiqbiz-api-script', 'tiqbiz_api_data', array(
            'timeout' => $this->timeout,
            'queue' => array_values($queue)
        ));

        ?>

        <div class="update-nag updated in-progress" id="tiqbiz_api_sync_progress">
            <img src="<?php echo plugin_dir_url(TIQBIZ_API_PLUGIN_PATH) . 'assets/img/logo.png'; ?>" alt="tiqbiz">
            <p class="in-progress-message">
                <span class="spinner"></span>
                <em>Please wait while we sync updates with your Tiqbiz account...</em>
            </p>
            <p class="in-progress-message">
                <strong>Don't close or navigate away from this page until the process has completed.</strong>
            </p>
            <ol></ol>
        </div>

        <div class="update-nag updated" id="tiqbiz_api_sync_success">
            <p>All updates successfully synced with Tiqbiz.</p>
        </div>

        <?php
    }

    public function syncProxy()
    {
        $path = isset($_REQUEST['path']) ? $_REQUEST['path'] : '';
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : '';
        $payload = isset($_REQUEST['payload']) ? $_REQUEST['payload'] : '';

        try {
            $response = $this->apiRequest($path, $method, $payload);
        } catch (\Exception $e) {
            $response = array(
                'success' => false,
                'error_message' => $e->getMessage()
            );
        }

        $this->jsonHeader();

        exit(json_encode($response));
    }

    public function updatePostTiqbizId()
    {
        $return = function($success, $error_message = '') {
            $this->jsonHeader();

            exit(json_encode(array(
                'success' => $success,
                'error_message' => $error_message
            )));
        };

        if (!check_ajax_referer('tiqbiz_api_nonce_update_post_tiqbiz_id', 'nonce', false)) {
            $return(false, 'Invalid nonce');
        }

        if (!isset($_POST['wordpress_post_id'])) {
            $return(false, 'Missing \'wordpress_post_id\' param');
        }

        $post_id = $_POST['wordpress_post_id'];

        if (!$this->validateSave($post_id)) {
            $return(false, 'Invalid user permissions');
        }

        if (!isset($_POST['tiqbiz_api_post_id'])) {
            $return(false, 'Missing \'tiqbiz_api_post_id\' param');
        }

        $tiqbiz_api_post_id = $_POST['tiqbiz_api_post_id'];

        update_post_meta($post_id, '_tiqbiz_api_post_id', $tiqbiz_api_post_id);

        $return(true);
    }

    public function addPostBoxesMetaBox()
    {
        if (!$this->business_id || !$this->boxes) {
            return;
        }

        add_meta_box(
            'tiqbiz-api-boxes-metabox',
            'Tiqbiz Boxes',
            array($this, 'renderPostBoxesMetaBox'),
            $this->post_type,
            'side',
            'high'
        );
    }

    public function addNotificationMetaBox()
    {
        if (!$this->business_id || !$this->boxes) {
            return;
        }

        add_meta_box(
            'tiqbiz-api-notification-metabox',
            'Tiqbiz Notification',
            array($this, 'renderPostNotificationMetaBox'),
            $this->post_type,
            'side',
            'high'
        );
    }

    public function renderPostBoxesMetaBox($post)
    {
        $checked_boxes = $this->getPostBoxes($post->ID);

        $nonce_id = 'tiqbiz_api_nonce_update_post_boxes';

        wp_nonce_field($nonce_id, $nonce_id, false);

        ?>
        <input type="hidden" name="tiqbiz_api_box" value="">

        <ul>
            <li>
                <label>
                    <input type="checkbox"
                        onclick="jQuery('#tiqbiz-api-boxes-metabox input[type=\'checkbox\']').prop('checked', jQuery(this).is(':checked'));">
                    <strong>Select all</strong>
                </label>
            </li>

            <?php

            foreach ($this->boxes as $box) {
                $checked_markup = '';

                if (in_array($box['id'], $checked_boxes)) {
                    $checked_markup = ' checked="checked"';
                }

                ?>
                <li>
                    <label>
                        <input type="checkbox" name="tiqbiz_api_box[]" value="<?php echo $box['id']; ?>"<?php echo $checked_markup; ?>>
                        <?php
                            if ($box['group']) {
                                echo '[', $box['group'], '] ';
                            }

                            echo $box['name'];
                        ?>
                    </label>
                </li>
                <?php
            }

            ?>
        </ul>
        <?php
    }

    public function syncPost($post_id, $additional_data = array())
    {
        if (!$this->validateSave($post_id)) {
            return;
        }

        $this->savePostBoxes($post_id);
        $notification = $this->savePostNotification($post_id);

        $post = get_post($post_id);

        if ($post->post_type != $this->post_type) {
            return;
        }

        $tiqbiz_api_post_id = (int)get_post_meta($post_id, '_tiqbiz_api_post_id', true);

        $post_data = array('title' => $post->post_title);

        $boxes = implode(',', $this->getPostBoxes($post_id));

        $converter = new HtmlConverter(array(
            'strip_tags' => true,
            'remove_nodes' => 'img'
        ));

        $excerpt = apply_filters('the_excerpt', $post->post_excerpt);
        $content = apply_filters('the_content', $post->post_content);

        $body = preg_replace('/\x{00a0}/siu', ' ', $excerpt ?: $content);

        if (strpos($body, '<img') !== false || $excerpt) {
            $permalink = get_permalink($post);
            $body .= "\n" . '<p>View the full post with images <a href="' . $permalink . '">here</a>.</p>';
        }

        if ($boxes && in_array($post->post_status, array('publish', 'future'))) {
            $post_data['post_type'] = 'newsfeed';
            $post_data['body_markdown'] = $converter->convert($body);
            $post_data['boxes'] = $boxes;
            $post_data['published_at'] = get_post_time('Y-m-d H:i:s', false, $post);

            if ($this->notify_on_publish) {
                $post_data['notifications'] = array($post_data['published_at']);
            }

            $post_data = array_merge($post_data, $additional_data);

            if ($tiqbiz_api_post_id) {
                $this->queuePush($post_id, 'businesses/' . $this->business_id . '/posts/' . $tiqbiz_api_post_id, 'PUT', $post_data);
            } else {
                $this->queuePush($post_id, 'businesses/' . $this->business_id . '/posts', 'POST', $post_data);
            }
        } else if ($tiqbiz_api_post_id) {
            $this->queuePush($post_id, 'businesses/' . $this->business_id . '/posts/' . $tiqbiz_api_post_id, 'DELETE', $post_data);
        }
    }

    public function renderPostNotificationMetaBox($post)
    {
        ?>

        <ul>
            <li>
                <label>
                    A notification will automatically be sent when your post is published.
                </label>
            </li>
        </ul>
        <?php
    }

    protected function validateSave($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        if (wp_is_post_revision($post_id)) {
            return false;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return false;
        }

        if (get_post_status($post_id) == 'auto-draft') {
            return false;
        }

        return true;
    }

    protected function savePostNotification($post_id)
    {
        if (!isset($_POST['tiqbiz_api_send_notification'])) {
            return;
        }

        $nonce_id = 'tiqbiz_api_nonce_update_post_send_notification';

        if (!isset($_POST[$nonce_id])) {
            return;
        }

        $nonce = $_POST[$nonce_id];

        if (!wp_verify_nonce($nonce, $nonce_id)) {
            return;
        }

        $send_notification = (bool)$_POST['tiqbiz_api_send_notification'];

        update_post_meta($post_id, '_tiqbiz_api_send_notification', $send_notification);

        return $send_notification;
    }

    protected function getPostNotification($post_id)
    {
        global $pagenow;

        if ($pagenow == 'post-new.php') {
            return true;
        }

        return (bool)get_post_meta($post_id, '_tiqbiz_api_send_notification', true);
    }

    protected function savePostBoxes($post_id)
    {
        if (!isset($_POST['tiqbiz_api_box'])) {
            return;
        }

        $nonce_id = 'tiqbiz_api_nonce_update_post_boxes';

        if (!isset($_POST[$nonce_id])) {
            return;
        }

        $nonce = $_POST[$nonce_id];

        if (!wp_verify_nonce($nonce, $nonce_id)) {
            return;
        }

        $boxes = array_filter((array)$_POST['tiqbiz_api_box']);

        update_post_meta($post_id, '_tiqbiz_api_boxes', wp_slash(json_encode($boxes)));
    }

    protected function getPostBoxes($post_id)
    {
        return (array)json_decode(get_post_meta($post_id, '_tiqbiz_api_boxes', true));
    }

    protected function queuePush($post_id, $path, $method, $payload)
    {
        if (!isset($_SESSION['tiqbiz_api_queue'])) {
            $_SESSION['tiqbiz_api_queue'] = array();
        }

        $nonce = wp_create_nonce('tiqbiz_api_nonce_update_post_tiqbiz_id');

        $_SESSION['tiqbiz_api_queue'][$post_id] = array(
            'wordpress_post_id' => $post_id,
            'path' => $path,
            'method' => $method,
            'payload' => $payload,
            'nonce' => $nonce
        );
    }

    private function getQueue()
    {
        if (
            isset($_SESSION['tiqbiz_api_queue']) &&
            is_array($_SESSION['tiqbiz_api_queue'])
        ) {
            $queue = $_SESSION['tiqbiz_api_queue'];

            $_SESSION['tiqbiz_api_queue'] = array();

            return $queue;
        } else {
            return array();
        }
    }

    protected function formatDateFromTime($time, $set_timezone = true)
    {
        $timezone = get_option('timezone_string');

        $date = new DateTime('@' . $time);

        if ($set_timezone && $timezone) {
            $date->setTimezone(new DateTimeZone($timezone));
        }

        return $date->format('Y-m-d H:i:s');
    }

    private function jsonHeader()
    {
        header('Content-Type: application/json');
    }

}
