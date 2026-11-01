# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

This is the GitHub **profile README** repository for the `Dreykovic` account (`github.com/Dreykovic/Dreykovic`). GitHub renders `README.md` on the user's profile page. The repo has no build, lint, or test tooling.

- `README.md` is the profile page. It is made almost entirely of SVG images from `assets/`, each wrapped in `<picture>` so `*-dark.svg` or `*-light.svg` follows the viewer's GitHub theme. GitHub's sanitizer strips `<style>`, `<script>` and most attributes from the README itself, so all styling and animation lives inside the SVGs.
- `assets/*.svg` is **generated, so don't edit it by hand**. Run `php scripts/build.php [path/to/portfolio]` (default `../portfolio`, the birewa.com Laravel repo). The script reads that repo's `config/site.php` and `config/projects.php`, plus `app/Support/Metier.php` for the skill-family regexes, colours and glyphs. It rebuilds every SVG: header, section headings, the four featured project cards, the weave (projects × skill threads) and the contact buttons. Re-run it after the portfolio's content changes. Admin overrides stored in the portfolio's database are not read.
- The design combines wascal.birewa.com (paper/ink tokens, 3px ink borders, hard offset shadows, Fraunces, Adinkra line icons, kente band) with the portfolio's "Métier" weave and woven-B monogram. SVGs loaded through `<img>` cannot use web fonts, so text uses font stacks with Georgia and Helvetica fallbacks. Text wrapping and pill widths are estimated per character, so check the result after changing copy.
- Animations are loops over content that is already fully drawn, never entry animations from `opacity:0` or an undrawn stroke. GitHub mobile, link previews and image proxies can freeze an SVG on its first frame.
- `.github/workflows/snake.yaml` generates a contribution-graph "snake" animation with `Platane/snk/svg-only@v3` (using the `github-dark` palette). It force-pushes `dist/snake.svg` to the **`output` branch** using `crazy-max/ghaction-github-pages`. The README no longer embeds the snake. If it is brought back, the embed must reference that branch, e.g. `https://raw.githubusercontent.com/Dreykovic/Dreykovic/output/snake.svg`. Never commit generated SVGs to `main`.

## Workflow notes

- The workflow runs on pushes to `main`, on `workflow_dispatch`, and on a cron schedule. The cron expression `* */12 * * *` fires every minute during hours 00 and 12 UTC, not once every 12 hours. `0 */12 * * *` is probably what was meant.
- Trigger it manually with:

  ```bash
  gh workflow run snake.yaml
  ```
