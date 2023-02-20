<?php

namespace App\Infrastructure;

interface PersistentInterface
{
    public function connect(): object;

    public function close(): bool;

    public function isConnected(): bool;
}
