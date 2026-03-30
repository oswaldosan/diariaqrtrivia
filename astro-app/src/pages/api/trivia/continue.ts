import type { APIRoute } from 'astro';
import { getPool } from '../../../lib/db';
import { getTriviaCookie, setTriviaCookie } from '../../../lib/session';
import type { TriviaState } from '../../../lib/types';

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const form = await request.formData();
  if (String(form.get('continue_next')) !== '1') return redirect('/trivia', 302);

  const trivia = await getTriviaCookie(cookies);
  if (!trivia?.feedback) return redirect('/trivia', 302);
  if (!trivia.feedback.isCorrect || trivia.feedback.gameOver) return redirect('/trivia', 302);

  const totalQ = trivia.questionIds.length;
  const nextIndex = trivia.currentIndex + 1;
  const cleared: TriviaState = { ...trivia, feedback: null };

  if (nextIndex >= totalQ) {
    const isWinner = cleared.score === totalQ ? 1 : 0;
    const pool = getPool();
    await pool.execute(
      'UPDATE trivia_sessions SET score = ?, is_winner = ?, completed_at = NOW() WHERE id = ?',
      [cleared.score, isWinner, cleared.sessionId]
    );
    await setTriviaCookie(cookies, cleared);
    return redirect('/result', 302);
  }

  await setTriviaCookie(cookies, {
    ...cleared,
    currentIndex: nextIndex,
  });
  return redirect('/trivia', 302);
};
