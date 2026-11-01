<?php

/**
 * Builds the profile README's SVGs in assets/ from the portfolio's own data,
 * and rewrites README.md's skills list between its markers.
 *
 *   php scripts/build.php [path/to/portfolio]   (default: ../portfolio)
 *
 * Visual language: GitHub's own design system (Primer): its colour tokens,
 * system font stack, 1px borders, 6/12px radii, no shadows. The one personal
 * touch is the B monogram drawn as contribution-graph cells. Every SVG is
 * written twice, *-light.svg and *-dark.svg, picked by <picture> in
 * README.md according to the viewer's GitHub theme.
 */

$root = dirname(__DIR__);
$portfolio = rtrim($argv[1] ?? $root.'/../portfolio', '/');
require $portfolio.'/app/Support/Metier.php';

use App\Support\Metier;

$site = require $portfolio.'/config/site.php';
$projects = require $portfolio.'/config/projects.php';
$id = $site['site'];

// ── data: projects and their skill families, as Metier::build() finds them (config only, no admin overrides) ──
$rows = [];
foreach ($projects['order'] as $slug) {
    if ($p = $projects['items'][$slug] ?? null) {
        $rows[] = $p + ['fam' => Metier::threadsOf($p['technologies'])];
    }
}
$techCount = count(array_unique(array_map([Metier::class, 'short'], array_merge(...array_column($rows, 'technologies')))));

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
foreach (Metier::FAMILIES as $fid => $f) {
    $n = 0;
    $tech = [];
    foreach ($rows as $p) {
        if (! isset($p['fam'][$fid])) {
            continue;
        }
        $n++;
        foreach (array_unique(array_map(fn ($x) => ALIAS[Metier::short($x)] ?? Metier::short($x), $p['fam'][$fid])) as $x) {
            // a language listed under « tests » is the project's language, not a testing skill
            if (! in_array($x, DROP, true) && ! ($fid === 'tests' && $x === 'JavaScript')) {
                $tech[$x] = ($tech[$x] ?? 0) + 1;
            }
        }
    }
    if ($n === 0) {
        continue;
    }
    arsort($tech);
    foreach (TIERS as $tier => [$min]) {
        if ($n >= $min) {
            break;
        }
    }
    $skills[] = ['id' => $fid, 'name' => $f[2], 'glyph' => $f[4], 'n' => $n, 'tier' => $tier, 'tech' => array_slice(array_keys($tech), 0, 6)];
}
usort($skills, fn ($a, $b) => $b['n'] <=> $a['n']);

// ── Primer tokens (primer/primitives, light and dark default themes) ──
$THEMES = [
    'light' => [
        'canvas' => '#ffffff', 'subtle' => '#f6f8fa', 'border' => '#d1d9e0', 'fg' => '#1f2328', 'muted' => '#59636e',
        'accent' => '#0969da', 'success' => '#1a7f37', 'btnBg' => '#f6f8fa', 'primary' => '#1f883d', 'onPrimary' => '#ffffff',
        'track' => '#eff2f5', 'graph' => ['#ebedf0', '#9be9a8', '#40c463', '#30a14e', '#216e39'],
    ],
    'dark' => [
        'canvas' => '#0d1117', 'subtle' => '#151b23', 'border' => '#3d444d', 'fg' => '#f0f6fc', 'muted' => '#9198a1',
        'accent' => '#4493f8', 'success' => '#3fb950', 'btnBg' => '#212830', 'primary' => '#238636', 'onPrimary' => '#ffffff',
        'track' => '#262c36', 'graph' => ['#151b23', '#0e4429', '#006d32', '#26a641', '#39d353'],
    ],
];
// one Primer scale colour per skill family: [light, dark]
const HUES = [
    'front' => ['#0969da', '#4493f8'], 'ui' => ['#1b7c83', '#39c5cf'], 'data' => ['#1a7f37', '#3fb950'], 'back' => ['#bc4c00', '#db6d28'],
    'secu' => ['#8250df', '#ab7df8'], 'devops' => ['#59636e', '#9198a1'], 'tests' => ['#bf3989', '#db61a2'], 'docs' => ['#9a6700', '#d29922'],
    'tools' => ['#c4432b', '#f78166'], 'auto' => ['#cf222e', '#f85149'], 'offline' => ['#6639ba', '#d2a8ff'],
];

