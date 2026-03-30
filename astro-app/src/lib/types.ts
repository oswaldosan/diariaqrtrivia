export type FeedbackState = {
  gameOver: boolean;
  isCorrect: boolean;
  explanation: string | null;
  questionText: string;
  answers: { id: number; answer_text: string; is_correct: number }[];
  selectedAnswerId: number;
  questionId: number;
};

export type TriviaState = {
  sessionId: number;
  userId: number;
  userName: string;
  questionIds: number[];
  currentIndex: number;
  score: number;
  feedback?: FeedbackState | null;
  lossHint?: string | null;
};
