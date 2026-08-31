# Catálogo de productos

Challenge de API REST en PHP nativo y frontend en HTML, CSS y JavaScript para administrar
productos.

## Ejecutar el proyecto

Se necesita Docker con Compose.

```bash
cp .env.example .env
```
```bash
docker compose up -d --build
```

El frontend queda accesible desde <http://localhost:8080>. MySQL solo es accesible desde la red interna
de Docker.

La tabla y los datos iniciales se crean cuando el volumen está vacío. Para
reiniciar la base de datos:

```bash
docker compose down -v
docker compose up -d --build
```

## Configuración

Las credenciales de MySQL y la cotización se leen desde `.env`. La variable
`PRECIO_USD` representa cuántos ars equivalen a un dólar. Por
ejemplo, con `PRECIO_USD=1512.50`, un producto de $1512.50 vale 1 USD.

## API

| Método | Ruta | Resultado |
|---|---|---|
| `GET` | `/productos` | Lista todos los productos |
| `GET` | `/productos/{id}` | Obtiene un producto |
| `POST` | `/productos` | Crea un producto |
| `PUT` | `/productos/{id}` | Actualiza un producto |
| `DELETE` | `/productos/{id}` | Elimina un producto |

Ejemplos:

```bash
curl http://localhost:8080/productos
```
```bash
curl -X POST http://localhost:8080/productos \
  -H 'Content-Type: application/json' \
  -d '{"nombre":"Webcam","descripcion":"Full HD","precio":"85000.00"}'
```

Las respuestas incluyen `precio` en pesos y `precio_usd` calculado por la API.

## Arquitectura

El backend usa una estructura Controller–Service–Repository:

- `ProductController` construye las respuestas HTTP.
- `ProductService` contiene los casos de uso y la conversión a dólares.
- `ProductRepository` concentra las consultas PDO preparadas.
- `ProductValidator` valida y normaliza los datos de entrada.
- `DatabaseConnection` mantiene una única conexión PDO por proceso.

FastRoute se utiliza únicamente para registrar las cinco rutas.

```text
api/        API REST en PHP
frontend/   interfaz en HTML, CSS y JavaScript
docker/     configuración de Apache, PHP y MySQL
```
