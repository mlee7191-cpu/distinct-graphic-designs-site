# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Static marketing/portfolio site for Mark Lee / Distinct Graphic Designs (https://distinctgraphicdesigns.com.au/). Plain HTML + CSS with no build step, no package manager, no linter and no tests. Pages: `index.html` (home), `resume.html`, `404.html`, and four service pages, each a folder with an `index.html` so URLs have a trailing slash (`google-ads-management/`, `seo-and-content/`, `graphic-design/`, `web-and-digital/`).

## Running and deploying

- **Preview:** open the HTML files directly, or serve the folder (e.g. `python -m http.server`). `index.html` and `resume.html` use relative asset paths. `404.html` and the service pages use **root-absolute** paths (`/assets/...`), because they are served from other URLs (the server returns `404.html` at whatever URL was requested), so preview them over HTTP rather than `file://`.
- **Deploy:** pushing to the git remote triggers cPanel's Git deployment, which runs [.cpanel.yml](.cpanel.yml). It copies an **explicit list** of files to `/home/discomau/public_html/` (`index.html`, `resume.html`, `404.html`, `.htaccess`, `robots.txt`, `sitemap.xml`, `assets/` recursively, and each service folder). **A new top-level page, folder or file is not published until it is added to `.cpanel.yml`.** The deploy overwrites the live `.htaccess` with the repo copy.
- **Server and redirects:** the host is LiteSpeed (Apache-compatible `.htaccess`), with no CDN or proxy in front. [.htaccess](.htaccess) 301s http and www to `https://distinctgraphicdesigns.com.au` in one hop, and redirects `/index.html` and `/folder/index.html` to their clean URLs. Keep internal links root-relative to clean URLs (`/`, `/resume.html`, `/seo-and-content/`), never `index.html`.
- **WordPress leftover:** the WordPress rewrite block in `.htaccess` is **commented out** (WordPress is no longer used). It used to send every unknown URL to `/index.php`, where a coming-soon plugin returned 503. Unknown URLs now return a real 404 via `ErrorDocument 404 /404.html`. Do not re-enable the block unless asked. The WordPress files may still exist on the server (outside this repo).
- **SEO files:** when adding or renaming a page, update [sitemap.xml](sitemap.xml) (absolute https `<loc>`, and a `<lastmod>` that is the real date the page last changed; no `changefreq` or `priority`). [robots.txt](robots.txt) points at it. Every page needs a self-referencing canonical plus Open Graph and Twitter tags (`og:image` is `assets/og-image.jpg`). The service pages carry `Service` JSON-LD and the home page carries `ProfessionalService` JSON-LD; only use properties valid for the type (for example `serviceType` is valid on `Service` but not on `ProfessionalService`).

## Architecture

**Styling is layered across three places, and the layering matters:**

1. [assets/css/industry.css](assets/css/industry.css) is the only stylesheet the pages actually link. It is a copy of the "Industry" design-system (tokens as CSS custom properties, plus component classes such as `.btn`, `.card`, `.input`, `.tag`, `.nav`, `.blueprint`, `.duotone`). Its header comment says the Google Fonts `@import` for Barlow was deliberately removed.
2. Each page has an inline `<style>` block in `<head>` that overrides the design system. This is where it does the following:
   - declares the self-hosted **Avenir Next World** `@font-face` rules (from `assets/fonts/`)
   - re-points `--font-heading` and `--font-body` at Avenir, which is why Barlow is never rendered
   - defines the layout variables `--content-max` (1280px) and `--gutter` (side padding that grows on wide screens so content stays centred while backgrounds run full-bleed)
   - carries the page-specific rules: hero animation keyframes on `index.html`, `@media print` on `resume.html`, and rounded-corner overrides
3. Most layout is done with **inline `style=""` attributes** using `var(--gutter)`, `var(--color-*)` and `clamp()`. Follow that convention and reuse the tokens rather than hardcoding colours or spacing.

The `<style>` block (font-face, gutter variables) is **duplicated** in every page, as are the header, the footer (including the `<nav aria-label="Footer">` link list) and the GTM snippet. There is no build step, so a change to shared overrides or to the footer nav has to be made in all seven pages. The service pages were laid out to match `resume.html`'s header band.

**Orphaned files:** [assets/site.css](assets/site.css) (an earlier generated build of the Industry theme that still imports Barlow from Google Fonts) and [assets/site.js](assets/site.js) are **not referenced by any HTML page**. Editing them has no effect on the live site. The live JavaScript is inline at the bottom of each page, so make JS changes there:
- `index.html`: the enquiry form's submit handler builds a `mailto:mark@distinctgraphicdesigns.com.au` link from the form fields. There is no backend, and nothing is stored or sent from the site. The form's on-page note says this, so keep the two consistent.
- `resume.html`: `#print-btn` calls `window.print()`. The `.no-print` class hides the nav and buttons under `@media print`. (`site.js` uses a different id, `#print-resume`, which is one sign it is stale.)

**Third-party:** Google Tag Manager (`GTM-KLMJ655L`) is embedded in every page (head script plus `<noscript>` iframe right after `<body>`). Any new page needs the same snippet.

**Assets:**
- Logo variants: `logo-v2.svg` is used on light backgrounds and `logo-v2-rev.svg` on the dark hero and header. The older `distinct-logo*.svg` and `icon-favicon.svg` are unreferenced.
- `hero-collage.webp` is used in the About section of `index.html`. `hero-collage.png` is the original and is **kept but unreferenced** (2.4 MB, so do not link it). `og-image.jpg` (1200x630, cropped from the collage) is the social sharing image.
- Favicons: `assets/favicon.svg` (square viewBox, keep it square) is the source. `favicon.ico` (48x48), `favicon-192.png`, `favicon-512.png` and `apple-touch-icon.png` (180x180, white background) live in the **site root**, are generated from it, and are listed in `.cpanel.yml`. Every page's `<head>` links `favicon.ico`, the SVG, `favicon-192.png` and `apple-touch-icon.png`. Regenerate the PNG/ICO files if the SVG changes.
- Fonts: Regular and Medium are served as WOFF2 with the TTF as fallback in `@font-face`; the other weights are TTF only.
- `mark-lee-resume-2026.pdf` is the downloadable copy of the résumé linked from `resume.html`. Keep it in sync with the HTML résumé content.
