import type { APIRoute } from 'astro';
import type { ResultSetHeader } from 'mysql2';
import { TRIVIA_QUESTIONS_COUNT } from '../../lib/config';
import { getPool } from '../../lib/db';
import { setTriviaCookie } from '../../lib/session';
import type { TriviaState } from '../../lib/types';

const REG_ERR = 'ldl_reg_err';

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const form = await request.formData();
  const full_name = String(form.get('full_name') ?? '').trim();
  const identity_number = String(form.get('identity_number') ?? '').trim();
  const email = String(form.get('email') ?? '').trim();
  const phone = String(form.get('phone') ?? '').trim();

  const errors: Record<string, string> = {};
  const old = { full_name, identity_number, email, phone };

  if (!full_name) errors.full_name = 'El nombre completo es requerido.';
  if (!identity_number) errors.identity_number = 'El número de identidad es requerido.';
  if (!phone) errors.phone = 'El número de teléfono es requerido.';
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    errors.email = 'Correo no válido.';
  }

  if (Object.keys(errors).length > 0) {
    cookies.set(REG_ERR, JSON.stringify({ errors, old }), {
      path: '/',
      httpOnly: true,
      maxAge: 120,
      sameSite: 'lax',
    });
    return redirect('/', 302);
  }

  const pool = getPool();
  const emailVal = email || null;

  const [userResult] = await pool.execute(
    'INSERT INTO users (full_name, identity_number, email, phone) VALUES (?, ?, ?, ?)',
    [full_name, identity_number, emailVal, phone]
  );
  const userId = Number((userResult as ResultSetHeader).insertId);

  const [sessResult] = await pool.execute(
    'INSERT INTO trivia_sessions (user_id, total_questions) VALUES (?, ?)',
    [userId, TRIVIA_QUESTIONS_COUNT]
  );
  const sessionId = Number((sessResult as ResultSetHeader).insertId);

  const [qRows] = await pool.query<{ id: number }[]>(
    'SELECT id FROM questions WHERE is_active = 1 ORDER BY RAND() LIMIT ?',
    [TRIVIA_QUESTIONS_COUNT]
  );
  const questionIds = (qRows as { id: number }[]).map((r) => r.id);

  if (questionIds.length < TRIVIA_QUESTIONS_COUNT) {
    cookies.set(REG_ERR, JSON.stringify({
      errors: { _form: 'No hay suficientes preguntas activas. Contacte al administrador.' },
      old,
    }), { path: '/', httpOnly: true, maxAge: 120, sameSite: 'lax' });
    return redirect('/', 302);
  }

  const state: TriviaState = {
    sessionId,
    userId,
    userName: full_name,
    questionIds,
    currentIndex: 0,
    score: 0,
    feedback: null,
    lossHint: null,
  };
  await setTriviaCookie(cookies, state);
  return redirect('/trivia', 302);
};
