<?php

declare(strict_types=1);

use App\Config;
use App\DatabaseConnection;
use App\Http\JsonResponse;
use App\Http\ProductController;
use App\Product\ProductRepository;
use App\Product\ProductService;
use App\Product\ProductValidator;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

require dirname(__DIR__) . '/vendor/autoload.php';

try {
    $config = Config::fromEnvironment();
    $repository = new ProductRepository(DatabaseConnection::get($config));
    $controller = new ProductController(
        new ProductService($repository, $config->usdRate),
        new ProductValidator(),
    );

    $dispatcher = simpleDispatcher(static function (RouteCollector $routes): void {
        $routes->addRoute('GET', '/productos', 'index');
        $routes->addRoute('GET', '/productos/{id:\\d+}', 'show');
        $routes->addRoute('POST', '/productos', 'store');
        $routes->addRoute('PUT', '/productos/{id:\\d+}', 'update');
        $routes->addRoute('DELETE', '/productos/{id:\\d+}', 'destroy');
    });

    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    $route = $dispatcher->dispatch($method, is_string($path) ? rawurldecode($path) : '/');

    $response = match ($route[0]) {
        Dispatcher::NOT_FOUND => JsonResponse::error(404, 'Ruta no encontrada.'),
        Dispatcher::METHOD_NOT_ALLOWED => JsonResponse::error(
            405,
            'Método no permitido.',
            ['Allow' => implode(', ', $route[1])],
        ),
        Dispatcher::FOUND => dispatch($controller, $route[1], $route[2], $method),
        default => JsonResponse::error(500, 'Error interno del servidor.'),
    };

    $response->send();
} catch (Throwable $error) {
    error_log(sprintf('%s: %s', $error::class, $error->getMessage()));
    JsonResponse::error(500, 'Error interno del servidor.')->send();
}

/**
 * @param array<string,string> $params
 */
function dispatch(ProductController $controller, string $handler, array $params, string $method): JsonResponse
{
    $id = isset($params['id']) ? (int) $params['id'] : 0;

    if (in_array($method, ['POST', 'PUT'], true) && !isJsonRequest()) {
        return JsonResponse::error(415, 'Se esperaba Content-Type application/json.');
    }

    try {
        $body = in_array($method, ['POST', 'PUT'], true) ? jsonBody() : [];
    } catch (JsonException) {
        return JsonResponse::error(400, 'El cuerpo no contiene un objeto JSON válido.');
    }

    return match ($handler) {
        'index' => $controller->index(),
        'show' => $controller->show($id),
        'store' => $controller->store($body),
        'update' => $controller->update($id, $body),
        'destroy' => $controller->destroy($id),
        default => JsonResponse::error(500, 'Error interno del servidor.'),
    };
}

function isJsonRequest(): bool
{
    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));

    return $contentType === 'application/json';
}

/**
 * @return array<string,mixed>
 *
 * @throws JsonException
 */
function jsonBody(): array
{
    $raw = trim((string) file_get_contents('php://input'));

    if ($raw === '') {
        return [];
    }

    if (!str_starts_with($raw, '{')) {
        throw new JsonException('Se esperaba un objeto JSON.');
    }

    $body = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);

    if (!is_array($body)) {
        throw new JsonException('Se esperaba un objeto JSON.');
    }

    return $body;
}
