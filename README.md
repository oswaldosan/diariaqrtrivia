# La Diaria Loto — Trivia QR

Aplicación web en **PHP** y **MySQL**: registro, trivia (un error elimina), resultados, admin y export CSV.

## Requisitos

- PHP 8+ con extensión `mysqli`
- MySQL 5.7+ / MariaDB 10+

## Base de datos

Importar **`schema.sql`** (phpMyAdmin o `mysql -u USER -p DB < schema.sql`).

## Configuración

Editar **`config.php`**: `DB_USER`, `DB_PASS`, `DB_NAME`, o definir variables de entorno `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT`.

En **Linux/WSL**, con `dev.enable` o `LDL_DEV=1` el host por defecto es `127.0.0.1` (evita error de socket con `localhost`).

## Vista previa sin MySQL

1. `touch dev.enable` o `export LDL_DEV=1`
2. `php -S localhost:8080`
3. Abrir **http://localhost:8080/preview.php**

En producción no subas `dev.enable`.

## Estructura

| Archivo | Rol |
|---------|-----|
| `index.php` | Registro |
| `trivia.php` | Preguntas y feedback |
| `result.php` | Resultado |
| `admin.php` | Panel y CSV |
| `preview.php` | Demo de diseño |
| `config.php` | BD, sesión, assets |
| `includes/decor.php` | Decoración |
| `assets/` | CSS, logo, animales |

## Local

```bash
php -S localhost:8080
```

Abrir `http://localhost:8080/index.php`
