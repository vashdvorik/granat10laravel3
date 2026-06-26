<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunGranatRuntime extends Command
{
    protected $signature = 'granat:work
                            {--serve : Start the local HTTP server}
                            {--vite : Start Vite dev server}
                            {--host=127.0.0.1 : Host for php artisan serve}
                            {--port=8000 : Port for php artisan serve}
                            {--dry-run : Print planned processes without starting them}';

    protected $description = 'Run all local Granat runtime workers from one command';

    /** @var array<string, Process> */
    private array $processes = [];

    private bool $stopping = false;

    public function handle(): int
    {
        $commands = $this->commands();

        $this->warnIfRuntimeLooksIncomplete();

        if ($this->option('dry-run')) {
            foreach ($commands as $name => $command) {
                $this->line($name.': '.$this->displayCommand($command));
            }

            return self::SUCCESS;
        }

        $this->registerSignalHandlers();

        foreach ($commands as $name => $command) {
            $process = new Process($command, base_path());
            $process->setTimeout(null);
            $process->start();
            $this->processes[$name] = $process;
            $this->info("Started {$name}");
        }

        $this->info('Granat runtime is running. Press Ctrl+C to stop.');

        while (! $this->stopping) {
            foreach ($this->processes as $name => $process) {
                $this->flushProcessOutput($name, $process);

                if (! $process->isRunning()) {
                    $this->error("Process {$name} stopped with exit code {$process->getExitCode()}.");
                    $this->stopProcesses();

                    return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
                }
            }

            usleep(200_000);
        }

        $this->stopProcesses();

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function commands(): array
    {
        $commands = [
            'queue:moderation' => [
                PHP_BINARY,
                'artisan',
                'queue:work',
                '--queue='.$this->moderationQueues(),
                '--tries=3',
                '--timeout=90',
            ],
            'queue:orders' => [
                PHP_BINARY,
                'artisan',
                'queue:work',
                '--queue='.(string) config('orders.notifications_queue', 'orders'),
                '--tries=3',
                '--timeout=30',
            ],
            'scheduler' => [
                PHP_BINARY,
                'artisan',
                'schedule:work',
            ],
        ];

        if ($this->option('serve')) {
            $commands = [
                'http' => [
                    PHP_BINARY,
                    'artisan',
                    'serve',
                    '--host='.(string) $this->option('host'),
                    '--port='.(string) $this->option('port'),
                ],
                ...$commands,
            ];
        }

        if ($this->option('vite')) {
            $commands['vite'] = [$this->npmBinary(), 'run', 'dev'];
        }

        return $commands;
    }

    private function moderationQueues(): string
    {
        $queues = [
            (string) config('moderation.queue', 'moderation'),
            (string) config('moderation.images.queue', config('moderation.queue', 'moderation')),
            'default',
        ];

        return collect($queues)
            ->filter()
            ->unique()
            ->implode(',');
    }

    private function warnIfRuntimeLooksIncomplete(): void
    {
        if (config('queue.default') === 'sync') {
            $this->warn('QUEUE_CONNECTION=sync: workers are not required, but queued production behavior is disabled.');
        }

        if (blank(config('services.telegram.bot_token'))) {
            $this->warn('TELEGRAM_BOT_TOKEN is not configured: Telegram delivery will fail until it is set.');
        }

        if (blank(config('services.telegram.webhook_secret'))) {
            $this->warn('TELEGRAM_WEBHOOK_SECRET is not configured: Telegram webhook will reject requests.');
        }
    }

    private function registerSignalHandlers(): void
    {
        if (! method_exists($this, 'trap') || ! defined('SIGINT') || ! defined('SIGTERM')) {
            return;
        }

        $this->trap([SIGINT, SIGTERM], function (): void {
            $this->stopping = true;
            $this->line('Stopping Granat runtime...');
        });
    }

    private function flushProcessOutput(string $name, Process $process): void
    {
        $output = $process->getIncrementalOutput();
        if ($output !== '') {
            $this->output->write($this->prefixOutput($name, $output));
        }

        $errorOutput = $process->getIncrementalErrorOutput();
        if ($errorOutput !== '') {
            $this->output->write($this->prefixOutput($name, $errorOutput));
        }
    }

    private function prefixOutput(string $name, string $output): string
    {
        return collect(preg_split("/\r\n|\n|\r/", $output) ?: [])
            ->filter(static fn (string $line): bool => $line !== '')
            ->map(static fn (string $line): string => "[{$name}] {$line}")
            ->implode(PHP_EOL).PHP_EOL;
    }

    private function stopProcesses(): void
    {
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $process->stop(5);
            }
        }
    }

    /**
     * @param array<int, string> $command
     */
    private function displayCommand(array $command): string
    {
        return implode(' ', array_map(
            static fn (string $part): string => str_contains($part, ' ') ? '"'.$part.'"' : $part,
            $command,
        ));
    }

    private function npmBinary(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm';
    }
}
