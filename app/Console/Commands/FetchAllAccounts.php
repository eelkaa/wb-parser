<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\WbApiService;
use Illuminate\Console\Command;

class FetchAllAccounts extends Command
{
    protected $signature = 'wb:fetch:all
                            {--dateFrom= : Дата ОТ (YYYY-MM-DD), по умолчанию — свежие данные из БД}
                            {--dateTo=   : Дата ДО (YYYY-MM-DD), по умолчанию — сегодня}
                            {--endpoint= : Конкретный эндпоинт: orders|sales|stocks|incomes|reportDetail}
                            {--account=  : ID конкретного аккаунта (по умолчанию — все)}';

    protected $description = 'Выгрузить данные WB для всех аккаунтов (запускается по cron)';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║   WB-Parser: Запуск полной выгрузки      ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('Время: ' . now()->format('Y-m-d H:i:s'));

        $accountIdFilter = $this->option('account');
        $query = Account::query();

        if ($accountIdFilter) {
            $query->where('id', $accountIdFilter);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->warn('⚠  Нет аккаунтов. Создайте: php artisan entity:account');
            return self::FAILURE;
        }

        $this->info("Найдено аккаунтов: {$accounts->count()}");

        $exitCode = self::SUCCESS;

        foreach ($accounts as $account) {
            $this->line('');
            $this->info("━━━ Аккаунт #{$account->id}: {$account->name} (компания: {$account->company->name ?? '—'}) ━━━");

            $result = $this->call('wb:fetch', [
                '--account'  => $account->id,
                '--dateFrom' => $this->option('dateFrom'),
                '--dateTo'   => $this->option('dateTo'),
                '--endpoint' => $this->option('endpoint'),
            ]);

            if ($result !== self::SUCCESS) {
                $this->error("✗ Аккаунт #{$account->id} завершился с ошибкой");
                $exitCode = self::FAILURE;
            }
        }

        $this->line('');
        $this->info('✅ Полная выгрузка завершена: ' . now()->format('Y-m-d H:i:s'));

        return $exitCode;
    }
}
