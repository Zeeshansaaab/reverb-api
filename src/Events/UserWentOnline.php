<?php

namespace ZeeshanSaab\ReverbApi\Events;


class UserWentOnline
{
    public string $userId;

    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }
}