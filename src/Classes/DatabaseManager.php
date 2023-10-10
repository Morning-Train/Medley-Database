<?php

namespace MorningMedley\Database\Classes;

use Illuminate\Database\ConnectionInterface;

class DatabaseManager extends \Illuminate\Database\DatabaseManager
{
    private ConnectionInterface $connection;

    public function connection($name = null)
    {
        if ($name === 'wpdb' || $name === null) {
            return $this->connections['wpdb'];
        }

        return parent::connection($name);
    }

    public function setWpdbConnection(ConnectionInterface $connection)
    {
        $this->connections['wpdb'] = $connection;
    }
}
