import type { APIRoute } from 'astro';
import type { ResultSetHeader } from 'mysql2';
import { getPool } from '../../../lib/db';

export const POST: APIRoute = async ({ request, redirect }) => {
  const form = await request.formData();
  const action = String(form.get('action'));
  if (action !== 'save_question') return redirect('/admin?section=questions', 302);

  let qid = Number(form.get('question_id'));
  const text = String(form.get('question_text') ?? '').trim();
  const answersRaw = form.getAll('answers');
  const answers = answersRaw.map((a) => String(a).trim()).filter(Boolean);
  const correct = Number(form.get('correct_answer'));

  if (!text || answers.length < 2) {
    return redirect('/admin?section=questions&flash=error&msg=' + encodeURIComponent('Pregunta y al menos 2 respuestas son requeridas.'), 302);
  }

  const pool = getPool();
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();
    if (qid > 0) {
      await conn.execute('UPDATE questions SET question_text = ? WHERE id = ?', [text, qid]);
      await conn.execute('DELETE FROM answers WHERE question_id = ?', [qid]);
    } else {
      const [ins] = await conn.execute('INSERT INTO questions (question_text) VALUES (?)', [text]);
      qid = Number((ins as ResultSetHeader).insertId);
    }
    for (let i = 0; i < answers.length; i++) {
      const at = answers[i];
      const ic = i === correct ? 1 : 0;
      await conn.execute(
        'INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)',
        [qid, at, ic]
      );
    }
    await conn.commit();
  } catch (e) {
    await conn.rollback();
    throw e;
  } finally {
    conn.release();
  }

  return redirect('/admin?section=questions&flash=ok', 302);
};
