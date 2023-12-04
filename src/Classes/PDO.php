<?php

namespace MorningMedley\Database\Classes;

class PDO extends \PDO
{
    private \wpdb $db;

    public function setDb(\wpdb $db): void
    {
        $this->db = $db;
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return $this->db->insert_id;
    }
}
