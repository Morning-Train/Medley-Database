<?php

namespace MorningMedley\Database\Classes;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;

class DatabaseConnection extends \Illuminate\Database\MySqlConnection
{
    public $db;

    /**
     * Count of active transactions
     *
     * @var int
     */
    public $transactions = 0;

    /**
     * The database connection configuration options.
     *
     * @var array
     */
    protected $config = [];

    public function __construct($pdo)
    {
        /** @var \WPDB $wpdb */
        global $wpdb;

        $this->config = [
            'name' => 'morningmedley/db-connection',
        ];
        $this->db = $wpdb;
        parent::__construct($pdo, DB_NAME, $wpdb->prefix, []);
    }


    public function insert($query, $bindings = [], $sequence = null)
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($sequence) {
            if ($this->pretending()) {
                return true;
            }

            $query = $this->bind_params($query, $bindings);

            $this->recordsHaveBeenModified();

            $result = $this->db->query($query);

            $this->lastInsertId = $this->getPdo()->lastInsertId($sequence);

            return (bool) $result;
        });
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return mixed
     * @throws QueryException
     *
     */
    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $query = $this->bind_params($query, $bindings);

        $result = $this->db->get_row($query);

        if ($result === false || $this->db->last_error) {
            throw new QueryException($query, $bindings, new \Exception($this->db->last_error));
        }

        return $result;
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     * @throws QueryException
     *
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $query = $this->bind_params($query, $bindings);

        $result = $this->db->get_results($query);

        if ($result === false || $this->db->last_error) {
            throw new QueryException($query, $bindings, new \Exception($this->db->last_error));
        }

        return $result;
    }

    /**
     * A hacky way to emulate bind parameters into SQL query
     *
     * @param $query
     * @param $bindings
     *
     * @return mixed
     */
    private function bind_params($query, $bindings, $update = false)
    {

        $query = str_replace('"', '`', $query);
        $bindings = $this->prepareBindings($bindings);

        if (! $bindings) {
            return $query;
        }

        $bindings = array_map(function ($replace) {
            if (is_string($replace)) {
                $replace = "'" . esc_sql($replace) . "'";
            } elseif ($replace === null) {
                $replace = "null";
            }

            return $replace;
        }, $bindings);

        $query = str_replace(['%', '?'], ['%%', '%s'], $query);
        $query = vsprintf($query, $bindings);

        return $query;
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array  $bindings
     *
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        $new_query = $this->bind_params($query, $bindings, true);

        return $this->unprepared($new_query);
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array  $bindings
     *
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        $new_query = $this->bind_params($query, $bindings, true);

        $result = $this->db->query($new_query);

        if ($result === false || $this->db->last_error) {
            throw new QueryException($new_query, $bindings, new \Exception($this->db->last_error));
        }

        return intval($result);
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string  $query
     *
     * @return bool
     */
    public function unprepared($query)
    {
        $result = $this->db->query($query);

        return ($result === false || $this->db->last_error);
    }

    /**
     * Return the last insert id
     *
     * @param  string  $args
     *
     * @return int
     */
    public function lastInsertId($args)
    {
        return $this->db->insert_id;
    }
}
