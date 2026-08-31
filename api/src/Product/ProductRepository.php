<?php

declare(strict_types=1);

namespace App\Product;

use PDO;
use RuntimeException;

final readonly class ProductRepository
{
    private const COLUMNS = 'id, nombre, descripcion, precio, created_at, updated_at';

    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        $statement = $this->pdo->query('SELECT ' . self::COLUMNS . ' FROM productos ORDER BY id');

        if ($statement === false) {
            return [];
        }

        /** @var list<array<string,mixed>> $products */
        $products = $statement->fetchAll();

        return $products;
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM productos WHERE id = :id');
        $statement->execute(['id' => $id]);
        $product = $statement->fetch();

        return is_array($product) ? $product : null;
    }

    /**
     * @param array{nombre:string,descripcion:?string,precio:string} $data
     *
     * @return array<string,mixed>
     */
    public function create(array $data): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO productos (nombre, descripcion, precio) VALUES (:nombre, :descripcion, :precio)'
        );
        $statement->execute($data);

        return $this->find((int) $this->pdo->lastInsertId())
            ?? throw new RuntimeException('No se pudo recuperar el producto creado.');
    }

    /**
     * @param array{nombre:string,descripcion:?string,precio:string} $data
     *
     * @return array<string,mixed>|null
     */
    public function update(int $id, array $data): ?array
    {
        $statement = $this->pdo->prepare(
            'UPDATE productos SET nombre = :nombre, descripcion = :descripcion, precio = :precio WHERE id = :id'
        );
        $statement->execute($data + ['id' => $id]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM productos WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }
}
