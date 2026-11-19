/* Pixel speech bubble beside the bear: types each line out, holds it, then moves on.
   Edit LINES to change what it says; one entry = one bubble. */
(function () {
  var LINES = [
    "Let down again"
  ];

  var el = document.querySelector(".bubble-text");
  if (!el) return;

  var still = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var i = 0;

  function show(line, done) {
    if (still) { el.textContent = line; setTimeout(done, 4000); return; }
    var n = 0;
    el.textContent = "";
    (function type() {
      el.textContent = line.slice(0, ++n);
      if (n < line.length) setTimeout(type, 55);
      else setTimeout(done, 2400);
    })();
  }

  (function next() {
    show(LINES[i], function () { i = (i + 1) % LINES.length; next(); });
  })();
})();
