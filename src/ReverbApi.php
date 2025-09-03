<?php

namespace ZeeshanSaab\ReverbApi;

use Illuminate\Support\Facades\Http;


class ReverbApi
{
    protected string $scheme;
    protected string $host;
    protected int $port;
    protected string $appId;
    protected string $key;
    protected string $secret;

    public function __construct(array $config)
    {
        $this->scheme = $config['scheme'];
        $this->host   = $config['host'];
        $this->port   = $config['port'];
        $this->appId  = $config['app_id'];
        $this->key    = $config['key'];
        $this->secret = $config['secret'];
    }

    protected function request(string $method, string $path, array $params = [])
    {
        $method = strtoupper($method);

        $params['auth_key']       = $this->key;
        $params['auth_timestamp'] = time();
        $params['auth_version']   = '1.0';

        ksort($params);
        $query = http_build_query($params);

        $stringToSign = "{$method}\n{$path}\n{$query}";
        $params['auth_signature'] = hash_hmac('sha256', $stringToSign, $this->secret);

        $url = "{$this->scheme}://{$this->host}:{$this->port}{$path}";

        $response = Http::timeout(10)->{$method}($url, $params);

        if ($response->failed()) {
            throw new \RuntimeException("Reverb API failed: " . $response->body());
        }

        return $response->json();
    }

    public function channels(array $params = [])
    {
        return $this->request('GET', "/apps/{$this->appId}/channels", $params);
    }

    public function channelInfo(string $channel, array $params = [])
    {
        return $this->request('GET', "/apps/{$this->appId}/channels/{$channel}", $params);
    }

    public function channelUsers(string $channel)
    {
        return $this->request('GET', "/apps/{$this->appId}/channels/{$channel}/users");
    }

    public function triggerEvent(string $channel, string $event, array $data = [])
    {
        $payload = [
            'name'     => $event,
            'channels' => [$channel],
            'data'     => json_encode($data),
        ];

        return $this->request('POST', "/apps/{$this->appId}/events", $payload);
    }
}