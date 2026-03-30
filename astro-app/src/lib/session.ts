import 'dotenv/config';
import { SignJWT, jwtVerify } from 'jose';
import type { AstroCookies } from 'astro';
import type { TriviaState } from './types';

const COOKIE = 'ldl_trivia';

function secret(): Uint8Array {
  const s = import.meta.env.SESSION_SECRET || process.env.SESSION_SECRET || 'dev-only-change-SESSION_SECRET';
  return new TextEncoder().encode(s);
}

export async function getTriviaCookie(cookies: AstroCookies): Promise<TriviaState | null> {
  const raw = cookies.get(COOKIE)?.value;
  if (!raw) return null;
  try {
    const { payload } = await jwtVerify(raw, secret());
    const s = (payload as { s?: string }).s;
    if (!s) return null;
    return JSON.parse(s) as TriviaState;
  } catch {
    return null;
  }
}

export async function setTriviaCookie(cookies: AstroCookies, data: TriviaState): Promise<void> {
  const token = await new SignJWT({ s: JSON.stringify(data) })
    .setProtectedHeader({ alg: 'HS256' })
    .setIssuedAt()
    .setExpirationTime('7d')
    .sign(secret());

  cookies.set(COOKIE, token, {
    path: '/',
    httpOnly: true,
    sameSite: 'lax',
    secure: import.meta.env.PROD,
    maxAge: 60 * 60 * 24 * 7,
  });
}

export async function clearTriviaCookie(cookies: AstroCookies): Promise<void> {
  cookies.delete(COOKIE, { path: '/' });
}
