<?php

/**
 * Builds the profile README's SVGs in assets/ from the portfolio's own data.
 *
 *   php scripts/build.php [path/to/portfolio]   (default: ../portfolio)
 *
 * Visual language: the WASCAL carnet (paper, ink borders, hard shadows,
 * Fraunces, Adinkra line icons, kente stripe) + the portfolio's « Métier »
 * weave (every project a row, every skill family a coloured thread, the
 * woven B). Every SVG is written twice: *-light.svg and *-dark.svg, picked by
 * <picture> in README.md according to the viewer's GitHub theme.
 */

$root = dirname(__DIR__);
$portfolio = rtrim($argv[1] ?? $root.'/../portfolio', '/');
require $portfolio.'/app/Support/Metier.php';

use App\Support\Metier;

$site = require $portfolio.'/config/site.php';
$projects = require $portfolio.'/config/projects.php';

// ── data: the weave, computed like Metier::build() (config only, no admin overrides) ──
$rows = [];
foreach ($projects['order'] as $slug) {
    $p = $projects['items'][$slug] ?? null;
    if (! $p) {
        continue;
    }
    $rows[] = $p + ['fam' => Metier::threadsOf($p['technologies'])];
}
$cols = [];
foreach (Metier::FAMILIES as $id => $f) {
    $cols[$id] = ['id' => $id, 'c' => $f[0], 'name' => $f[2], 'glyph' => $f[4], 'n' => count(array_filter($rows, fn ($p) => isset($p['fam'][$id])))];
}
$cols = array_values(array_filter($cols, fn ($c) => $c['n'] > 0));
usort($cols, fn ($a, $b) => $b['n'] <=> $a['n']);
foreach ($rows as &$p) {
    $dom = $cols[0];
    foreach ($cols as $f) {
        if (count($p['fam'][$f['id']] ?? []) > count($p['fam'][$dom['id']] ?? [])) {
            $dom = $f;
        }
    }
    $p['dom'] = $dom;
}
unset($p);
preg_match_all('~\d{4}~', implode(' ', array_column($rows, 'year')), $m);
$years = array_map('intval', $m[0]);
$techCount = count(array_unique(array_map([Metier::class, 'short'], array_merge(...array_column($rows, 'technologies')))));
$id = $site['site'];

// ── design tokens (wascal.birewa.com :root) ──
$THEMES = [
    'light' => [
        'paper' => '#fffaf0', 'cream' => '#fbf1dc', 'ink' => '#231a10', 'ink2' => '#5b4a35', 'line' => 'rgba(35,26,16,.14)',
        'border' => '#231a10', 'shadow' => '#231a10', 'accent' => '#b5440f', 'gold' => '#8a5f00', 'goldFill' => '#f2a900', 'onGold' => '#231a10',
        'adk' => '#f2a900', 'off' => 'rgba(35,26,16,.10)',
    ],
    'dark' => [
        'paper' => '#231a10', 'cream' => '#2e2317', 'ink' => '#f3ece0', 'ink2' => '#cabfae', 'line' => 'rgba(243,236,224,.16)',
        'border' => '#f3ece0', 'shadow' => '#f2a900', 'accent' => '#f2a900', 'gold' => '#f2a900', 'goldFill' => '#f2a900', 'onGold' => '#231a10',
        'adk' => '#f2a900', 'off' => 'rgba(243,236,224,.10)',
    ],
];
// thread colours too dark to read on the dark paper
$DARK_THREAD = ['#2a2420' => '#cabfae', '#3b2e7e' => '#8b7be0', '#0a6157' => '#22a896', '#1f4f9c' => '#5b8be0', '#7d3c6b' => '#c27aae', '#3f7a47' => '#62b06c'];
$KENTE = [['#f2a900', 15], ['#c5302b', 13], ['#1c7a45', 15], ['#1e3f8f', 14], ['#231a10', 7], ['#e85d1b', 16]];

