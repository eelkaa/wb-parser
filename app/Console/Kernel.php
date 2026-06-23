<?php

namespace App\Console;

use App\Console\Commands\AddAccount;
use App\Console\Commands\AddApiService;
use App\Console\Commands\AddApiToken;
use App\Console\Commands\AddCompany;
use App\Console\Commands\AddTokenType;
use App\Console\Commands\FetchAllAccounts;
use App\Console\Commands\FetchWbData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        FetchWbData::class,
        FetchAllAccounts::class,
        AddCompany::class,
        AddAccount::class,
        AddApiService::class,
        AddTokenType::class,
        AddApiToken::class,
    ];

    /**
     * Планировщик: запускать выгрузку дважды в день — в 6:00 и 18:00.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('wb:fetch:all')
            ->twiceDaily(6, 18)
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/wb-fetch.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
