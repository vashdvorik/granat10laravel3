<?php

namespace App\Console\Commands;

use App\Services\EnvironmentSettingsService;
use App\Services\ModerationSecretStore;
use Illuminate\Console\Command;

class MigrateModerationSecrets extends Command
{
    protected $signature = 'moderation:migrate-secrets';

    protected $description = 'Encrypt existing moderation API keys in the database';

    public function handle(ModerationSecretStore $store, EnvironmentSettingsService $environment): int
    {
        $count = $store->importLegacy($environment);
        $this->info("Imported {$count} moderation secret(s).");

        return self::SUCCESS;
    }
}
