# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Static marketing/portfolio site for Mark Lee / Distinct Graphic Designs (https://distinctgraphicdesigns.com.au/). Plain HTML + CSS with no build step, no package manager, no linter and no tests. Two pages: `index.html` (home) and `resume.html`.

## Running and deploying

- **Preview:** open the HTML files directly, or serve the folder (e.g. `python -m http.server`). Pages use relative asset paths, so either works.
- **Deploy:** pushing to the git remote triggers cPanel's Git deployment, which runs [.cpanel.yml](.cpanel.yml). It copies an **explicit list** of files to `/home/discomau/public_html/` (`index.html`, `resume.html`, `.htaccess`, `robots.txt`, `sitemap.xml`, and `assets/` recursively). **A new top-level page or file is not published until it is added to `.cpanel.yml`.**
- **Hosting quirk:** [.htaccess](.htaccess) contains a WordPress rewrite block (unknown paths fall through to `/index.php`), and `DirectoryIndex index.html index.php`. This is likely left over from a WordPress install on the same host, so leave it alone unless asked.
- **SEO files:** when adding or renaming a page, update [sitemap.xml](sitemap.xml) (`<loc>` and `<lastmod>`) as well. [robots.txt](robots.txt) points at it.

## Architecture

**Styling is layered across three places, and the layering matters:**

1. [assets/css/industry.css](assets/css/industry.css) is the only stylesheet the pages actually link. It is a copy of the "Industry" design-system (tokens as CSS custom properties, plus component classes such as `.btn`, `.card`, `.input`, `.tag`, `.nav`, `.blueprint`, `.duotone`). Its header comment says the Google Fonts `@import` for Barlow was deliberately removed.
2. Each page has an inline `<style>` block in `<head>` that overrides the design system. This is where it does the following:
   - declares the self-hosted **Avenir Next World** `@font-face` rules (from `assets/fonts/`)
   - re-points `--font-heading` and `--font-body` at Avenir, which is why Barlow is never rendered
   - defines the layout variables `--content-max` (1280px) and `--gutter` (side padding that grows on wide screens so content stays centred while backgrounds run full-bleed)
   - carries the page-specific rules: hero animation keyframes on `index.html`, `@media print` on `resume.html`, and rounded-corner overrides
3. Most layout is done with **inline `style=""` attributes** using `var(--gutter)`, `var(--color-*)` and `clamp()`. Follow that convention and reuse the tokens rather than hardcoding colours or spacing.

The `<style>` block (font-face, gutter variables) is **duplicated** in `index.html` and `resume.html`. A change to shared overrides needs to be made in both.

**Orphaned files:** [assets/site.css](assets/site.css) (an earlier generated build of the Industry theme that still imports Barlow from Google Fonts) and [assets/site.js](assets/site.js) are **not referenced by any HTML page**. Editing them has no effect on the live site. The live JavaScript is inline at the bottom of each page, so make JS changes there:
- `index.html`: the enquiry form's submit handler builds a `mailto:mark@distinctgraphicdesigns.com.au` link from the form fields. There is no backend, and nothing is stored or sent from the site. The form's on-page note says this, so keep the two consistent.
- `resume.html`: `#print-btn` calls `window.print()`. The `.no-print` class hides the nav and buttons under `@media print`. (`site.js` uses a different id, `#print-resume`, which is one sign it is stale.)

**Third-party:** Google Tag Manager (`GTM-KLMJ655L`) is embedded in `index.html` only (head script plus `<noscript>` iframe). `resume.html` has none.

**Assets:**
- Logo variants: `logo-v2.svg` is used on light backgrounds and `logo-v2-rev.svg` on the dark hero and header. The older `distinct-logo*.svg` and `icon-favicon.svg` are unreferenced.
- `hero-collage.png` is used in the About section of `index.html`.
- `mark-lee-resume-2026.pdf` is the downloadable copy of the résumé linked from `resume.html`. Keep it in sync with the HTML résumé content.
