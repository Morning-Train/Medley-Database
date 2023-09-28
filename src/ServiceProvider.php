<?php

namespace MorningMedley\Database;

use Illuminate\Database\Connectors\ConnectionFactory;
use MorningMedley\Database\Classes\Cli;
use MorningMedley\Database\Classes\DatabaseConnection;
use MorningMedley\Database\Classes\DatabaseManager;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\DatabaseTransactionsManager;
use MorningMedley\Facades\DB;

class ServiceProvider extends DatabaseServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . "/config/config.php", 'database');
        parent::register();
    }

    public function boot()
    {
        parent::boot();
        if (class_exists("\WP_CLI")) {
            $this->app->bind('db.cli', fn($app) => new Cli($app));
            $this->app->make('db.cli');
        }
    }

    /**
     * Register the primary database bindings.
     *
     * @return void
     */
    protected function registerConnectionServices()
    {
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->make('db')->setConnection(new DatabaseConnection($this->createPDO()));

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });

        $this->app->bind('db.schema', function ($app) {
            return $app['db']->connection()->getSchemaBuilder();
        });

        $this->app->singleton('db.transactions', function ($app) {
            return new DatabaseTransactionsManager;
        });

        DB::setFacadeApplication($this->app);
    }

    public function createPDO(): \PDO
    {
        global $wpdb;

        // Parse the DB_HOST using WordPress's specific style
        // Supports IPv4, IPv6, and socket connections
        $host_data = $wpdb->parse_db_host(DB_HOST);

        if (is_array($host_data)) {
            [$host, $port, $socket, $is_ipv6] = $host_data;
        } else {
            // Redacted. Throw an error or something
        }

        // Wrap the IPv6 host in braces as required
        if ($is_ipv6 && extension_loaded('mysqlnd')) {
            $host = "[$host]";
        }

        // Generate either a socket connection string or TCP connection string
        if (isset($socket)) {
            $connection_str = 'mysql:unix_socket=' . $socket . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        } else {
            $connection_str = 'mysql:host=' . $host . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

            if (isset($port)) {
                $connection_str .= ';port=' . $port;
            }
        }

        // Open the connection
        return new \PDO($connection_str, DB_USER, DB_PASSWORD);
    }
}
