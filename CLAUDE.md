# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repository is

This is the GitHub **profile README** repository for the `Dreykovic` account (`github.com/Dreykovic/Dreykovic`). GitHub renders `README.md` on the user's profile page. There is no lint or test tooling, and no CI.

- **A profile, not a mini portfolio.** The README presents the developer's *skills*, and projects stay on birewa.com. The user explicitly rejected a project-centred version. Sections: header, a short intro with availability, **01 Skills** (the loom, then the per-skill technologies folded in `<details>`), **02 Now** (current open-source work and research), **03 Say hello**.
- Skills are measured, not self-rated. Each family's length is the number of portfolio projects whose stack matches it (`Metier::threadsOf`), and its technologies are the most frequent ones in those stacks, cleaned up by the `ALIAS` and `DROP` lists in `scripts/build.php`. Tiers are set by `TIERS`: 10+ projects is Core, 5–9 is Solid, fewer is Growing.
- The skills list between `<!-- skills:start -->` and `<!-- skills:end -->` in `README.md` is **rewritten by `scripts/build.php`**. Edit the script, never that block. Each skill name links to its page on birewa.com (`/en/skills/{Metier::SLUGS}`).
- Every image is wrapped in `<picture>` so `*-dark.svg` or `*-light.svg` follows the viewer's GitHub theme. GitHub's sanitizer strips `<style>`, `<script>` and most attributes from the README itself, so all styling and animation lives inside the SVGs.

## Generating the SVGs

```bash
php scripts/build.php                  # reads ../portfolio by default
php scripts/build.php /path/to/portfolio
```

- `assets/*.svg` is **generated, so don't edit it by hand**. The script reads the portfolio repo (birewa.com, Laravel): `config/site.php`, `config/projects.php`, and `app/Support/Metier.php` for the skill-family regexes, colours and glyphs. Admin overrides stored in the portfolio's database are not read. Re-run it after the portfolio's content changes. The header's figures (projects, skill threads, technologies) are computed, but the README's alt texts and prose repeat some of them by hand, so keep them in sync.
- The design combines wascal.birewa.com (paper/ink tokens, 3px ink borders, hard offset shadows, Fraunces, Adinkra line icons, kente band) with the portfolio's "Métier" weave and woven-B monogram. SVGs loaded through `<img>` cannot use web fonts, so text uses font stacks with Georgia and Helvetica fallbacks. Pill widths are estimated per character, so check the result after changing copy.
- SVG widths are kept small (header 1000, loom 820, headings 800) so their type stays legible when GitHub scales them down on a phone.
- Animations are loops over content that is already fully drawn, never entry animations from `opacity:0` or an undrawn stroke. GitHub mobile, link previews and image proxies can freeze an SVG on its first frame.

## Previewing

`gh api markdown -f mode=markdown -f text="$(cat README.md)"` returns GitHub's own HTML for the README. Use `mode=markdown`, not `gfm`: `gfm` turns every newline into `<br>`, as in comments. Serve that HTML next to `assets/` and check it at about 880px and 390px wide, in both themes.
