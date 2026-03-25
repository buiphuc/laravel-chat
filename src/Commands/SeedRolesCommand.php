<?php

namespace PhucBui\Chat\Commands;

use Illuminate\Console\Command;
use PhucBui\Chat\Models\ChatRole;

class SeedRolesCommand extends Command
{
    protected $signature = 'chat:seed-roles {--force : Overwrite existing roles}';

    protected $description = 'Seed default chat roles from config';

    public function handle(): void
    {
        $roles = config('chat.default_roles', []);

        if (empty($roles)) {
            $this->warn('No default roles defined in config/chat.php');
            return;
        }

        $isDefault = true; // First role is default

        foreach ($roles as $index => $roleData) {
            $existing = ChatRole::where('name', $roleData['name'])->first();

            if ($existing && !$this->option('force')) {
                $this->line("  Skipping: {$roleData['name']} (already exists)");
                continue;
            }

            ChatRole::updateOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'permissions' => $roleData['permissions'],
                    'is_default' => $index === count($roles) - 1, // Last role (member) is default
                ]
            );

            $this->info("  Created: {$roleData['name']}");
        }

        $this->info('Chat roles seeded successfully!');
    }
}
