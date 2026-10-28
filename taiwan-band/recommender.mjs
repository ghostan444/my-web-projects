export const GENRES = ['獨立搖滾', '迷幻搖滾', '後搖滾', '金屬', '龐克', '民謠'];
export const MOODS = ['放鬆', '浪漫', '憂鬱', '熱血', '宣洩', '專注'];
export function normalize(value) {
  return value.normalize('NFKC').toLowerCase().replace(/[\s’'·.。／/\-]/g, '');
}
export function findFavorite(bands, value) {
  const q = normalize(value.trim());
  if (!q) return null;
  return bands.find(b => [b.name, b.en, ...(b.aliases || [])].some(name => normalize(name) === q)) || null;
}
export function recommend(bands, { favorite = null, genres = [], mood = '', energy = 3 } = {}) {
  return bands.filter(b => b.id !== favorite?.id)
    .map(b => {
      const matchingGenres = b.genres.filter(g => genres.includes(g));
      const shared = favorite ? b.genres.filter(g => favorite.genres.includes(g)) : [];
      const moodMatch = !!mood && b.moods.includes(mood);
      const union = favorite ? new Set([...favorite.genres, ...b.genres]).size : 1;
      const score = (genres.length ? matchingGenres.length / genres.length * 12 : 0)
        + (favorite ? shared.length / union * 10 : 0)
        + (moodMatch ? 6 : 0) + (5 - Math.abs(energy - b.energy));
      const reason = matchingGenres.length ? matchingGenres.join('・')
        : shared.length ? '相近曲風：' + shared.join('・')
          : moodMatch ? mood + '氛圍' : '能量接近';
      return { band: b, score, reason, matchingGenres };
    })
    .sort((a, b) => b.score - a.score || a.band.id - b.band.id).slice(0, 3);
}