// Adinkra symbols, as drawn on wascal.birewa.com (viewBox 0 0 100 100)
$ADK = [
    'adinkrahene' => ['M10 50a40 40 0 1 0 80 0a40 40 0 1 0 -80 0', 'M24 50a26 26 0 1 0 52 0a26 26 0 1 0 -52 0', 'M38 50a12 12 0 1 0 24 0a12 12 0 1 0 -24 0'],
    'akomantoso' => ['M41 50a9 9 0 1 0 18 0a9 9 0 1 0 -18 0', 'M50 36C38 28 36 20 40 16C44 12 49 14 50 20C51 14 56 12 60 16C64 20 62 28 50 36Z', 'M50 36V41', 'M64 50C72 38 80 36 84 40C88 44 86 49 80 50C86 51 88 56 84 60C80 64 72 62 64 50Z', 'M64 50H59', 'M50 64C62 72 64 80 60 84C56 88 51 86 50 80C49 86 44 88 40 84C36 80 38 72 50 64Z', 'M50 64V59', 'M36 50C28 62 20 64 16 60C12 56 14 51 20 50C14 49 12 44 16 40C20 36 28 38 36 50Z', 'M36 50H41'],
    'aya' => ['M50 92V10', 'M50 30L34 18', 'M50 30L66 18', 'M50 48L30 34', 'M50 48L70 34', 'M50 66L26 50', 'M50 66L74 50'],
    'nkyinkyim' => ['M26 8L56 29L26 50L56 71L26 92', 'M44 8L74 29L44 50L74 71L44 92', 'M26 8H44', 'M26 92H44'],
    'nsoromma' => ['M50 8L59.2 27.8L79.7 20.3L72.2 40.8L92 50L72.2 59.2L79.7 79.7L59.2 72.2L50 92L40.8 72.2L20.3 79.7L27.8 59.2L8 50L27.8 40.8L20.3 20.3L40.8 27.8Z', 'M38 50a12 12 0 1 0 24 0a12 12 0 1 0 -24 0'],
    'nyamedua' => ['M50 90V30', 'M50 30L30 12', 'M50 30L70 12', 'M50 54L26 40', 'M50 54L74 40', 'M32 90H68'],
    'neaonnim' => ['M50 24V76', 'M40 50H60', 'M50 24C40 12 18 14 18 30C18 42 34 44 35 35C36 29 28 27 26 32', 'M50 24C60 12 82 14 82 30C82 42 66 44 65 35C64 29 72 27 74 32', 'M50 76C40 88 18 86 18 70C18 58 34 56 35 65C36 71 28 73 26 68', 'M50 76C60 88 82 86 82 70C82 58 66 56 65 65C64 71 72 73 74 68'],
    'sankofa' => ['M50 90C20 68 8 46 21 30C31 18 46 22 50 35C54 22 69 18 79 30C92 46 80 68 50 90Z', 'M21 30C8 24 12 8 25 10C34 12 34 23 28 25', 'M79 30C92 24 88 8 75 10C66 12 66 23 72 25'],
];

const SERIF = "Fraunces, 'Iowan Old Style', Georgia, 'Times New Roman', serif";
const SANS = "'Hanken Grotesk', -apple-system, 'Segoe UI', Helvetica, Arial, sans-serif";

