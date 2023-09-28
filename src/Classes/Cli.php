<?php

namespace MorningMedley\Database\Classes;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;

class Cli
{
    private Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
        if (class_exists('\WP_CLI')) {
            \WP_CLI::add_command('database', $this);
        }
    }

    public function migrate(array $args, array $assocArray)
    {
        $options = $assocArray;
        $paths = (array) $this->app->config['database.migration.path'];

        if (isset($options['path'])) {
            $paths = (array) $options['path'];
            unset($options['path']);
        }
        $repository = new DatabaseMigrationRepository($this->app['db'], $this->app['config']['database.migrations']);
        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }
        $migrator = new Migrator($repository, $this->app['db'], new Filesystem(), $this->app['events']);

        $migrator->run($paths, $options);
    }
}
