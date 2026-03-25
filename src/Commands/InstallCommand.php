<?php

namespace PhucBui\Chat\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'chat:install {--force : Overwrite existing files}';

    protected $description = 'Install the Laravel Chat package';

    public function handle(): void
    {
        $this->info('Installing Laravel Chat Package...');

        // Publish config
        $this->call('vendor:publish', [
            '--tag' => 'chat-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--tag' => 'chat-migrations',
            '--force' => $this->option('force'),
        ]);

        // Optionally publish views
        if ($this->confirm('Do you want to publish chat views?', false)) {
            $this->call('vendor:publish', [
                '--tag' => 'chat-views',
                '--force' => $this->option('force'),
            ]);
        }

        $this->info('');
        $this->info('Laravel Chat Package installed successfully!');
        $this->info('');
        $this->info('Next steps:');
        $this->info('  1. Update config/chat.php with your actors configuration');
        $this->info('  2. Run: php artisan migrate');
        $this->info('  3. Run: php artisan chat:seed-roles');
        $this->info('  4. Add ChatActorInterface and HasChat trait to your User model(s)');
        $this->info('  5. Register actor resolvers in your AppServiceProvider');
    }
}
