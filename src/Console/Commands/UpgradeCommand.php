<?php

namespace Laracord\Console\Commands;

use Illuminate\Console\Command;

class UpgradeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laracord:upgrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A simple upgrade helper to assist in upgrading Laracord between major versions';

    /**
     * The replacement patterns.
     */
    protected array $replacements;

    /**
     * Determine whether the command is hidden.
     */
    protected $hidden = true;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->components->info('Starting the Laracord upgrade process...');

        if (! $this->components->confirm('These actions are irreversible. Do you wish to <fg=blue>continue</>?', true)) {
            $this->components->error('The upgrade has been <fg=red>cancelled</>.');

            return;
        }

        $this->handleServices();

        $this->components->info('Upgrade process completed successfully!');
    }

    /**
     * Handle the Service -> Task upgrade.
     */
    protected function handleServices(): void
    {
        $replacements = [
            'namespace App\Services' => 'namespace App\Tasks',
            'use App\Services' => 'use App\Tasks',
            '@var \App\Services' => '@var \App\Tasks',
            'App\Services\\' => 'App\Tasks\\',
            'use Laracord\Services\Service;' => 'use Laracord\Tasks\Task;',
            'extends Service' => 'extends Task',
            'The service' => 'The task',
            'the service' => 'the task',
        ];

        $this->components->info('Checking for Service classes to migrate...');

        $services = app_path('Services');
        $tasks = app_path('Tasks');

        if (! file_exists($services)) {
            return;
        }

        if (! file_exists($tasks)) {
            mkdir($tasks, 0755, true);
        }

        $files = glob($services.'/*.php');

        if (empty($files)) {
            return;
        }

        $fileNames = collect($files)
            ->map(fn ($file) => basename($file, '.php'))
            ->map(fn ($class) => "<fg=blue>{$class}</>")
            ->all();

        $this->components->bulletList($fileNames);

        if (! $this->components->confirm('Found <fg=blue>'.count($files).'</> Service classes to migrate. Do you wish to <fg=blue>continue</>?', true)) {
            $this->components->error('The upgrade has been <fg=red>cancelled</>.');

            exit(0);
        }

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $newPath = str_replace('/Services/', '/Tasks/', $file);

            $this->components->task(
                "Migrating <fg=blue>{$name}</> class",
                fn () => $this->handleUpgrade($file, $newPath, $replacements)
            );
        }

        if (count(glob($services.'/*')) === 0) {
            rmdir($services);
        }

        $this->newLine();

        $this->components->info('Successfully migrated <fg=blue>'.count($files).'</> Service classes to Tasks.');
    }

    /**
     * Upgrade the Service class file.
     */
    protected function handleUpgrade(string $oldPath, string $newPath, array $replacements): bool
    {
        $content = file_get_contents($oldPath);

        foreach ($replacements as $pattern => $replacement) {
            $content = str_replace($pattern, $replacement, $content);
        }

        $result = file_put_contents($newPath, $content) !== false;

        if ($result) {
            unlink($oldPath);
        }

        return $result;
    }
}
