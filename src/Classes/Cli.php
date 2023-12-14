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
            \WP_CLI::add_command('medley migrate', [$this, 'migrate']);
            \WP_CLI::add_command('medley make:migration', [$this, 'makeMigration']);
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
        $paths = (array) $this->app['config']['database.migration.paths'];

        if (isset($options['path'])) {
            $paths = (array) $options['path'];
            unset($options['path']);
        }

        $repository = $this->app->makeWith(
            DatabaseMigrationRepository::class, [
            'resolver' => $this->app['db'],
            'table' => $this->app['config']['database.migrations'],
        ]);

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $migrator = $this->app->makeWith(Migrator::class, [
            'repository' => $repository,
            'resolver' => $this->app['db'],
            'files' => $this->filesystem,
            'dispatcher' => $this->app['events'],
        ]);

        /** @var Migrator $migrator */
        $ranMigrations = $migrator->run($paths, $options);
        $count = count($ranMigrations);

        if ($count !== 0) {
            foreach ($ranMigrations as $i => $file) {
                $ranMigrations[$i] = [
                    'index' => $i,
                    'file' => $file,
                ];
            }
            \WP_CLI::success("Ran {$count} migrations successfully");
            \WP_CLI\Utils\format_items('table', $ranMigrations, ['index', 'file']);

        } else {
            \WP_CLI::success("No migrations to run");
        }
    }

    public function makeMigration(array $args, array $assocArray)
    {
        if (empty($args[0])) {
            \WP_CLI::error('Please supply a name for this migration');
        }

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

        $creator = $this->app->makeWith(MigrationCreator::class,
            ['files' => $this->filesystem, 'customStubPath' => $this->getStubsPath()]);
        $file = $creator->create(
            $name, $path, $table, $create
        );

        \WP_CLI::success("Migration file: {$file} created");
    }
}
