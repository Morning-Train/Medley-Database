<?php

    namespace MorningMedley\Database\Classes;

    class DatabaseManager extends \Illuminate\Database\DatabaseManager
    {
        private \Illuminate\Database\ConnectionInterface $connection;

        public function connection($name = null)
        {
            return $this->connection;
        }

        /**
         * @param  \Illuminate\Database\ConnectionInterface  $connection
         */
        public function setConnection(\Illuminate\Database\ConnectionInterface $connection): void
        {
            $this->connection = $connection;
        }
    }