function e(string $s): string
{
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/** An Adinkra symbol at ($x,$y), $size px, with a highlight travelling along each stroke from $delay seconds. */
function adk(string $name, float $x, float $y, float $size, string $color, float $delay = 0, float $width = 4, float $opacity = 1): string
{
    global $ADK;
    $out = '<g transform="translate('.$x.' '.$y.') scale('.($size / 100).')" fill="none" stroke="'.$color.'" stroke-width="'.$width.'" stroke-linecap="round" stroke-linejoin="round" opacity="'.$opacity.'">';
    foreach ($ADK[$name] as $i => $d) {
        $out .= '<path d="'.$d.'"/><path class="trace" pathLength="1" stroke="#fffaf0" stroke-opacity=".55" style="animation-delay:'.round($delay + $i * .35, 2).'s" d="'.$d.'"/>';
    }

    return $out.'</g>';
}

/** A Métier family glyph (viewBox 0 0 24 24). */
function glyph(array $f, float $x, float $y, float $size, string $color): string
{
    return '<g transform="translate('.$x.' '.$y.') scale('.($size / 24).')" fill="none" stroke="'.$color.'" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$f['glyph'].'</g>';
}

function kente(float $x, float $y, float $w, float $h, float $scale = 1): string
{
    global $KENTE;
    $out = '';
    $cx = $x;
    while ($cx < $x + $w) {
        foreach ($KENTE as [$c, $bw]) {
            $bw *= $scale;
            $out .= '<rect x="'.round($cx, 1).'" y="'.$y.'" width="'.round(min($bw, $x + $w - $cx), 1).'" height="'.$h.'" fill="'.$c.'"/>';
            $cx += $bw;
            if ($cx >= $x + $w) {
                break;
            }
        }
    }

    return $out;
}

/** The card frame: hard offset shadow, ink border, paper fill. */
function card(array $t, float $x, float $y, float $w, float $h, float $r = 22, float $off = 9): string
{
    return '<rect x="'.($x + $off).'" y="'.($y + $off).'" width="'.$w.'" height="'.$h.'" rx="'.$r.'" fill="'.$t['shadow'].'"/>'
        .'<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="'.$r.'" fill="'.$t['paper'].'" stroke="'.$t['border'].'" stroke-width="3"/>';
}

function thread(string $c, string $theme): string
{
    global $DARK_THREAD;

    return $theme === 'dark' ? ($DARK_THREAD[$c] ?? $c) : $c;
}

/** Greedy word wrap on an estimated glyph width (no font metrics in SVG). */
function wrap(string $text, float $width, float $size, float $factor = .5, int $max = 99): array
{
    $per = (int) floor($width / ($size * $factor));
    $lines = explode("\n", wordwrap($text, $per, "\n", true));
    if (count($lines) > $max) {
        $lines = array_slice($lines, 0, $max);
        $lines[$max - 1] = rtrim($lines[$max - 1], ' ,.;:').'…';
    }

    return $lines;
}

function svg(float $w, float $h, string $title, string $body): string
{
    // The first frame is the finished design: every animation is a loop over
    // content that is already visible, so a frozen or reduced-motion render
    // (GitHub mobile, link previews, image proxies) still shows everything.
    $css = '.trace{stroke-dasharray:.08 .92;stroke-dashoffset:1;animation:trace 4s linear infinite}'
        .'.breathe{animation:breathe 3.6s ease-in-out infinite}'
        .'.spin{transform-box:fill-box;transform-origin:center;animation:spin 60s linear infinite}'
        .'.shuttle{animation:shuttle 7s cubic-bezier(.65,0,.35,1) infinite alternate}'
        .'@keyframes trace{to{stroke-dashoffset:0}}'
        .'@keyframes breathe{0%,100%{opacity:1}50%{opacity:.45}}'
        .'@keyframes spin{to{transform:rotate(360deg)}}'
        .'@keyframes shuttle{to{transform:translateY(var(--travel))}}'
        .'@media (prefers-reduced-motion:reduce){.trace{display:none}.breathe,.spin,.shuttle{animation:none}}';

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'" width="'.$w.'" height="'.$h.'" role="img" aria-label="'.e($title).'">'
        .'<title>'.e($title).'</title><style>'.$css.'</style>'.$body.'</svg>'."\n";
}

$out = [];

// ── header (1000 wide, so its type stays legible when GitHub scales it down on a phone) ──
foreach ($THEMES as $theme => $t) {
    $W = 1000;
    $H = 404;
    $b = card($t, 6, 6, $W - 24, $H - 24, 26, 10);
    $b .= '<clipPath id="k"><rect x="6" y="6" width="'.($W - 24).'" height="40" rx="26"/></clipPath><g clip-path="url(#k)">'.kente(6, 6, $W - 24, 16, 1.6).'</g>';
    $b .= '<line x1="6" y1="22" x2="'.($W - 18).'" y2="22" stroke="'.$t['border'].'" stroke-width="3"/>';

    // overline
    $b .= adk('adinkrahene', 50, 66, 34, $t['accent'], .2, 7)
        .'<text x="96" y="90" font-family="'.SANS.'" font-size="17" font-weight="800" letter-spacing="3" fill="'.$t['accent'].'">FULLSTACK ENGINEER · '.e(mb_strtoupper($id['city'].', '.$id['country'])).'</text>';
    // name
    $b .= '<text x="46" y="182" font-family="'.SERIF.'" font-size="84" font-weight="900" letter-spacing="-1.8" fill="'.$t['ink'].'">Birewa Audrey</text>';
    $b .= '<text x="46" y="268" font-family="'.SERIF.'" font-size="84" font-weight="900" letter-spacing="-1.8" fill="'.$t['ink'].'">Amona<tspan fill="'.$t['goldFill'].'">.</tspan></text>';
    // stat pills
    $stats = [[count($cols), 'skill families'], [$techCount, 'technologies'], [count($rows), 'projects']];
    $x = 50;
    foreach ($stats as [$n, $label]) {
        $nw = mb_strlen((string) $n) * 17 + 4;
        $w = $nw + mb_strlen($label) * 10 + 44;
        $b .= '<rect x="'.$x.'" y="304" width="'.$w.'" height="52" rx="26" fill="'.$t['paper'].'" stroke="'.$t['border'].'" stroke-width="2.5"/>'
            .'<text x="'.($x + 20).'" y="340" font-family="'.SERIF.'" font-size="28" font-weight="900" fill="'.$t['gold'].'">'.e((string) $n).'</text>'
            .'<text x="'.($x + 26 + $nw).'" y="337" font-family="'.SANS.'" font-size="18" font-weight="700" fill="'.$t['ink'].'">'.e($label).'</text>';
        $x += $w + 12;
    }

    // the woven B: lit points take the thread colours in column order
    $B = ['11110', '10001', '10001', '11110', '10001', '10001', '11110'];
    $cell = 31;
    $gap = 6;
    $bx = 752;
    $by = 72;
    $b .= '<g class="spin" opacity=".9">'.adk('nsoromma', 690, 44, 300, $t['line'], 0, 1.4).'</g>';
    $k = 0;
    foreach ($B as $r => $line) {
        foreach (str_split($line) as $c => $v) {
            $cx = $bx + $c * ($cell + $gap);
            $cy = $by + $r * ($cell + $gap);
            if ($v === '1') {
                $col = thread($cols[$k % count($cols)]['c'], $theme);
                $b .= '<rect class="breathe" style="animation-delay:'.round($k * .18, 2).'s" x="'.$cx.'" y="'.$cy.'" width="'.$cell.'" height="'.$cell.'" rx="6" fill="'.$col.'" stroke="'.$t['border'].'" stroke-width="2.5"/>';
                $k++;
            } else {
                $b .= '<rect x="'.$cx.'" y="'.$cy.'" width="'.$cell.'" height="'.$cell.'" rx="6" fill="none" stroke="'.$t['off'].'" stroke-width="2" stroke-dasharray="4 4"/>';
            }
        }
    }
    $b .= '<text x="'.($bx + 2.5 * $cell + 2 * $gap).'" y="'.($by + 7 * ($cell + $gap) + 20).'" text-anchor="middle" font-family="'.SANS.'" font-size="13" font-weight="800" letter-spacing="2.5" fill="'.$t['ink2'].'">ONE THREAD PER SKILL</text>';

    $out["header-$theme.svg"] = svg($W, $H, 'Birewa Audrey Amona, fullstack engineer in Mindelo, Cabo Verde', $b);
}

// ── section headings (800 wide for the same reason as the header) ──
$heads = [
    'skills' => ['neaonnim', '01 · SKILLS', 'What I work with.'],
    'now' => ['aya', '02 · NOW', 'What I’m building.'],
    'contact' => ['sankofa', '03 · SAY HELLO', 'Got a project to set afloat?'],
];
foreach ($heads as $key => [$sym, $over, $title]) {
    foreach ($THEMES as $theme => $t) {
        $b = adk($sym, 2, 4, 36, $t['accent'], .1, 6)
            .'<text x="50" y="30" font-family="'.SANS.'" font-size="17" font-weight="800" letter-spacing="3" fill="'.$t['accent'].'">'.e($over).'</text>'
            .'<text x="2" y="88" font-family="'.SERIF.'" font-size="44" font-weight="900" letter-spacing="-1" fill="'.$t['ink'].'">'.e($title).'</text>';
        $out["h-$key-$theme.svg"] = svg(800, 104, $title, $b);
    }
}

// ── skills: each family, how many projects use it, and its most used technologies ──
// Raw stack entries are written for case studies; these make them read as skills.
const ALIAS = [
    'spatie/laravel-permission' => 'Spatie Permission', 'HTML/CSS' => 'HTML / CSS', 'HTML' => 'HTML / CSS', 'CSS' => 'HTML / CSS',
    'DomPDF / FPDI' => 'DomPDF', 'Taches planifiees' => 'Scheduled jobs', 'Tâches planifiées' => 'Scheduled jobs',
    "File d'attente base de donnees" => 'Queues', 'Hébergement mutualisé' => 'Shared hosting', 'Laravel Echo + Pusher' => 'Echo + Pusher',
    'vite-plugin-pwa / Workbox' => 'Workbox', 'Claude Code plugin API' => 'Claude Code plugins', 'Google Gemini API' => 'Gemini API',
    'Apache / .htaccess' => 'Apache', 'DredgeView survey formats' => 'DredgeView formats', 'Dexie' => 'Dexie / IndexedDB',
];
const DROP = ['Slides Framework', 'dataclasses', 'gentrk CLI', 'localStorage', 'Font Awesome', 'SweetAlert2'];
const TIERS = [[10, 'Core', 'used in 10+ projects'], [5, 'Solid', 'used in 5 to 9 projects'], [0, 'Growing', 'used in a few projects']];

$skills = [];
foreach ($cols as $f) {
    $tech = [];
    foreach ($rows as $p) {
        $mine = array_map(fn ($x) => ALIAS[Metier::short($x)] ?? Metier::short($x), $p['fam'][$f['id']] ?? []);
        foreach (array_unique($mine) as $x) {
            // a language listed under « tests » is the project's language, not a testing skill
            if (! in_array($x, DROP, true) && ! ($f['id'] === 'tests' && $x === 'JavaScript')) {
                $tech[$x] = ($tech[$x] ?? 0) + 1;
            }
        }
    }
    arsort($tech);
    foreach (TIERS as $i => [$min]) {
        if ($f['n'] >= $min) {
            break;
        }
    }
    $skills[] = $f + ['tech' => array_slice(array_keys($tech), 0, 6), 'tier' => $i];
}

// the loom: one thread per skill family, as long as the number of projects using it
foreach ($THEMES as $theme => $t) {
    $W = 820;
    $rowH = 46;
    $x0 = 340;
    $len = $W - $x0 - 90;
    $max = max(array_column($skills, 'n'));
    $y = 34;
    $b = '';
    foreach ($skills as $k => $f) {
        if ($k === 0 || $f['tier'] !== $skills[$k - 1]['tier']) {
            [, $label, $hint] = TIERS[$f['tier']];
            $y += $k === 0 ? 0 : 14;
            $b .= '<text x="40" y="'.($y + 14).'" font-family="'.SANS.'" font-size="14" font-weight="800" letter-spacing="3" fill="'.$t['accent'].'">'.e(mb_strtoupper($label)).'<tspan font-weight="600" letter-spacing="1" fill="'.$t['ink2'].'">  ·  '.e($hint).'</tspan></text>';
            $y += 30;
        }
        $col = thread($f['c'], $theme);
        $cy = $y + $rowH / 2;
        $b .= '<rect x="40" y="'.($cy - 17).'" width="34" height="34" rx="8" fill="'.$t['cream'].'" stroke="'.$t['border'].'" stroke-width="2"/>'
            .glyph($f, 45, $cy - 12, 24, $col)
            .'<text x="88" y="'.($cy + 7).'" font-family="'.SANS.'" font-size="20" font-weight="800" fill="'.$t['ink'].'">'.e($f['name']).'</text>';
        // the warp underneath, then the thread woven over and under it
        $w = max(28, $len * $f['n'] / $max);
        $b .= '<line x1="'.$x0.'" y1="'.$cy.'" x2="'.($x0 + $len).'" y2="'.$cy.'" stroke="'.$t['line'].'" stroke-width="2" stroke-dasharray="2 6"/>'
            .'<rect x="'.$x0.'" y="'.($cy - 8).'" width="'.round($w, 1).'" height="16" rx="8" fill="'.$col.'" stroke="'.$t['border'].'" stroke-width="2"/>';
        for ($sx = $x0 + 22; $sx < $x0 + $w - 14; $sx += 26) {
            $b .= '<rect x="'.$sx.'" y="'.($cy - 8).'" width="5" height="16" fill="'.$t['paper'].'" opacity=".55"/>';
        }
        $b .= '<line class="trace" pathLength="1" style="animation-delay:'.round($k * .3, 2).'s" x1="'.($x0 + 6).'" y1="'.$cy.'" x2="'.round($x0 + $w - 6, 1).'" y2="'.$cy.'" stroke="#fffaf0" stroke-opacity=".7" stroke-width="5" stroke-linecap="round"/>'
            .'<text x="'.round($x0 + $w + 12, 1).'" y="'.($cy + 8).'" font-family="'.SERIF.'" font-size="22" font-weight="900" fill="'.$t['gold'].'">'.$f['n'].'</text>';
        $y += $rowH;
    }
    $H = $y + 34;
    $b = card($t, 6, 6, $W - 22, $H - 22, 22, 9).'<g transform="translate(0 6)">'.$b.'</g>';
    $out["loom-$theme.svg"] = svg($W, $H, count($skills).' skill families, each as long as the number of projects that use it', $b);
}

// the family icons used next to each skill in README.md
foreach ($skills as $f) {
    foreach ($THEMES as $theme => $t) {
        $b = '<rect x="2" y="2" width="36" height="36" rx="9" fill="'.$t['cream'].'" stroke="'.$t['border'].'" stroke-width="2"/>'.glyph($f, 8, 8, 24, thread($f['c'], $theme));
        $out["skill-{$f['id']}-$theme.svg"] = svg(40, 40, $f['name'], $b);
    }
}

// ── contact buttons ──
$icons = [
    'site' => '<circle cx="12" cy="12" r="9.5"/><path d="M2.5 12h19M12 2.5c3 3.2 3 15.8 0 19M12 2.5c-3 3.2-3 15.8 0 19"/>',
    'linkedin' => '<rect x="2.5" y="2.5" width="19" height="19" rx="4"/><path d="M7.5 10.5v6M7.5 7.5v.01M11.5 16.5v-6M11.5 13c0-1.6 1-2.5 2.4-2.5 1.5 0 2.1 1 2.1 2.6v3.4"/>',
    'email' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M3 6.5l9 6.5 9-6.5"/>',
    'github' => '<path d="M9 19c-4.3 1.4-4.3-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.2 4.2 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12.3 12.3 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/>',
];
$buttons = [
    'site' => [preg_replace('~^https?://~', '', $id['origin']), true],
    'linkedin' => ['LinkedIn', false],
    'email' => ['Email me', false],
];
foreach ($buttons as $key => [$label, $primary]) {
    foreach ($THEMES as $theme => $t) {
        $w = 92 + mb_strlen($label) * 11;
        $fill = $primary ? $t['goldFill'] : $t['paper'];
        $fg = $primary ? $t['onGold'] : $t['ink'];
        $b = '<rect x="7" y="7" width="'.($w - 12).'" height="56" rx="28" fill="'.$t['shadow'].'"/>'
            .'<rect x="2" y="2" width="'.($w - 12).'" height="56" rx="28" fill="'.$fill.'" stroke="'.$t['border'].'" stroke-width="2.5"/>'
            .'<g transform="translate(24 17) scale(1.08)" fill="none" stroke="'.$fg.'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$icons[$key].'</g>'
            .'<text x="62" y="37" font-family="'.SANS.'" font-size="19" font-weight="800" fill="'.$fg.'">'.e($label).'</text>';
        $out["btn-$key-$theme.svg"] = svg($w, 68, $label, $b);
    }
}

// ── footer: the kente band ──
foreach ($THEMES as $theme => $t) {
    $b = '<clipPath id="f"><rect x="0" y="0" width="1200" height="14" rx="7"/></clipPath><g clip-path="url(#f)">'.kente(0, 0, 1200, 14, 1.6).'</g>';
    $out["kente-$theme.svg"] = svg(1200, 14, 'Kente band', $b);
}

@mkdir($root.'/assets', 0755, true);
foreach (glob($root.'/assets/*.svg') as $old) {
    unlink($old);
}
foreach ($out as $name => $svg) {
    file_put_contents($root.'/assets/'.$name, $svg);
}
echo count($out)." SVGs written to assets/\n";

// ── README.md: the skills list between its markers, regenerated from the same data ──
$pic = fn (string $name, string $alt, string $attr) => '<picture><source media="(prefers-color-scheme: dark)" srcset="assets/'.$name.'-dark.svg"><img src="assets/'.$name.'-light.svg" alt="'.$alt.'" '.$attr.'></picture>';
$md = '';
foreach ($skills as $k => $f) {
    if ($k === 0 || $f['tier'] !== $skills[$k - 1]['tier']) {
        [, $label, $hint] = TIERS[$f['tier']];
        $md .= "\n**{$label}** <sub>· {$hint}</sub>\n";
    }
    $href = rtrim($id['origin'], '/').'/en/skills/'.Metier::SLUGS[$f['id']];
    $md .= "\n".$pic('skill-'.$f['id'], '', 'height="22"')." **[{$f['name']}]({$href})** <sub>· {$f['n']} projects</sub><br>\n"
        .implode(' ', array_map(fn ($x) => '`'.$x.'`', $f['tech']))."\n";
}
$readme = $root.'/README.md';
$text = file_get_contents($readme);
$new = preg_replace('~(<!-- skills:start[^>]*-->).*?(<!-- skills:end -->)~s', "$1\n".$md."\n$2", $text, 1, $n);
if ($n) {
    file_put_contents($readme, $new);
    echo "README.md skills block updated\n";
}
