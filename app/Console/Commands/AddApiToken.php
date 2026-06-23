<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\ApiService;
use App\Models\Token;
use App\Models\TokenType;
use Illuminate\Console\Command;

class AddApiToken extends Command
{
    protected $signature = 'entity:token
                            {--account= : ID аккаунта}
                            {--service= : ID API-сервиса}
                            {--type=    : ID типа токена}
                            {--value=   : Значение токена}';

    protected $description = 'Добавить или обновить API-токен для аккаунта';

    public function handle(): int
    {
        // Аккаунт
        $accountId = $this->option('account');
        if (!$accountId) {
            $accounts = Account::with('company')->get();
            if ($accounts->isEmpty()) {
                $this->error('Нет ни одного аккаунта. Создайте: php artisan entity:account');
                return self::FAILURE;
            }
            $this->table(['ID', 'Компания', 'Аккаунт'], $accounts->map(fn($a) => [
                $a->id, $a->company->name ?? '—', $a->name,
            ])->toArray());
            $accountId = $this->ask('Введите ID аккаунта');
        }

        $account = Account::find($accountId);
        if (!$account) {
            $this->error("Аккаунт с ID={$accountId} не найден.");
            return self::FAILURE;
        }

        // API-сервис
        $serviceId = $this->option('service');
        if (!$serviceId) {
            $services = ApiService::all();
            if ($services->isEmpty()) {
                $this->error('Нет ни одного API-сервиса. Создайте: php artisan entity:api-service');
                return self::FAILURE;
            }
            $this->table(['ID', 'Название', 'Описание'], $services->toArray());
            $serviceId = $this->ask('Введите ID API-сервиса');
        }

        $service = ApiService::find($serviceId);
        if (!$service) {
            $this->error("API-сервис с ID={$serviceId} не найден.");
            return self::FAILURE;
        }

        // Тип токена
        $typeId = $this->option('type');
        if (!$typeId) {
            $types = TokenType::all();
            if ($types->isEmpty()) {
                $this->error('Нет ни одного типа токена. Создайте: php artisan entity:token-type');
                return self::FAILURE;
            }
            $this->table(['ID', 'Название', 'Описание'], $types->toArray());
            $typeId = $this->ask('Введите ID типа токена');
        }

        $tokenType = TokenType::find($typeId);
        if (!$tokenType) {
            $this->error("Тип токена с ID={$typeId} не найден.");
            return self::FAILURE;
        }

        // Значение токена
        $value = $this->option('value') ?? $this->secret('Введите значение токена');
        if (empty($value)) {
            $this->error('Значение токена не может быть пустым.');
            return self::FAILURE;
        }

        // Создаём или обновляем (upsert по уникальному ключу)
        $token = Token::updateOrCreate(
            [
                'account_id'     => $account->id,
                'api_service_id' => $service->id,
                'token_type_id'  => $tokenType->id,
            ],
            ['token_value' => $value]
        );

        $action = $token->wasRecentlyCreated ? 'создан' : 'обновлён';
        $this->info("✅ Токен {$action}:");
        $this->table(
            ['ID', 'Аккаунт', 'Сервис', 'Тип', 'Токен (начало)'],
            [[$token->id, $account->name, $service->name, $tokenType->name, substr($value, 0, 10) . '...']]
        );

        return self::SUCCESS;
    }
}
