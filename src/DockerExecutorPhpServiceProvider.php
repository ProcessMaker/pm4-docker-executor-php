<?php
namespace ProcessMaker\Package\DockerExecutorPhp;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ProcessMaker\Traits\PluginServiceProviderTrait;
use ProcessMaker\Models\ScriptExecutor;

class DockerExecutorPhpServiceProvider extends ServiceProvider
{
    use PluginServiceProviderTrait;

    const version = '1.0.0'; // Required for PluginServiceProviderTrait

    public function register()
    {
    }

    public function boot()
    {
        // Docker containers are built for a specific script executor ID `{id}`
        // This is replaced on execution.
        $image = env('SCRIPTS_PHP_IMAGE', 'processmaker4/executor-php-{id}:latest');

        \Artisan::command('docker-executor-php:install', function () {
            $scriptExecutor = ScriptExecutor::install([
                'language' => 'php',
                'title' => 'PHP Executor',
                'description' => 'Default PHP Executor'
            ]);
            
            // Build the instance image. This is the same as if you were to build it from the admin UI
            \Artisan::call('processmaker:build-script-executor ' . $scriptExecutor->id);
            
            // Restart the workers so they know about the new supported language
            \Artisan::call('horizon:terminate');
        });

        $config = [
            'name' => 'PHP',
            'runner' => 'PhpRunner',
            'mime_type' => 'application/x-php',
            'image' => $image,
            'options' => ['invokerPackage' => "ProcessMaker\\Client"],
            'init_dockerfile' => [
                'ARG SDK_DIR',
                'COPY $SDK_DIR /opt/sdk-php',
                'RUN composer config repositories.sdk-php path /opt/sdk-php',
                'RUN composer require processmaker/sdk-php:@dev',
            ],
            'package_path' => __DIR__ . '/..'
        ];
        config(['script-runners.php' => $config]);

        $this->completePluginBoot();
    }
}
