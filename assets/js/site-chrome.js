/* Shared page behaviour (progressive enhancement; every page works without it).
   1. Sticky quick bar: once the page header scrolls out of view, a slim translucent bar with the logo and a "Let's talk" button slides in.
      It hides again while the enquiry form is on screen.
   2. Home page only: scroll reveals ([data-reveal]) and number count-ups ([data-count]).
   All motion is skipped under prefers-reduced-motion. */
(function () {
  var reduce = window.matchMedia && matchMedia("(prefers-reduced-motion: reduce)").matches;
  var header = document.querySelector("header.nav, header[role=banner]");

  if (header && "IntersectionObserver" in window) {
    var bar = document.createElement("div");
    bar.className = "sticky-bar no-print";
    bar.setAttribute("aria-hidden", "true");
    bar.innerHTML = '<a href="/" tabindex="-1" aria-label="Distinct Graphic Designs home"><img src="/assets/logo-v2-rev.svg" alt="" width="132" height="46"></a>' +
                    '<a class="btn" href="/#enquire" tabindex="-1">Let’s talk</a>';
    document.body.appendChild(bar);
    var pastHeader = false, atForm = false;
    function sync() {
      var show = pastHeader && !atForm;
      bar.classList.toggle("is-visible", show);
      bar.setAttribute("aria-hidden", show ? "false" : "true");
      Array.prototype.forEach.call(bar.querySelectorAll("a"), function (a) { a.tabIndex = show ? 0 : -1; });
    }
    new IntersectionObserver(function (e) { pastHeader = !e[0].isIntersecting && e[0].boundingClientRect.top < 0; sync(); }).observe(header);
    var form = document.getElementById("enquire");
    if (form) new IntersectionObserver(function (e) { atForm = e[0].isIntersecting; sync(); }, { threshold: 0.25 }).observe(form);
  }

  var reveals = document.querySelectorAll("[data-reveal]"), counts = document.querySelectorAll("[data-count]");
  if (!("IntersectionObserver" in window) || reduce) {
    Array.prototype.forEach.call(reveals, function (el) { el.classList.add("is-in"); });
    return;
  }
  var io = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (!en.isIntersecting) return;
      io.unobserve(en.target);
      en.target.classList.add("is-in");
    });
  }, { threshold: 0.15, rootMargin: "0px 0px -6% 0px" });
  Array.prototype.forEach.call(reveals, function (el) { io.observe(el); });

  function countUp(el) {
    var to = parseFloat(el.getAttribute("data-count")), t0 = null, dur = 1100;
    function tick(t) {
      if (t0 === null) t0 = t;
      var p = Math.min((t - t0) / dur, 1), eased = 1 - Math.pow(1 - p, 4);
      el.textContent = Math.round(to * eased);
      if (p < 1) requestAnimationFrame(tick);
    }
    el.textContent = "0";
    requestAnimationFrame(tick);
    // Backstop: if animation frames are paused (background tab), still land on the real number.
    setTimeout(function () { el.textContent = Math.round(to); }, dur + 400);
  }
  var cio = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) { if (en.isIntersecting) { cio.unobserve(en.target); countUp(en.target); } });
  }, { threshold: 0.6 });
  Array.prototype.forEach.call(counts, function (el) { cio.observe(el); });
})();
