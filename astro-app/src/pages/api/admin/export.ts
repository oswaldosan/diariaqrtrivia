import type { APIRoute } from 'astro';
import { getPool } from '../../../lib/db';

export const GET: APIRoute = async () => {
  const pool = getPool();
  const [rows] = await pool.query<
    {
      full_name: string;
      identity_number: string;
      email: string | null;
      phone: string;
      score: number | null;
      total_questions: number;
      resultado: string;
      fecha_registro: string;
      fecha_trivia: string | null;
    }[]
  >(`
    SELECT u.full_name, u.identity_number, u.email, u.phone,
           ts.score, ts.total_questions,
           CASE WHEN ts.is_winner = 1 THEN 'Ganador' ELSE 'No gano' END as resultado,
           u.created_at as fecha_registro, ts.completed_at as fecha_trivia
    FROM trivia_sessions ts
    JOIN users u ON u.id = ts.user_id
    ORDER BY ts.created_at DESC
  `);

  const date = new Date().toISOString().slice(0, 10);
  const lines: string[] = ['\ufeffNombre,Identidad,Email,Telefono,Puntaje,Total,Resultado,Registro,Trivia'];
  for (const r of rows as Record<string, unknown>[]) {
    const line = [
      r.full_name,
      r.identity_number,
      r.email ?? '',
      r.phone,
      r.score ?? '',
      r.total_questions,
      r.resultado,
      r.fecha_registro,
      r.fecha_trivia ?? '',
    ].map((v) => `"${String(v).replace(/"/g, '""')}"`);
    lines.push(line.join(','));
  }

  return new Response(lines.join('\n'), {
    status: 200,
    headers: {
      'Content-Type': 'text/csv; charset=UTF-8',
      'Content-Disposition': `attachment; filename="participantes_${date}.csv"`,
    },
  });
};
