<?php

declare(strict_types=1);

namespace App\Product;

final readonly class ProductValidator
{
    /**
     * @param array<string,mixed> $input
     *
     * @return array{
     *     data:array{nombre:string,descripcion:?string,precio:string},
     *     errors:array<string,list<string>>
     * }
     */
    public function validate(array $input): array
    {
        $errors = [];
        $name = $this->name($input['nombre'] ?? null, $errors);
        $description = $this->description($input['descripcion'] ?? null, $errors);
        $price = $this->price($input['precio'] ?? null, $errors);

        return [
            'data' => ['nombre' => $name, 'descripcion' => $description, 'precio' => $price],
            'errors' => $errors,
        ];
    }

    /** @param array<string,list<string>> $errors */
    private function name(mixed $value, array &$errors): string
    {
        if (!is_string($value) || trim($value) === '') {
            $errors['nombre'][] = 'El nombre es obligatorio.';

            return '';
        }

        $name = trim($value);

        if (mb_strlen($name) > 255) {
            $errors['nombre'][] = 'El nombre no puede superar los 255 caracteres.';
        }

        return $name;
    }

    /** @param array<string,list<string>> $errors */
    private function description(mixed $value, array &$errors): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            $errors['descripcion'][] = 'La descripción debe ser texto.';

            return null;
        }

        return trim($value) === '' ? null : trim($value);
    }

    /** @param array<string,list<string>> $errors */
    private function price(mixed $value, array &$errors): string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            $errors['precio'][] = 'El precio es obligatorio.';

            return '0.00';
        }

        $raw = trim((string) $value);

        if (preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $raw, $parts) !== 1) {
            $errors['precio'][] = 'El precio debe ser un número positivo con hasta dos decimales.';

            return '0.00';
        }

        $integer = ltrim($parts[1], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = str_pad($parts[2] ?? '', 2, '0');

        if ($integer === '0' && $fraction === '00') {
            $errors['precio'][] = 'El precio debe ser mayor a cero.';
        }

        if (strlen($integer) > 8) {
            $errors['precio'][] = 'El precio no puede superar 99999999.99.';
        }

        return sprintf('%s.%s', $integer, $fraction);
    }
}
