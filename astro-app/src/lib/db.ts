import mysql from 'mysql2/promise';
import { env } from './config';

let pool: mysql.Pool | null = null;

export function getPool(): mysql.Pool {
  if (!pool) {
    pool = mysql.createPool({
      host: env('DB_HOST', '127.0.0.1'),
      user: env('DB_USER', 'root'),
      password: env('DB_PASS', ''),
      database: env('DB_NAME', 'test'),
      port: Number(env('DB_PORT', '3306')),
      waitForConnections: true,
      connectionLimit: 10,
    });
  }
  return pool;
}
