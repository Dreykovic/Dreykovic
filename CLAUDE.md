# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

This is the GitHub **profile README** repository for the `Dreykovic` account (`github.com/Dreykovic/Dreykovic`). GitHub renders `README.md` on the user's profile page. There is no lint or test tooling, and no CI.

- **Text first, images as frame.** All content that should be readable, searchable or copyable lives in Markdown: intro, project titles, descriptions, stacks, links and contact. SVGs carry identity only: the header, section headings, per-project skill signatures, the weave (folded inside `<details>`), contact buttons and the kente band. SVG text shrinks to a few pixels on a phone, so don't move content back into images.
- Every image is wrapped in `<picture>` so `*-dark.svg` or `*-light.svg` follows the viewer's GitHub theme. GitHub's sanitizer strips `<style>`, `<script>` and most attributes from the README itself, so all styling and animation lives inside the SVGs.
- The README's projects mirror the portfolio: the four featured ones (case studies on birewa.com), then the public repos under "Open source". Client repositories are private. When a new public repo is added, add it to the `SHOWN` list in `scripts/build.php` so its signature is generated.

## Generating the SVGs

```bash
php scripts/build.php                  # reads ../portfolio by default
php scripts/build.php /path/to/portfolio
```

- `assets/*.svg` is **generated, so don't edit it by hand**. The script reads the portfolio repo (birewa.com, Laravel): `config/site.php`, `config/projects.php`, and `app/Support/Metier.php` for the skill-family regexes, colours and glyphs. Admin overrides stored in the portfolio's database are not read. Re-run it after the portfolio's content changes. The header's figures (projects, skill threads, technologies) are computed, but the README's alt texts and prose repeat some of them by hand, so keep them in sync.
- The design combines wascal.birewa.com (paper/ink tokens, 3px ink borders, hard offset shadows, Fraunces, Adinkra line icons, kente band) with the portfolio's "Métier" weave and woven-B monogram. SVGs loaded through `<img>` cannot use web fonts, so text uses font stacks with Georgia and Helvetica fallbacks. Pill widths are estimated per character, so check the result after changing copy.
- The header is 1000 units wide and the headings 800, narrower than the weave (1200), so their type stays legible when scaled down on a phone.
- Animations are loops over content that is already fully drawn, never entry animations from `opacity:0` or an undrawn stroke. GitHub mobile, link previews and image proxies can freeze an SVG on its first frame.

## Previewing

`gh api markdown -f mode=markdown -f text="$(cat README.md)"` returns GitHub's own HTML for the README. Use `mode=markdown`, not `gfm`: `gfm` turns every newline into `<br>`, as in comments. Serve that HTML next to `assets/` and check it at about 880px and 390px wide, in both themes.
