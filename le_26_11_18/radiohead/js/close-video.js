/* Clicking the window's X pops up a full-screen video instead of closing anything.
   It closes itself when the video ends, or on Esc, a click outside the video, or its own X. */
(function () {
  var trigger = document.querySelector(".win-x");
  if (!trigger) return;

  var overlay = document.createElement("div");
  overlay.className = "tv-overlay";
  overlay.hidden = true;
  overlay.setAttribute("role", "dialog");
  overlay.setAttribute("aria-modal", "true");
  overlay.setAttribute("aria-label", "影片");
  overlay.innerHTML =
    '<video class="tv-overlay-video" playsinline preload="none"></video>' +
    '<button class="tv-overlay-close" type="button" aria-label="關閉影片">X</button>';
  document.body.appendChild(overlay);

  var video = overlay.querySelector("video");
  var closeBtn = overlay.querySelector(".tv-overlay-close");
  video.src = trigger.getAttribute("data-video");

  function open() {
    overlay.hidden = false;
    video.currentTime = 0;
    video.play().catch(function () {});
    closeBtn.focus();
  }

  function close() {
    if (overlay.hidden) return;
    video.pause();
    overlay.hidden = true;
    trigger.focus();
  }

  trigger.addEventListener("click", open);
  closeBtn.addEventListener("click", close);
  video.addEventListener("ended", close);
  overlay.addEventListener("click", function (e) { if (e.target === overlay) close(); });
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") close(); });
})();
