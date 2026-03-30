import 'dotenv/config';
import { existsSync } from 'node:fs';
import { join } from 'node:path';

export const APP_NAME = 'La Diaria Loto';
export const TRIVIA_QUESTIONS_COUNT = 6;

export function env(key: string, fallback: string): string {
  const p = process.env[key];
  if (p !== undefined && p !== '') return p;
  const v = import.meta.env[key];
  if (v !== undefined && v !== '') return String(v);
  return fallback;
}

export function isPreviewAllowed(): boolean {
  if (process.env.LDL_DEV === '1') return true;
  try {
    return existsSync(join(process.cwd(), 'dev.enable'));
  } catch {
    return false;
  }
}
