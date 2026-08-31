<?php

declare(strict_types=1);

namespace App\Http;

use App\Product\ProductService;
use App\Product\ProductValidator;

final readonly class ProductController
{
    public function __construct(
        private ProductService $products,
        private ProductValidator $validator,
    ) {
    }

    public function index(): JsonResponse
    {
        return new JsonResponse(200, ['data' => $this->products->all()]);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->products->find($id);

        return $product === null
            ? JsonResponse::error(404, 'Producto no encontrado.')
            : new JsonResponse(200, ['data' => $product]);
    }

    /** @param array<string,mixed> $body */
    public function store(array $body): JsonResponse
    {
        $validated = $this->validator->validate($body);

        if ($validated['errors'] !== []) {
            return new JsonResponse(422, [
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validated['errors'],
            ]);
        }

        $product = $this->products->create($validated['data']);

        return new JsonResponse(201, ['data' => $product], [
            'Location' => '/productos/' . $product['id'],
        ]);
    }

    /** @param array<string,mixed> $body */
    public function update(int $id, array $body): JsonResponse
    {
        $validated = $this->validator->validate($body);

        if ($validated['errors'] !== []) {
            return new JsonResponse(422, [
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validated['errors'],
            ]);
        }

        $product = $this->products->update($id, $validated['data']);

        return $product === null
            ? JsonResponse::error(404, 'Producto no encontrado.')
            : new JsonResponse(200, ['data' => $product]);
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->products->delete($id)
            ? new JsonResponse(204)
            : JsonResponse::error(404, 'Producto no encontrado.');
    }
}
