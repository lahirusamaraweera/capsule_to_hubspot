<?php

class capsules
{
    private $token = null;
    private $baseurl = 'https://api.capsulecrm.com/';

    function __construct($token = null)
    {
        $this->token = $token;
    }

    public function request($url, $method = 'get', $data = null)
    {
        if (is_null($url) || is_null($this->token)) {
            throw new \Exception('token or URL required');
        }

        $formated_url = $this->baseurl . $url;
        switch ($method) {
            case "post":
                return \Httpful\Request::post($formated_url)
                    ->addHeader("Authorization", "Bearer {$this->token}")
                    ->body(json_encode($data))
                    ->send();
            default:
                return \Httpful\Request::get($formated_url)
                    ->addHeader("Authorization", "Bearer {$this->token}")
                    ->send();
        }
    }


}