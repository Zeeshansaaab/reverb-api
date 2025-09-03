<?php


namespace ZeeshanSaab\ReverbApi\Facades;

use Illuminate\Support\Facades\Facade;

class Reverb extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'reverb.api';
    }
    public static function isUserOnline(string $userId): bool
    {
        return app(\ZeeshanSaab\ReverbApi\PresenceManager::class)->isUserOnline($userId);
    }

    public static function onlineUsers(): array
    {
        return app(\ZeeshanSaab\ReverbApi\PresenceManager::class)->onlineUsers();
    }

}