const SANS = "-apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans', Helvetica, Arial, sans-serif";
const MONO = "ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, 'Liberation Mono', monospace";

function e(string $s): string
{
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function hue(string $fid, string $theme): string
{
    return HUES[$fid][$theme === 'dark' ? 1 : 0];
}

/** A Métier family glyph (viewBox 0 0 24 24), drawn as a 1.5px line icon like Octicons. */
function glyph(string $paths, float $x, float $y, float $size, string $color): string
{
    return '<g transform="translate('.$x.' '.$y.') scale('.($size / 24).')" fill="none" stroke="'.$color.'" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$paths.'</g>';
}

/** A Primer Label / Counter: rounded pill, 1px border. Returns [svg, width]. */
function label(array $t, float $x, float $y, string $text, ?string $dot = null, float $size = 15): array
{
    $w = mb_strlen($text) * $size * .52 + 26 + ($dot ? 16 : 0);
    $h = $size + 14;
    $svg = '<rect x="'.$x.'" y="'.$y.'" width="'.round($w).'" height="'.$h.'" rx="'.($h / 2).'" fill="none" stroke="'.($dot ?? $t['border']).'" stroke-width="1"/>';
    $tx = $x + 13;
    if ($dot) {
        $svg .= '<circle class="pulse" cx="'.($x + 16).'" cy="'.($y + $h / 2).'" r="4.5" fill="'.$dot.'"/>';
        $tx += 16;
    }
    $svg .= '<text x="'.$tx.'" y="'.($y + $h / 2 + $size * .36).'" font-family="'.SANS.'" font-size="'.$size.'" font-weight="500" fill="'.($dot ?? $t['muted']).'">'.e($text).'</text>';

    return [$svg, round($w)];
}

function svg(float $w, float $h, string $title, string $body): string
{
    // Loops only, over content already drawn: a frozen first frame (GitHub
    // mobile, link previews, image proxies) still shows the finished image.
    $css = '.pulse{animation:pulse 2.4s ease-in-out infinite}.cell{animation:cell 4.8s ease-in-out infinite}'
        .'@keyframes pulse{50%{opacity:.35}}@keyframes cell{50%{opacity:.55}}'
        .'@media (prefers-reduced-motion:reduce){.pulse,.cell{animation:none}}';

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$h.'" width="'.$w.'" height="'.$h.'" role="img" aria-label="'.e($title).'">'
        .'<title>'.e($title).'</title><style>'.$css.'</style>'.$body.'</svg>'."\n";
}

$out = [];

// ── header: a Primer Box, with the B monogram as contribution-graph cells ──
foreach ($THEMES as $theme => $t) {
    $W = 800;
    $H = 260;
    $b = '<rect x=".5" y=".5" width="'.($W - 1).'" height="'.($H - 1).'" rx="12" fill="'.$t['canvas'].'" stroke="'.$t['border'].'"/>';
    $b .= '<text x="32" y="78" font-family="'.SANS.'" font-size="38" font-weight="600" fill="'.$t['fg'].'">'.e($id['name']).'</text>';
    $b .= '<text x="32" y="114" font-family="'.SANS.'" font-size="20" fill="'.$t['muted'].'">Fullstack engineer · '.e($id['city'].', '.$id['country']).'</text>';
    $b .= '<text x="32" y="148" font-family="'.MONO.'" font-size="15" fill="'.$t['muted'].'">'.e(implode(' · ', $id['coreTech'])).'</text>';
    [$s, $w] = label($t, 32, 180, 'Open to work', $t['success']);
    $b .= $s;
    $x = 32 + $w + 10;
    foreach ([count($skills).' skill families', $techCount.' technologies', count($rows).' projects'] as $text) {
        [$s, $w] = label($t, $x, 180, $text);
        $b .= $s;
        $x += $w + 10;
    }

    // B on a 5 × 7 grid, like a week column of the contribution calendar
    $B = ['11110', '10001', '10001', '11110', '10001', '10001', '11110'];
    $cell = 19;
    $gap = 4;
    $bx = $W - 32 - 5 * $cell - 4 * $gap;
    $by = 32;
    $k = 0;
    foreach ($B as $r => $line) {
        foreach (str_split($line) as $c => $v) {
            $level = $v === '1' ? 2 + ($k++ * 2 + $r) % 3 : 0;
            $anim = $level ? ' class="cell" style="animation-delay:'.round(($r + $c) * .3, 1).'s"' : '';
            $b .= '<rect'.$anim.' x="'.($bx + $c * ($cell + $gap)).'" y="'.($by + $r * ($cell + $gap)).'" width="'.$cell.'" height="'.$cell.'" rx="4" fill="'.$t['graph'][$level].'" stroke="'.($theme === 'light' ? 'rgba(31,35,40,.06)' : 'rgba(240,246,252,.05)').'"/>';
        }
    }
    // the calendar's own legend
    $ly = $by + 7 * ($cell + $gap) + 10;
    $b .= '<text x="'.($bx - 6).'" y="'.($ly + 11).'" text-anchor="end" font-family="'.SANS.'" font-size="12" fill="'.$t['muted'].'">Less</text>';
    foreach ($t['graph'] as $i => $c) {
        $b .= '<rect x="'.($bx + $i * 16).'" y="'.$ly.'" width="12" height="12" rx="2.5" fill="'.$c.'"/>';
    }
    $b .= '<text x="'.($bx + 5 * 16 + 2).'" y="'.($ly + 11).'" font-family="'.SANS.'" font-size="12" fill="'.$t['muted'].'">More</text>';

    $out["header-$theme.svg"] = svg($W, $H, $id['name'].', fullstack engineer in '.$id['city'].', '.$id['country'].'. Open to work.', $b);
}

// ── skills: a Primer Box list, one row per family, a progress bar as long as the number of projects using it ──
foreach ($THEMES as $theme => $t) {
    $W = 640;
    $rowH = 40;
    $max = max(array_column($skills, 'n'));
    $x0 = 318;
    $len = $W - $x0 - 64;
    $head = 52;
    $b = '';
    $y = $head;
    foreach ($skills as $k => $f) {
        if ($k === 0 || $f['tier'] !== $skills[$k - 1]['tier']) {
            [, $tier, $hint] = TIERS[$f['tier']];
            $b .= '<rect x="1" y="'.$y.'" width="'.($W - 2).'" height="30" fill="'.$t['subtle'].'"/>'
                .'<line x1="0" y1="'.$y.'" x2="'.$W.'" y2="'.$y.'" stroke="'.$t['border'].'"/>'
                .'<text x="20" y="'.($y + 20).'" font-family="'.SANS.'" font-size="13" font-weight="600" fill="'.$t['fg'].'">'.e($tier).'<tspan font-weight="400" fill="'.$t['muted'].'">  ·  '.e($hint).'</tspan></text>';
            $y += 30;
        }
        $cy = $y + $rowH / 2;
        $col = hue($f['id'], $theme);
        $b .= '<line x1="0" y1="'.$y.'" x2="'.$W.'" y2="'.$y.'" stroke="'.$t['border'].'"/>'
            .glyph($f['glyph'], 20, $cy - 10, 20, $col)
            .'<text x="52" y="'.($cy + 6).'" font-family="'.SANS.'" font-size="16" font-weight="600" fill="'.$t['fg'].'">'.e($f['name']).'</text>'
            .'<rect x="'.$x0.'" y="'.($cy - 4).'" width="'.$len.'" height="8" rx="4" fill="'.$t['track'].'"/>'
            .'<rect x="'.$x0.'" y="'.($cy - 4).'" width="'.round(max(8, $len * $f['n'] / $max), 1).'" height="8" rx="4" fill="'.$col.'"/>'
            .'<text x="'.($W - 20).'" y="'.($cy + 5).'" text-anchor="end" font-family="'.SANS.'" font-size="14" font-weight="600" fill="'.$t['muted'].'">'.$f['n'].'</text>';
        $y += $rowH;
    }
    $H = $y + 1;
    $frame = '<clipPath id="box"><rect x=".5" y=".5" width="'.($W - 1).'" height="'.($H - 1).'" rx="12"/></clipPath>'
        .'<rect x=".5" y=".5" width="'.($W - 1).'" height="'.($H - 1).'" rx="12" fill="'.$t['canvas'].'"/>'
        .'<g clip-path="url(#box)"><rect x="0" y="0" width="'.$W.'" height="'.$head.'" fill="'.$t['subtle'].'"/>'.$b.'</g>'
        .'<rect x=".5" y=".5" width="'.($W - 1).'" height="'.($H - 1).'" rx="12" fill="none" stroke="'.$t['border'].'"/>'
        .'<text x="20" y="32" font-family="'.SANS.'" font-size="15" font-weight="600" fill="'.$t['fg'].'">Skills, measured across '.count($rows).' shipped projects</text>';
    [$s] = label($t, $W - 20 - (mb_strlen((string) count($skills)) * 8 + 26), 13, (string) count($skills), null, 13);
    $out["skills-$theme.svg"] = svg($W, $H, count($skills).' skill families, each with a bar as long as the number of projects that use it', $frame.$s);
}

// the family icons next to each skill in README.md's list
foreach ($skills as $f) {
    foreach ($THEMES as $theme => $t) {
        $out["skill-{$f['id']}-$theme.svg"] = svg(24, 24, $f['name'], glyph($f['glyph'], 2, 2, 20, hue($f['id'], $theme)));
    }
}

// ── contact: Primer buttons with Octicon-style icons ──
$icons = [
    'site' => '<circle cx="12" cy="12" r="9.5"/><path d="M2.5 12h19M12 2.5c3 3.2 3 15.8 0 19M12 2.5c-3 3.2-3 15.8 0 19"/>',
    'linkedin' => '<rect x="2.5" y="2.5" width="19" height="19" rx="4"/><path d="M7.5 10.5v6M7.5 7.5v.01M11.5 16.5v-6M11.5 13c0-1.6 1-2.5 2.4-2.5 1.5 0 2.1 1 2.1 2.6v3.4"/>',
    'email' => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M3 6.5l9 6.5 9-6.5"/>',
];
$buttons = ['site' => [preg_replace('~^https?://~', '', $id['origin']), true], 'linkedin' => ['LinkedIn', false], 'email' => ['Email', false]];
foreach ($buttons as $key => [$text, $primary]) {
    foreach ($THEMES as $theme => $t) {
        $w = 58 + mb_strlen($text) * 8.6;
        $fg = $primary ? $t['onPrimary'] : $t['fg'];
        $b = '<rect x=".5" y=".5" width="'.($w - 1).'" height="35" rx="6" fill="'.($primary ? $t['primary'] : $t['btnBg']).'" stroke="'.($primary ? 'rgba(31,35,40,.15)' : $t['border']).'"/>'
            .glyph($icons[$key], 14, 10, 16, $primary ? $fg : $t['muted'])
            .'<text x="40" y="23" font-family="'.SANS.'" font-size="14" font-weight="500" fill="'.$fg.'">'.e($text).'</text>';
        $out["btn-$key-$theme.svg"] = svg($w, 36, $text, $b);
    }
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
$pic = fn (string $name, string $attr) => '<picture><source media="(prefers-color-scheme: dark)" srcset="assets/'.$name.'-dark.svg"><img src="assets/'.$name.'-light.svg" alt="" '.$attr.'></picture>';
$md = '';
foreach ($skills as $k => $f) {
    if ($k === 0 || $f['tier'] !== $skills[$k - 1]['tier']) {
        [, $tier, $hint] = TIERS[$f['tier']];
        $md .= "\n#### {$tier} <sub>· {$hint}</sub>\n";
    }
    $href = rtrim($id['origin'], '/').'/en/skills/'.Metier::SLUGS[$f['id']];
    $md .= "\n".$pic('skill-'.$f['id'], 'height="16"')." **[{$f['name']}]({$href})** · <sub>{$f['n']} projects</sub><br>\n"
        .implode(' ', array_map(fn ($x) => '`'.$x.'`', $f['tech']))."\n";
}
$readme = $root.'/README.md';
$new = preg_replace('~(<!-- skills:start[^>]*-->).*?(<!-- skills:end -->)~s', "$1\n".$md."\n$2", file_get_contents($readme), 1, $n);
if ($n) {
    file_put_contents($readme, $new);
    echo "README.md skills block updated\n";
}
