<?php

namespace ZeeshanSaab\ReverbApi\Events;

class UserWentOffline
{
    public string $userId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }
}