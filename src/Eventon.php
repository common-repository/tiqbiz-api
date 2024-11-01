<?php

namespace Tiqbiz\Api;

class Eventon extends Posts
{

    protected $post_type = 'ajde_events';
    protected $notify_on_publish = false;

    public function __construct()
    {
        if (!class_exists('\EventON')) {
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

        global $eventon;

        $event = array_pop(($eventon->evo_generator->get_single_event_data($event_id)));

        if (!$event['srow']) {
            return;
        }

        $event_meta = get_post_meta($event_id);

        $event_meta_fields = array(
            'allday',
            'location_name',
            'location',
            'lat',
            'lon'
        );

        foreach ($event_meta_fields as $field) {
            $field_key = 'evcal_' . $field;

            if (isset($event_meta[$field_key]) && is_array($event_meta[$field_key])) {
                $event[$field] = array_shift($event_meta[$field_key]);
            } else {
                $event[$field] = null;
            }
        }

        $additional_data = array();

        $additional_data['post_type'] = 'calendar';

        $additional_data['start_date'] = $this->formatDateFromTime($event['srow'], false);

        if ($event['erow']) {
            $additional_data['end_date'] = $this->formatDateFromTime($event['erow'], false);
        }

        $additional_data['all_day'] = (int)($event['allday'] == 'yes');

        $additional_data['location_name'] = $event['location_name'];
        $additional_data['location_address'] = $event['location'];
        $additional_data['location_latitude'] = $event['lat'];
        $additional_data['location_longitude'] = $event['lon'];

        if ($this->savePostNotification($event_id)) {
            $additional_data['notifications'] = array($this->formatDateFromTime($event['srow'] - 60*60*24, false));
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
