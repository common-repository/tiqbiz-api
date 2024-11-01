<?php

namespace Tiqbiz\Api;

use Exception;

class Api
{

    protected $endpoint_base = 'https://api.tiqbiz.com/v6/';

    protected $token;
    protected $email;
    protected $password;
    protected $business_id;
    protected $boxes;
    protected $timeout = 30;

    public function __construct()
    {
        $this->token = get_option('tiqbiz_api_token', null);

        $settings = get_option('tiqbiz_api_settings');

        foreach (array_keys(get_class_vars(__CLASS__)) as $setting) {
            if (isset($settings[$setting])) {
                $this->$setting = $settings[$setting];
            }
        }
    }

    protected function getBusiness()
    {
        static $business = null;

        if (!$business) {
            $user = $this->apiRequest('users/auth');

            if (!isset($user->admin_of) || !count($user->admin_of) || $user->admin_of[0]->role > 5) {
                throw new Exception('The user does not have sufficient admin access.');
            }

            $business = $user->admin_of[0];
        }

        return $business;
    }

    protected function getBoxes()
    {
        $business = $this->getBusiness();
        $boxes = $this->apiRequest('businesses/' . $business->id . '/boxes', 'GET', array('limit' => 999));

        return array_map(function($box) {
            return array(
                'id' => $box->id,
                'name' => $box->box_name,
                'description' => $box->box_description,
                'group' => $box->box_group
            );
        }, $boxes->data);
    }

    protected function getPluginVersion($plugin = null) {
        if ($plugin) {
            $plugin_details = @get_plugin_data(ABSPATH . 'wp-content/plugins/' . $plugin);

            if (strlen($plugin_details['Name'])) {
                if (!is_plugin_active($plugin)) {
                    return $plugin_details['Version'] . ' (Inactive)';
                }
            } else {
                return 'Not Installed';
            }
        } else {
            $plugin_details = get_plugin_data(TIQBIZ_API_PLUGIN_PATH);
        }

        return $plugin_details['Version'];
    }

    protected function apiRequest($path, $method = 'GET', $payload = array(), $authenticate = true, $throw_on_auth_error = false)
    {
        if (!$this->token && $authenticate) {
            $this->apiAuthenticate();
        }

        $endpoint = $this->endpoint_base . $path;

        $payload['_method'] = $method;
        $query = http_build_query($payload);

        if ($method == 'GET') {
            $endpoint .= '?' . $query;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method != 'GET') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }

        if ($this->token) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->token));
        }

        $response = curl_exec($ch);

        if (!$response) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        $results = json_decode($response);

        if (!is_object($results)) {
            throw new Exception('Invalid response from server.');
        }

        if (isset($results->error)) {
            $error = $this->implodeRecursively(', ', (array)$results->error->message);

            if ($results->error->status == 401 && !$throw_on_auth_error) {
                $this->token = null;

                $this->apiAuthenticate();

                return $this->apiRequest($path, $method, $payload, $authenticate, true);
            }

            throw new Exception($error);
        }

        return $results;
    }

    private function apiAuthenticate()
    {
        $auth = $this->apiRequest('users/login', 'POST', array(
            'email' => $this->email,
            'password' => $this->password
        ), false, true);

        $this->token = $auth->token;
        update_option('tiqbiz_api_token', $auth->token);
    }

    private function implodeRecursively($glue, $array)
    {
        $return = array();

        array_walk_recursive($array, function($item) use (&$return) {
            $return[] = $item;
        });

        return implode($glue, $return);
    }

}
