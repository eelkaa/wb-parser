<?php

namespace App\Console;

use App\Console\Commands\FetchWbData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        FetchWbData::class,
    ];

    /**
     * Планировщик: запускать синхронизацию каждый час.
     * Можно изменить на ->daily() или ->everyThirtyMinutes()
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('wb:fetch')
                 ->hourly()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/wb-fetch.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
