<?php

declare(strict_types=1);

namespace App\Product;

final class ProductService
{
    private int $usdRateCents;

    public function __construct(
        private readonly ProductRepository $products,
        string $usdRate,
    ) {
        $this->usdRateCents = self::toCents($usdRate);
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        return array_map($this->withUsdPrice(...), $this->products->all());
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $product = $this->products->find($id);

        return $product === null ? null : $this->withUsdPrice($product);
    }

    /**
     * @param array{nombre:string,descripcion:?string,precio:string} $data
     *
     * @return array<string,mixed>
     */
    public function create(array $data): array
    {
        return $this->withUsdPrice($this->products->create($data));
    }

    /**
     * @param array{nombre:string,descripcion:?string,precio:string} $data
     *
     * @return array<string,mixed>|null
     */
    public function update(int $id, array $data): ?array
    {
        $product = $this->products->update($id, $data);

        return $product === null ? null : $this->withUsdPrice($product);
    }

    public function delete(int $id): bool
    {
        return $this->products->delete($id);
    }

    /**
     * @param array<string,mixed> $product
     *
     * @return array<string,mixed>
     */
    private function withUsdPrice(array $product): array
    {
        $arsCents = self::toCents((string) $product['precio']);
        $numerator = $arsCents * 100;
        $usdCents = intdiv($numerator, $this->usdRateCents);

        if (2 * ($numerator % $this->usdRateCents) >= $this->usdRateCents) {
            ++$usdCents;
        }

        $product['id'] = (int) $product['id'];
        $product['precio'] = self::fromCents($arsCents);
        $product['precio_usd'] = self::fromCents($usdCents);

        return $product;
    }

    private static function toCents(string $decimal): int
    {
        [$integer, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');

        return (int) $integer * 100 + (int) str_pad($fraction, 2, '0');
    }

    private static function fromCents(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
