import CRC32 from 'crc-32';
import { readdirSync } from 'node:fs';
import { join } from 'node:path';

function ldlDecorU32(pageId: string, salt: string): number {
  return (CRC32.str(`${pageId}|${salt}`) >>> 0) & 0x7fffffff;
}

function pickUniqueIndices(nFiles: number, need: number, seed: string): number[] {
  if (nFiles === 0 || need <= 0) return [];
  const indices: number[] = [];
  let h = CRC32.str(seed) >>> 0;
  for (let k = 0; k < need; k++) {
    h = CRC32.str(`${h}x${k}`) >>> 0;
    let i = h % nFiles;
    let guard = 0;
    while (indices.includes(i) && guard < nFiles + 5) {
      i = (i + 1) % nFiles;
      guard++;
    }
    indices.push(i);
  }
  return indices;
}

function animalsBasenames(): string[] {
  const dir = join(process.cwd(), 'public', 'assets', 'animals');
  try {
    const names = readdirSync(dir).filter((f) => f.endsWith('.png'));
    names.sort();
    return names;
  } catch {
    return [];
  }
}

function esc(s: string): string {
  return s
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

/** HTML decoración (misma lógica que PHP includes/decor.php) */
export function renderDecor(pageId: string): string {
  const files = animalsBasenames();
  const n = files.length;
  const positions = ['p1', 'p2', 'p3', 'p4', 'p5'];
  let out = '<div class="page-decor page-decor--top" aria-hidden="true">';

  if (n > 0) {
    const need = Math.min(5, n);
    const idxs = pickUniqueIndices(n, need, `${pageId}-anim`);
    idxs.forEach((fi, j) => {
      const basename = files[fi];
      const pos = positions[j] ?? `p${j + 1}`;
      const delay = j * 0.7;
      out += `<div class="page-decor-float page-decor-float--${pos}" style="animation-delay: ${delay}s">`;
      out += `<img class="page-decor-img" src="/assets/animals/${esc(basename)}" alt="" width="120" height="120" loading="lazy" decoding="async">`;
      out += '</div>';
    });
  }

  const seed = ldlDecorU32(pageId, 'spark');
  out += `<span class="page-decor-extra page-decor-extra--spark" style="animation-delay:${(seed % 5) * 0.15}s"></span>`;
  out += `<span class="page-decor-extra page-decor-extra--blob" style="animation-delay:${((seed >> 3) % 5) * 0.2}s"></span>`;
  out += `<span class="page-decor-extra page-decor-extra--ring" style="animation-delay:${((seed >> 6) % 5) * 0.25}s"></span>`;
  out += '</div>';

  out += '<div class="page-decor page-decor--field" aria-hidden="true">';
  const count = 22;
  for (let i = 0; i < count; i++) {
    const h = ldlDecorU32(pageId, `bit${i}`);
    const top = 3 + (h % 92);
    const side = (h >> 7) % 100;
    const size = 6 + (h % 36);
    let op = 0.05 + ((h >> 14) % 18) / 100;
    if (op > 0.22) op = 0.22;
    const delay = (h % 80) / 10;
    const dur = 9 + (h % 18);
    const rot = h % 360;
    const variant = i % 7;
    let pos = `top:${top}%;`;
    if (i % 4 === 1 || i % 4 === 2) {
      pos += `right:${2 + (side % 45)}%;left:auto;`;
    } else {
      pos += `left:${3 + (side % 50)}%;`;
    }
    out += `<span class="page-decor-bit page-decor-bit--v${variant}" style="${pos}width:${size}px;height:${size}px;opacity:${op};animation-delay:${delay}s;--dur:${dur}s;--rot:${rot}deg"></span>`;
  }
  out += '</div>';

  if (n > 0) {
    const scatterN = 16;
    out += '<div class="page-decor page-decor--scatter" aria-hidden="true">';
    for (let si = 0; si < scatterN; si++) {
      const h = ldlDecorU32(pageId, `scatter${si}`);
      const fi = h % n;
      const basename = files[fi];
      const top = 6 + (h % 82);
      const side = (h >> 9) % 88;
      const useRight = (h >> 17) & 1;
      const w = 38 + (h % 56);
      let op = 0.2 + ((h >> 21) % 20) / 100;
      if (op > 0.44) op = 0.44;
      const delay = (h % 70) / 10;
      const dur = 10 + (h % 16);
      const rot = (h % 21) - 10;
      let style = `top:${top}%;`;
      if (useRight) {
        style += `right:${2 + (side % 42)}%;left:auto;`;
      } else {
        style += `left:${2 + (side % 46)}%;`;
      }
      style += `opacity:${op};animation-delay:${delay}s;--dur:${dur}s;--rot:${rot}deg;width:${w}px;`;
      out += `<div class="page-decor-scatter" style="${style}">`;
      out += `<img src="/assets/animals/${esc(basename)}" alt="" width="128" height="128" loading="lazy" decoding="async">`;
      out += '</div>';
    }
    out += '</div>';
  }

  return out;
}
