import type { APIRoute } from 'astro';
import { getPool } from '../../../lib/db';
import { getTriviaCookie, setTriviaCookie } from '../../../lib/session';
import type { FeedbackState, TriviaState } from '../../../lib/types';

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const form = await request.formData();
  const answerId = Number(form.get('answer_id'));
  if (!answerId) return redirect('/trivia', 302);

  const trivia = await getTriviaCookie(cookies);
  if (!trivia) return redirect('/', 302);
  if (trivia.feedback) return redirect('/trivia', 302);

  const questionId = trivia.questionIds[trivia.currentIndex];
  const pool = getPool();

  const [rows] = await pool.execute(
    'SELECT is_correct FROM answers WHERE id = ? AND question_id = ?',
    [answerId, questionId]
  );
  const row = (rows as { is_correct: number }[])[0];
  const isCorrect = row && Number(row.is_correct) === 1 ? 1 : 0;

  await pool.execute(
    'INSERT INTO trivia_answers (session_id, question_id, answer_id, is_correct) VALUES (?, ?, ?, ?)',
    [trivia.sessionId, questionId, answerId, isCorrect]
  );

  const [qRows] = await pool.execute(
    'SELECT question_text, explanation FROM questions WHERE id = ?',
    [questionId]
  );
  const qrow = (qRows as { question_text: string; explanation: string | null }[])[0];

  const [aRows] = await pool.execute(
    'SELECT id, answer_text, is_correct FROM answers WHERE question_id = ? ORDER BY id ASC',
    [questionId]
  );
  const answersRows = aRows as { id: number; answer_text: string; is_correct: number }[];

  const explanationRaw = qrow?.explanation != null ? String(qrow.explanation).trim() : '';

  let next: TriviaState;

  if (isCorrect === 1) {
    const feedback: FeedbackState = {
      gameOver: false,
      isCorrect: true,
      explanation: explanationRaw !== '' ? qrow.explanation : null,
      questionText: qrow.question_text,
      answers: answersRows,
      selectedAnswerId: answerId,
      questionId,
    };
    next = {
      ...trivia,
      score: trivia.score + 1,
      feedback,
    };
  } else {
    await pool.execute(
      'UPDATE trivia_sessions SET score = ?, is_winner = 0, completed_at = NOW() WHERE id = ?',
      [trivia.score, trivia.sessionId]
    );
    const feedback: FeedbackState = {
      gameOver: true,
      isCorrect: false,
      explanation: explanationRaw !== '' ? qrow.explanation : null,
      questionText: qrow.question_text,
      answers: answersRows,
      selectedAnswerId: answerId,
      questionId,
    };
    next = {
      ...trivia,
      lossHint: explanationRaw,
      feedback,
    };
  }

  await setTriviaCookie(cookies, next);
  return redirect('/trivia', 302);
};
