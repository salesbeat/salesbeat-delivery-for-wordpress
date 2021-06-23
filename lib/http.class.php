<?php

namespace Salesbeat\Lib;

class Http
{
    /**
     * @param $url
     * @param $data
     * @return array
     */
    public function get($url, $data)
    {
        if (!$url)
            return ['type' => 'error', 'message' => 'Empty url'];

        if (!is_array($data))
            return ['status' => 'error', 'message' => 'Data not array'];

        return $this->send('get', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return array
     */
    public function post($url, $data)
    {
        if (!$url)
            return ['type' => 'error', 'message' => 'Empty url'];

        if (!is_array($data))
            return ['status' => 'error', 'message' => 'Data not array'];

        return $this->send('post', $url, $data);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    private function send($method, $url, $data)
    {
        $options = [];
        if ($method == 'get') {
            $url = $url . '?' . http_build_query($data);

            $options = [
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => [],
                'cookies' => [],
                'method' => 'GET'
            ];
        } elseif ($method == 'post') {
            $options = [
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => ['Content-Type' => 'application/json'],
                'body' => wp_json_encode($data),
                'cookies' => [],
                'method' => 'POST'
            ];
        }

        $response = wp_remote_request($url, $options);
        $result = wp_remote_retrieve_body($response);

        return !empty($result) ? json_decode($result, true) : [];
    }
}