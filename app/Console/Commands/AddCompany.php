<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class AddCompany extends Command
{
    protected $signature = 'entity:company
                            {--name= : Название компании}';

    protected $description = 'Добавить новую компанию';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Введите название компании');

        if (empty($name)) {
            $this->error('Название компании не может быть пустым.');
            return self::FAILURE;
        }

        $company = Company::create(['name' => $name]);

        $this->info("✅ Компания создана:");
        $this->table(['ID', 'Название', 'Дата создания'], [
            [$company->id, $company->name, $company->created_at],
        ]);

        return self::SUCCESS;
    }
}
