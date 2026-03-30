import type { APIRoute } from 'astro';
import { getPool } from '../../../lib/db';

export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  if (String(form.get('action')) !== 'toggle') return redirect('/admin?section=questions', 302);
  const id = Number(form.get('id'));
  if (!id) return redirect('/admin?section=questions', 302);
  const pool = getPool();
  await pool.execute('UPDATE questions SET is_active = NOT is_active WHERE id = ?', [id]);
  return redirect('/admin?section=questions&flash=ok', 302);
};
