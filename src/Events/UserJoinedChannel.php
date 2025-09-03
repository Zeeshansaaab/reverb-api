<?php

namespace ZeeshanSaab\ReverbApi\Events;

class UserJoinedChannel
{
    public string $channel;
    public array $user;

    public function __construct(string $channel, array $user)
    {
        $this->channel = $channel;
        $this->user    = $user;
    }
}

