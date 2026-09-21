/* Shared analytics for the home page and the four service pages (resume.html keeps its own inline PostHog script).
   1. Remembers where this visit started (first landing page, referrer, UTM and Google click ID) for the browser session, so the
      enquiry form can send it along with the enquiry.
   2. Loads PostHog (US cloud): pageviews only, autocapture and session replay off, anonymous until someone sends an enquiry.
   3. Exposes window.dgd so the enquiry form can read the attribution and send events to PostHog and dataLayer (GTM/GA4).
   Every failure is swallowed, so tracking can never break a page or the form.
   Visit any page with #notrack once on a device to keep your own visits and test enquiries out of the data; #track undoes it.
   (Same switch as resume.html.) */
(function () {
  var PH_KEY = "phc_B4DjK5WmNTiAjv6Q9utUf8H6teNzaJ7cdbLruM3CJLNk", PH_HOST = "https://us.i.posthog.com";
  var ATTR_KEY = "dgd_attr", off = false;

  try {
    if (location.hash === "#notrack") localStorage.setItem("ph_notrack", "1");
    if (location.hash === "#track") localStorage.removeItem("ph_notrack");
    off = localStorage.getItem("ph_notrack") === "1";
  } catch (e) {}

  // First touch of this browser session. Kept even when analytics is off, because it is form data, not tracking.
  try {
    if (!sessionStorage.getItem(ATTR_KEY)) {
      var q = new URLSearchParams(location.search), a = { landing_page: location.pathname, referrer: document.referrer || "" };
      ["utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "gclid"].forEach(function (k) {
        if (q.get(k)) a[k] = q.get(k);
      });
      sessionStorage.setItem(ATTR_KEY, JSON.stringify(a));
    }
  } catch (e) {}

  window.dgd = {
    attribution: function () {
      try { return JSON.parse(sessionStorage.getItem(ATTR_KEY)) || {}; } catch (e) { return {}; }
    },
    distinctId: function () {
      try { var id = window.posthog && window.posthog.get_distinct_id(); return typeof id === "string" ? id : ""; } catch (e) { return ""; }
    },
    identify: function (id, props) {
      if (off) return;
      try { window.posthog.identify(id, props); } catch (e) {}
    },
    track: function (event, props) {
      if (off) return;
      try { window.posthog.capture(event, props); } catch (e) {}
      try { (window.dataLayer = window.dataLayer || []).push({ event: event }); } catch (e) {} // no name or email goes to GTM/GA4
    }
  };
  if (off) return;

  try {
    // PostHog's standard async loader: queues calls in a stub until array.js has loaded from PostHog's CDN.
    !function (d, w) {
      var p = w.posthog = w.posthog || [];
      if (p.__SV || p.__loaded) return;
      p._i = [];
      p.init = function (token, config, name) {
        var s = d.createElement("script"), first = d.getElementsByTagName("script")[0], t = p;
        s.type = "text/javascript"; s.crossOrigin = "anonymous"; s.async = true;
        s.src = config.api_host.replace(".i.posthog.com", "-assets.i.posthog.com") + "/static/array.js";
        first.parentNode.insertBefore(s, first);
        if (name !== undefined) { t = p[name] = []; } else { name = "posthog"; }
        t.people = t.people || [];
        t.toString = function (x) { return "posthog" + (name !== "posthog" ? "." + name : "") + (x ? "" : " (stub)"); };
        t.people.toString = function () { return t.toString(1) + ".people (stub)"; };
        ("capture identify setPersonProperties register register_once unregister reset set_config " +
         "opt_out_capturing opt_in_capturing has_opted_out_capturing debug get_distinct_id people.set people.set_once")
          .split(" ").forEach(function (m) {
            var o = t, parts = m.split(".");
            if (parts.length === 2) { o = t[parts[0]]; m = parts[1]; }
            o[m] = function () { o.push([m].concat(Array.prototype.slice.call(arguments, 0))); };
          });
        p._i.push([token, config, name]);
      };
      p.__SV = 1;
    }(document, window);
    window.posthog.init(PH_KEY, {
      api_host: PH_HOST,
      ui_host: "https://us.posthog.com",
      person_profiles: "identified_only", // a person profile is only created when someone sends an enquiry and is identified by email
      autocapture: false,                 // only pageviews and the explicit enquiry_submitted event are recorded
      disable_session_recording: true,
      respect_dnt: true
    });
  } catch (e) {}
})();
