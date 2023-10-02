<?php

namespace MorningMedley\Database\Classes;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @property Filesystem $filesystem
 */
class Cli
{
    private Container $app;

    public function __construct(Container $app, Filesystem $filesystem)
    {
        $this->app = $app;
        $this->filesystem = $filesystem;
        if (class_exists('\WP_CLI')) {
            \WP_CLI::add_command('database', $this);
        }
    }

    private function getMigrationPath(): string
    {
        return $this->app->basePath(Arr::first((array) $this->app['config']['database.migration.paths']));
    }

    private function getStubsPath(): string
    {
        return $this->app->basePath($this->app['config']['database.migration.stubsPath']);
    }

    public function migrate(array $args, array $assocArray)
    {
        $options = $assocArray;
        $paths = (array) $this->app->config['database.migration.paths'];

        if (isset($options['path'])) {
            $paths = (array) $options['path'];
            unset($options['path']);
        }
        $repository = new DatabaseMigrationRepository($this->app['db'], $this->getMigrationPath());
        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }
        $migrator = new Migrator($repository, $this->app['db'], $this->filesystem, $this->app['events']);

        $migrator->run($paths, $options);
    }

    public function makeMigration(array $args, array $assocArray)
    {
        $name = Str::snake(trim($args[0]));

        $table = $assocArray['table'] ?? false;

        $create = $assocArray['create'] ?? false;
        $path = $assocArray['path'] ?? $this->getMigrationPath();

        if (! $table && is_string($create)) {
            $table = $create;

            $create = true;
        }

        if (! $table) {
            $table = 'table';
        }

        $creator = new MigrationCreator($this->filesystem, $this->getStubsPath());
        $file = $creator->create(
            $name, $path, $table, $create
        );

        \WP_CLI::success("yas migration file: " . $file);
    }
}
