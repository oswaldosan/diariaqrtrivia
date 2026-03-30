import type { APIRoute } from 'astro';
import { getPool } from '../../../lib/db';

export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  if (String(form.get('action')) !== 'delete') return redirect('/admin?section=questions', 302);
  const id = Number(form.get('id'));
  if (!id) return redirect('/admin?section=questions', 302);
  const pool = getPool();
  await pool.execute('DELETE FROM questions WHERE id = ?', [id]);
  return redirect('/admin?section=questions&flash=ok', 302);
};
