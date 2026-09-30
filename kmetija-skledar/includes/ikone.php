<?php
// Ilustracije izdelkov (dokler kmetija nima pravih fotografij)
function product_art(int $idCategory, int $size = 180): string
{
    if ($idCategory === 1) { // bučno olje
        $w = (int) round($size * 84 / 180);
        return '<svg width="' . $w . '" height="' . $size . '" viewBox="0 0 84 180" aria-hidden="true">'
            . '<rect x="33" y="2" width="18" height="16" rx="3" fill="#C5D19A" stroke="#111" stroke-width="2"/>'
            . '<path d="M35 18V40C35 48 10 54 10 78V168C10 173 13 176 18 176H66C71 176 74 173 74 168V78C74 54 49 48 49 40V18Z" fill="#111"/>'
            . '<rect x="18" y="100" width="48" height="48" rx="4" fill="#C5D19A"/>'
            . '<path d="M42 112C42 112 32 124 32 130C32 135.5 36.5 139 42 139C47.5 139 52 135.5 52 130C52 124 42 112 42 112Z" fill="#111"/></svg>';
    }
    if ($idCategory === 2) { // bučnice
        $seeds = [[50, 42, -25], [76, 40, 10], [102, 42, -10], [128, 42, 25], [64, 28, 15], [90, 26, -35], [116, 28, 5]];
        $out = '<svg width="180" height="120" viewBox="0 0 180 120" aria-hidden="true"><path d="M10 50H170C170 90 134 116 90 116C46 116 10 90 10 50Z" fill="#111"/>';
        foreach ($seeds as [$x, $y, $r]) {
            $out .= '<ellipse cx="' . $x . '" cy="' . $y . '" rx="14" ry="8" fill="#C5D19A" stroke="#111" stroke-width="2" transform="rotate(' . $r . ' ' . $x . ' ' . $y . ')"/>';
        }
        return $out . '</svg>';
    }
    // darilni paket
    return '<svg width="150" height="150" viewBox="0 0 150 150" aria-hidden="true">'
        . '<rect x="15" y="55" width="120" height="85" rx="4" fill="#111"/><rect x="8" y="38" width="134" height="24" rx="4" fill="#111"/>'
        . '<rect x="66" y="38" width="18" height="102" fill="#C5D19A"/>'
        . '<path d="M75 38C60 14 36 18 44 32C48 38 75 38 75 38ZM75 38C90 14 114 18 106 32C102 38 75 38 75 38Z" fill="none" stroke="#111" stroke-width="3"/></svg>';
}

function logo_mark(): string
{
    return '<span class="logo-mark"><svg width="18" height="22" viewBox="0 0 18 22" fill="none" stroke="#111" stroke-width="1.8" aria-hidden="true">'
        . '<path d="M9 1C9 1 1 10 1 14.5C1 18.6 4.6 21 9 21C13.4 21 17 18.6 17 14.5C17 10 9 1 9 1Z"/></svg></span>';
}
