<?php

namespace App\Console\Commands;

use App\Models\ApiService;
use Illuminate\Console\Command;

class AddApiService extends Command
{
    protected $signature = 'entity:api-service
                            {--name=        : Название сервиса (например: Wildberries)}
                            {--description= : Описание сервиса}';

    protected $description = 'Добавить новый API-сервис';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Введите название API-сервиса');
        if (empty($name)) {
            $this->error('Название не может быть пустым.');
            return self::FAILURE;
        }

        $description = $this->option('description') ?? $this->ask('Введите описание (можно пустым)', '');

        $service = ApiService::create([
            'name'        => $name,
            'description' => $description ?: null,
        ]);

        $this->info("✅ API-сервис создан:");
        $this->table(['ID', 'Название', 'Описание'], [
            [$service->id, $service->name, $service->description],
        ]);

        return self::SUCCESS;
    }
}
