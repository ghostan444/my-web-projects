import { GENRES, MOODS, normalize, findFavorite, recommend } from './recommender.mjs';
let bands = [];
const chosen = new Set();
let mood = '';
const $ = selector => document.querySelector(selector);
const escapeHTML = value => String(value).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
function makeChips(list, element, kind) {
  list.forEach(text => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'chip';
    button.textContent = text;
    button.setAttribute('aria-pressed', 'false');
    button.onclick = () => {
      if (kind === 'genre') {
        chosen.has(text) ? chosen.delete(text) : chosen.add(text);
        button.setAttribute('aria-pressed', chosen.has(text));
      } else {
        mood = mood === text ? '' : text;
        element.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', b.textContent === mood));
      }
    };
    element.append(button);
  });
}
makeChips(GENRES, $('#genres'), 'genre');
makeChips(MOODS, $('#moods'), 'mood');
$('#energy').oninput = () => $('#energyvalue').textContent = `${$('#energy').value} / 5`;
function render(items, reasons = []) {
  $('#cards').innerHTML = items.map((band, i) => `
    <article class="card" style="animation-delay:${i * .07}s">
      <div class="cover" aria-hidden="true"><span>SIDE ${i + 1}</span><b>✳</b><span>TW / ${String(band.id + 1).padStart(2, '0')}</span></div>
      <div class="card-body">
        <div class="card-head"><div><h3>${escapeHTML(band.name)}</h3><span class="en">${escapeHTML(band.en)}</span></div>
        <span class="match">${escapeHTML(reasons[i] || '編輯選曲')}</span></div>
        <div class="tags">${band.genres.map(escapeHTML).join(' / ')}</div>
        <p class="desc">${escapeHTML(band.desc)}</p>
        ${band.note ? `<p class="band-note">${escapeHTML(band.note)}</p>` : ''}
        <div class="song"><div><small>從這首開始聽</small>♪ ${escapeHTML(band.song)}</div>
        <a href="https://www.youtube.com/results?search_query=${encodeURIComponent(band.name + ' ' + band.song + ' official')}" target="_blank" rel="noopener noreferrer" aria-label="在 YouTube 搜尋 ${escapeHTML(band.name)} ${escapeHTML(band.song)}">YouTube 搜尋 ↗</a></div>
        <a class="source" href="${escapeHTML(band.source)}" target="_blank" rel="noopener noreferrer">樂團資料來源 ↗</a>
      </div>
    </article>`).join('');
  $('.count').textContent = items.length + ' PICKS';
}
function showDefault() {
  $('#result-title').textContent = '先聽聽這三團';
  $('#meta').textContent = '從三種聲音開始，或在調頻區選擇你的喜好。';
  $('#message').innerHTML = '';
  render([bands[1], bands[0], bands[4]]);
}
$('#form').onsubmit = event => {
  event.preventDefault();
  if (!bands.length) return;
  const raw = $('#favorite').value.trim();
  const favorite = findFavorite(bands, raw);
  const energy = Number($('#energy').value);
  const genres = [...chosen];
  const results = recommend(bands, { favorite, genres, mood, energy });
  $('#result-title').textContent = '這次，試試這幾團';
  $('#meta').textContent = [favorite ? '從 ' + favorite.name + ' 出發' : '依你的聽歌偏好',
  genres.length ? genres.join('、') : '不限曲風', mood || '不限心情', '能量 ' + energy + '/5'].join(' · ');
  const messages = [];
  if (raw && !favorite) messages.push(`目前還沒收錄「${raw}」，這次先依其他選項推薦。可從輸入建議挑選已收錄的樂團。`);
  if (genres.length && results.some(result => !result.matchingGenres.length)) messages.push('前三組中有其他曲風的探索推薦，依心情或能量補充；每張卡片會標明配對依據。');
  if (favorite && results.some(result => !result.band.genres.some(g => favorite.genres.includes(g)))) messages.push('部分結果與原樂團沒有相同曲風標籤，屬於其他條件下的延伸探索。');
  $('#message').innerHTML = messages.map(text => '<p class="notice">' + escapeHTML(text) + '</p>').join('');
  render(results.map(result => result.band), results.map(result => result.reason));
  if (innerWidth < 621) $('.results').scrollIntoView({ behavior: 'smooth', block: 'start' });
};
$('#form').onreset = () => {
  chosen.clear(); mood = '';
  document.querySelectorAll('.chip').forEach(button => button.setAttribute('aria-pressed', 'false'));
  $('#energyvalue').textContent = '3 / 5';
  if (bands.length) showDefault();
};
$('#catalog-toggle').onclick = () => {
  const open = $('#catalog').classList.toggle('open');
  $('#catalog-toggle').setAttribute('aria-expanded', open);
  $('#catalog-toggle').textContent = open ? '收起樂團清單 ↑' : `看看全部 ${bands.length} 團 ↗`;
  if (open) $('#catalog').scrollIntoView({ behavior: 'smooth' });
};
function renderCatalog() {
  const q = normalize($('#catalog-search').value);
  const genre = $('#catalog-genre').value;
  const visible = bands.filter(b => (!genre || b.genres.includes(genre)) &&
    (!q || [b.name, b.en, ...(b.aliases || [])].some(name => normalize(name).includes(q))));
  $('#catalog-count').textContent = `${visible.length} / ${bands.length} 組`;
  $('#catalog-grid').innerHTML = visible.length ? visible.map(b => `
    <a href="${escapeHTML(b.source)}" target="_blank" rel="noopener noreferrer">${escapeHTML(b.name)} ↗
    <small>${b.genres.map(escapeHTML).join(' / ')}</small>${b.note ? `<small>${escapeHTML(b.note)}</small>` : ''}</a>`).join('')
    : '<p class="empty">找不到符合的樂團，試試其他關鍵字或曲風。</p>';
}
$('#catalog-search').oninput = renderCatalog;
$('#catalog-genre').onchange = renderCatalog;
fetch('bands.json').then(response => {
  if (!response.ok) throw Error('Could not load bands');
  return response.json();
}).then(data => {
  bands = data;
  document.querySelectorAll('[data-band-count]').forEach(el => el.textContent = bands.length);
  $('#catalog-toggle').textContent = `看看全部 ${bands.length} 團 ↗`;
  $('#bandnames').innerHTML = bands.map(b => `<option value="${escapeHTML(b.name)}">${escapeHTML(b.en)}</option>`).join('');
  GENRES.forEach(genre => {
    const option = document.createElement('option');
    option.textContent = genre; option.value = genre;
    $('#catalog-genre').append(option);
  });
  renderCatalog(); showDefault();
}).catch(() => {
  $('#cards').innerHTML = '<p class="empty">資料暫時無法載入，請重新整理再試一次。</p>';
  $('.submit').disabled = true;
});
