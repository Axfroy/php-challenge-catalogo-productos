<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

final readonly class Config
{
    private function __construct(
        public string $dbHost,
        public int $dbPort,
        public string $dbName,
        public string $dbUser,
        public string $dbPassword,
        public string $usdRate,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $rate = self::required('PRECIO_USD');

        if (preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $rate) !== 1 || (float) $rate <= 0) {
            throw new RuntimeException('PRECIO_USD debe ser un número mayor a cero con hasta dos decimales.');
        }

        $port = filter_var(self::required('DB_PORT'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);

        if ($port === false) {
            throw new RuntimeException('DB_PORT debe ser un puerto válido.');
        }

        return new self(
            self::required('DB_HOST'),
            $port,
            self::required('DB_NAME'),
            self::required('DB_USER'),
            self::required('DB_PASSWORD'),
            $rate,
        );
    }

    private static function required(string $name): string
    {
        $value = getenv($name);

        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException(sprintf('Falta la variable de entorno %s.', $name));
        }

        return trim($value);
    }
}
