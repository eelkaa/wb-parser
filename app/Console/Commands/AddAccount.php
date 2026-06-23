<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Console\Command;

class AddAccount extends Command
{
    protected $signature = 'entity:account
                            {--company= : ID компании}
                            {--name=    : Название аккаунта}';

    protected $description = 'Добавить новый аккаунт к компании';

    public function handle(): int
    {
        // Выбор компании
        $companyId = $this->option('company');
        if (!$companyId) {
            $companies = Company::all(['id', 'name']);
            if ($companies->isEmpty()) {
                $this->error('Нет ни одной компании. Сначала создайте компанию: php artisan entity:company');
                return self::FAILURE;
            }
            $this->table(['ID', 'Название'], $companies->toArray());
            $companyId = $this->ask('Введите ID компании');
        }

        $company = Company::find($companyId);
        if (!$company) {
            $this->error("Компания с ID={$companyId} не найдена.");
            return self::FAILURE;
        }

        $name = $this->option('name') ?? $this->ask('Введите название аккаунта');
        if (empty($name)) {
            $this->error('Название аккаунта не может быть пустым.');
            return self::FAILURE;
        }

        $account = Account::create([
            'company_id' => $company->id,
            'name'       => $name,
        ]);

        $this->info("✅ Аккаунт создан:");
        $this->table(['ID', 'Компания', 'Название', 'Дата создания'], [
            [$account->id, $company->name, $account->name, $account->created_at],
        ]);

        return self::SUCCESS;
    }
}
