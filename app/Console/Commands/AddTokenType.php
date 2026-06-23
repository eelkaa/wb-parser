<?php

namespace App\Console\Commands;

use App\Models\TokenType;
use Illuminate\Console\Command;

class AddTokenType extends Command
{
    protected $signature = 'entity:token-type
                            {--name=        : Название типа токена (например: Statistics)}
                            {--description= : Описание}';

    protected $description = 'Добавить новый тип токена';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Введите название типа токена');
        if (empty($name)) {
            $this->error('Название не может быть пустым.');
            return self::FAILURE;
        }

        $description = $this->option('description') ?? $this->ask('Введите описание (можно пустым)', '');

        $tokenType = TokenType::create([
            'name'        => $name,
            'description' => $description ?: null,
        ]);

        $this->info("✅ Тип токена создан:");
        $this->table(['ID', 'Название', 'Описание'], [
            [$tokenType->id, $tokenType->name, $tokenType->description],
        ]);

        return self::SUCCESS;
    }
}
