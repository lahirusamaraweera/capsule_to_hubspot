<?php
class hubspot
{
    private $token = null;
    private $user_id = null;
    private $app_id = null;
    private $baseurl = 'https://api.hubapi.com';
    function __construct($token = null, $user_id = null, $app_id = null)
    {
        $this->token = $token;
        $this->user_id = $user_id;
        $this->app_id = $app_id;
    }

    public function request($url, $method = 'get', $data = null, $url_params = [])
    {
        try {
            if (is_null($url) || is_null($this->token)) {
                throw new \Exception('token or URL required');
            }
            $url_params['hapikey'] = $this->token;
            $formated_url = $this->baseurl . $url . "?" . http_build_query($url_params);
            switch ($method) {
                case "post":
                    return \Httpful\Request::post($formated_url)
                        ->sendsJson()
                        ->body(json_encode($data))
                        ->send();
                case "put":
                    return \Httpful\Request::put($formated_url)
                        ->sendsJson()
                        ->body(json_encode($data))
                        ->send();
                case "delete":
                    return \Httpful\Request::delete($formated_url)
                        ->send();
                default:
                    return \Httpful\Request::get($formated_url)
                        ->send();
            }

        } catch (\Exception $e) {
            return (object)[
                'body' => [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]
            ];
        }
    }


}