<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;
use WeakMap;

/**
 * Зашифрованное поле, которое не роняет страницу, если его нельзя прочитать.
 *
 * Пишет и читает так же, как встроенный `encrypted` (`Crypt::encryptString`), поэтому старые данные совместимы.
 * Разница в одном: если значение нельзя расшифровать (ключ `APP_KEY` менялся, запись повреждена или записана
 * без шифрования), вместо исключения возвращается `UNREADABLE`, а в журнал пишется предупреждение.
 * Остальное содержимое страницы показывается как обычно.
 */
class SafeEncrypted implements CastsAttributes
{
    /** Что видит пользователь вместо данных, которые прочитать нельзя. */
    public const UNREADABLE = 'Данные недоступны';

    /** @var WeakMap<Model, array<string, true>>|null сбои, уже записанные в журнал: одна запись на поле модели */
    private static ?WeakMap $reported = null;

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException $exception) {
            $this->report($model, $key, $exception);

            return self::UNREADABLE;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null ? null : Crypt::encryptString((string) $value);
    }

    private function report(Model $model, string $key, DecryptException $exception): void
    {
        self::$reported ??= new WeakMap();
        $fields = self::$reported[$model] ?? [];

        if (isset($fields[$key])) {
            return;
        }

        $fields[$key] = true;
        self::$reported[$model] = $fields;
        $id = $model->getKey();

        try {
            Log::warning('Поле не расшифровывается: возможно, сменился APP_KEY или запись повреждена.', [
                'model' => $model::class,
                'id' => $id,
                'field' => $key,
                'reason' => $exception->getMessage(),
            ]);
        } catch (Throwable) {
            // журнал не должен ломать страницу
        }
    }
}
