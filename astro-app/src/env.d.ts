/// <reference path="../.astro/types.d.ts" />
/// <reference types="astro/client" />

interface ImportMetaEnv {
  readonly SESSION_SECRET: string;
  readonly DB_HOST: string;
  readonly DB_USER: string;
  readonly DB_PASS: string;
  readonly DB_NAME: string;
  readonly DB_PORT: string;
  readonly LDL_DEV: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
