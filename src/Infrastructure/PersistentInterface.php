<?php

namespace App\Infrastructure;

interface PersistentInterface
{
    /**
     * Connect to DB
     *
     * @return object
     */
    public function connect(): object;

    /**
     * Close connection
     *
     * @return bool
     */
    public function close(): bool;

    /**
     * Check if connection is active
     *
     * @return bool
     */
    public function isConnected(): bool;
}
