(function () {
  var video = document.getElementById('ambience-video');
  var button = document.getElementById('ambience-toggle');
  if (!video || !button) return;
  video.muted = true;
  function sync() {
    button.textContent = video.paused ? '▶' : 'Ⅱ';
    button.setAttribute('aria-label', video.paused ? '播放背景影片 / Play background video' : '暫停背景影片 / Pause background video');
    button.setAttribute('title', button.getAttribute('aria-label'));
  }
  video.addEventListener('play', sync);
  video.addEventListener('pause', sync);
  button.addEventListener('click', function () {
    if (video.paused) video.play().catch(sync); else video.pause();
  });
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { video.autoplay = false; video.pause(); }
  else video.play().catch(sync);
  sync();
})();
