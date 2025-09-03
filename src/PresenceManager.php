<?php

namespace ZeeshanSaab\ReverbApi;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use ZeeshanSaab\ReverbApi\Events\UserJoinedChannel;
use ZeeshanSaab\ReverbApi\Events\UserLeftChannel;
use ZeeshanSaab\ReverbApi\Events\UserWentOnline;
use ZeeshanSaab\ReverbApi\Events\UserWentOffline;

class PresenceManager
{
    protected ReverbApi $api;

    public function __construct(ReverbApi $api)
    {
        $this->api = $api;
    }

    public function sync(string $channel): void
    {
        $cacheKey = "reverb.presence.{$channel}";
        $globalKey = "reverb.global.presence";

        $previous = Cache::get($cacheKey, []);
        $current  = $this->api->channelUsers($channel)['users'] ?? [];

        $previousIds = collect($previous)->pluck('id')->all();
        $currentIds  = collect($current)->pluck('id')->all();

        // Joined channel
        foreach (array_diff($currentIds, $previousIds) as $userId) {
            $user = collect($current)->firstWhere('id', $userId);
            Event::dispatch(new UserJoinedChannel($channel, $user));

            // update global map
            $this->markUserOnline($userId, $channel);
        }

        // Left channel
        foreach (array_diff($previousIds, $currentIds) as $userId) {
            $user = collect($previous)->firstWhere('id', $userId);
            Event::dispatch(new UserLeftChannel($channel, $user));

            // update global map
            $this->markUserOffline($userId, $channel);
        }

        Cache::put($cacheKey, $current, 60);
    }

    protected function markUserOnline(string $userId, string $channel): void
    {
        $globalKey = "reverb.global.presence.{$userId}";
        $channels = Cache::get($globalKey, []);
        $wasOffline = empty($channels);

        $channels[] = $channel;
        Cache::put($globalKey, array_unique($channels), 60);

        if ($wasOffline) {
            Event::dispatch(new UserWentOnline($userId));
        }
    }

    protected function markUserOffline(string $userId, string $channel): void
    {
        $globalKey = "reverb.global.presence.{$userId}";
        $channels = Cache::get($globalKey, []);
        $channels = array_diff($channels, [$channel]);

        if (empty($channels)) {
            Cache::forget($globalKey);
            Event::dispatch(new UserWentOffline($userId));
        } else {
            Cache::put($globalKey, $channels, 60);
        }
    }

    public function isUserOnline(string $userId): bool
    {
        return Cache::has("reverb.global.presence.{$userId}");
    }

    public function onlineUsers(): array
    {
        $allKeys = Cache::getRedis()->keys("reverb.global.presence.*"); // if Redis
        return array_map(fn($key) => str_replace("reverb.global.presence.", '', $key), $allKeys);
    }
}
