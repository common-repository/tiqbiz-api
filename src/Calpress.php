<?php

namespace Tiqbiz\Api;

use Calp_Event;

class Calpress extends Posts
{

    protected $post_type = 'calp_event';
    protected $notify_on_publish = false;

    public function __construct()
    {
        if (!class_exists('\Calp_Event')) {
            return;
        }

        parent::__construct();
    }

    public function syncPost($event_id, $additional_data = array())
    {
        if (get_post_type($event_id) != $this->post_type) {
            return;
        }

        if (!$this->validateSave($event_id)) {
            return;
        }

        try {
            $event = new Calp_Event($event_id);
        } catch (\Exception $e) {
            return;
        }

        $additional_data = array();

        $additional_data['post_type'] = 'calendar';

        $additional_data['start_date'] = $this->formatDateFromTime($event->start);
        $additional_data['end_date'] = $this->formatDateFromTime($event->end);
        $additional_data['all_day'] = (int)$event->allday;

        $additional_data['location_name'] = $event->venue;
        $additional_data['location_address'] = $event->address;

        if ($this->savePostNotification($event_id)) {
            $additional_data['notifications'] = array($this->formatDateFromTime($event->start - 60*60*24));
        }

        parent::syncPost($event_id, $additional_data);
    }

    public function renderPostNotificationMetaBox($post)
    {
        $send_notification = $this->getPostNotification($post->ID);

        $nonce_id = 'tiqbiz_api_nonce_update_post_send_notification';

        wp_nonce_field($nonce_id, $nonce_id, false);

        ?>
        <input type="hidden" name="tiqbiz_api_send_notification" value="">

        <ul>
            <li>
                <label>
                    <input type="checkbox" name="tiqbiz_api_send_notification"<?php echo $send_notification ? ' checked="checked"' : ''; ?>>
                    Send notification 24 hours before?
                </label>
            </li>
        </ul>
        <?php
    }

}
