<?php
// Polje za geslo z gumbom za prikaz/skritje gesla.
function password_input(string $name, string $id, string $autocomplete, bool $invalid, string $describedBy = ''): string
{
    $described = trim($describedBy . ' ' . $id . '-error');
    return '<div class="input-wrap">'
        . '<input type="password" id="' . $id . '" name="' . $name . '" class="has-toggle" autocomplete="' . $autocomplete . '" required'
        . ' aria-describedby="' . $described . '"' . ($invalid ? ' aria-invalid="true"' : '') . '>'
        . '<button type="button" class="toggle-pass" aria-label="Pokaži geslo" aria-pressed="false" data-target="' . $id . '">'
        . '<svg class="icon-show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>'
        . '<svg class="icon-hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.4 10.4 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.1M6.6 6.6C3.8 8.4 2 12 2 12s3.6 7 10 7c1.8 0 3.4-.5 4.8-1.3"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>'
        . '</button></div>';
}
