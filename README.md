# La Diaria Loto — Trivia

Aplicación web (**Astro** + **Node** + **MySQL**) para registrar participantes, trivia de varias preguntas, resultados y panel de administración con exportación CSV.

La app vive en **`astro-app/`** (el stack PHP anterior fue retirado).

## Requisitos

- Node.js 20+ (recomendado 22+ para herramientas recientes)
- MySQL 5.7+ / MariaDB 10+

## Base de datos

Importar **`schema.sql`** en tu base (crea tablas y puede incluir preguntas según el archivo).

Variables de entorno: ver **`astro-app/.env.example`**.

## Desarrollo

```bash
cd astro-app
cp .env.example .env
# Editar .env: DB_* y SESSION_SECRET
npm install
npm run dev
```

Abrir **http://localhost:4321**

### Preview de diseño (sin MySQL)

Archivo vacío **`astro-app/dev.enable`** o `LDL_DEV=1` en `.env`. Luego **http://localhost:4321/preview**

## Producción

```bash
cd astro-app
npm run build
node ./dist/server/entry.mjs
```

## Estructura útil

| Ruta | Rol |
|------|-----|
| `astro-app/` | Código fuente Astro, APIs y `public/assets/` (CSS, logo, animales) |
| `schema.sql` | Esquema y datos iniciales MySQL |

## Línea gráfica

Referencia de colores y trazos: ver comentarios en `astro-app/public/assets/style.css` (verde `#13a538`, `#76b82a`, etc.).

## Ideas de mejora

- Credenciales solo por variables de entorno en producción.
- HTTPS y cabeceras de seguridad.
- Proteger `/admin` con autenticación.
- Rate limiting en el registro.

## Licencia / uso

Proyecto interno para campaña **La Diaria Loto**. Ajustar según las políticas de tu organización.
# diariaqrtrivia
