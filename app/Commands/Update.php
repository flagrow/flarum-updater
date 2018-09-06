<?php

namespace App\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\ProgressBar;

class Update extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'update';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Interactively guides you through updating Flarum and installed extensions.';
    /**
     * @var Client
     */
    private $api;

    public function __construct(Client $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! $this->isValidFlarumInstallation()) {
            $this->error("No valid Flarum installation found at your current path.");
            exit;
        }

        if (($lock = $this->getComposerLock()) === null) {
            $this->error("No composer.lock file found, it is need to verify your current installation status.");
            exit;
        }

        if (! $this->confirm("Analysing Flarum releases and extension compatibility requires sending your composer.lock to Flagrow.io. Are you okay with us processing that information?")) {
            $this->comment("Okay. We can't help you without that data :(").
            exit;
        }

        /** @var ProgressBar $progress */
        $progress = null;

        $response = $this->api->post('updater/sync-lock', [
            'multipart' => [
                [
                    'name' => 'lock',
                    'contents' => fopen($lock, 'r')
                ]
            ],
            'progress' => function (
                $totalDownload, $currentDownload,
                $totalUpload, $currentUpload
            ) use (&$progress) {
                if ($progress === null) {
                    $progress = $this->output->createProgressBar($totalUpload);
                }

                $progress->setProgress($currentUpload);
            }
        ]);

        $progress->finish();

        dd($response->getBody()->getContents());
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    private function isValidFlarumInstallation(): bool
    {
        return config('database.connections.default') !== null;
    }

    private function getComposerLock(): ?string
    {
        $lock = getcwd() . '/composer.lock';

        return file_exists($lock) ? $lock : null;
    }
}
