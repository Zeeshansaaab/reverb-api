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

    protected function sign(string $method, string $path, array $params = []): array
    {
        $params['auth_key']       = $this->key;
        $params['auth_timestamp'] = time();
        $params['auth_version']   = '1.0';

        ksort($params);
        $query = http_build_query($params);

        $stringToSign = strtoupper($method) . "\n{$path}\n{$query}";
        $params['auth_signature'] = hash_hmac('sha256', $stringToSign, $this->secret);

        return $params;
    }

    protected function request(string $method, string $path, array $params = [], array $body = null)
    {
        $method = strtoupper($method);

        // Base params
        $params['auth_key']       = $this->key;
        $params['auth_timestamp'] = time();
        $params['auth_version']   = '1.0';

        // Add body_md5 for POST/PUT
        $json = null;
        if ($body !== null) {
            $json = json_encode($body, JSON_UNESCAPED_UNICODE);
            $params['body_md5'] = md5($json);
        }

        // Lowercase all keys for signing
        $lowerParams = [];
        foreach ($params as $k => $v) {
            $lowerParams[strtolower($k)] = $v;
        }

        // Sort by key (lowercase)
        ksort($lowerParams);

        // Build query string (not urlencoded)
        $query = urldecode(http_build_query($lowerParams, '', '&'));

        // Build string to sign
        $stringToSign = "{$method}\n{$path}\n{$query}";
        $signature = hash_hmac('sha256', $stringToSign, $this->secret);
        $params['auth_signature'] = $signature;

        // Final URL
        $url = "{$this->scheme}://{$this->host}:{$this->port}{$path}?" . http_build_query($params);

        $http = Http::timeout(10);

        $response = $json === null
            ? $http->send($method, $url)
            : $http->withHeaders(['Content-Type' => 'application/json'])
                ->send($method, $url, ['body' => $json]);

        if ($response->failed()) {
            throw new \RuntimeException("Reverb API failed: " . $response->body());
        }

        return $response->json();
    }


    /** ------------------------
     *  Channels Endpoints
     *  -----------------------*/
    public function channels(array $params = [])
    {
        return $this->request('GET', "/apps/{$this->appId}/channels", $params);
    }

    public function channelInfo(string $channel, array $params = [])
    {
        return $this->request('GET', "/apps/{$this->appId}/channels/{$channel}", $params);
    }
    /** ------------------------
     *  Users Endpoints
     *  -----------------------*/
    public function channelUsers(string $channel)
    {
        return $this->request('GET', "/apps/{$this->appId}/channels/{$channel}/users");
    }
    public function terminateUserConnections(string $userId)
    {
        return $this->request(
            'POST',
            "/apps/{$this->appId}/users/{$userId}/terminate_connections"
        );
    }


    /** ------------------------
     *  Events Endpoints
     *  -----------------------*/
    public function triggerEvent(string $channel, string $event, array $data = [])
    {
        $payload = [
            'name'     => $event,
            'channels' => [$channel],
            'data'     => json_encode($data),
        ];

        return $this->request('POST', "/apps/{$this->appId}/events", [], $payload);
    }


    public function triggerBatchEvents(array $batchPayload)
    {
        // Format: [["channel" => "c1", "name" => "ev", "data" => "data"], ...]
        return $this->request('POST', "/apps/{$this->appId}/batch_events", [], $batchPayload);
    }

    /** ------------------------
     *  Debugging / Webhooks
     *  -----------------------*/
    public function webhooks()
    {
        return $this->request('GET', "/apps/{$this->appId}/webhooks");
    }
}
