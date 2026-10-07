<?php
// Skupne funkcije: seja, prijavljen uporabnik, vloge, CSRF zaščita, sporočila

const SESSION_TIMEOUT = 30 * 60; // samodejna odjava po 30 minutah neaktivnosti

date_default_timezone_set('Europe/Ljubljana');

if (session_status() === PHP_SESSION_NONE) {
    // Piškotek seje: JavaScript do njega ne more (httponly), drugi spletni strani ga brskalnik ne pošlje (samesite)
    session_set_cookie_params([
        'lifetime' => 0,        // piškotek izgine, ko zapreš brskalnik
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1'); // PHP ne sprejme ID seje, ki ga ni sam ustvaril
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Preveri neaktivnost prijavljenega uporabnika
if (isset($_SESSION['user'])) {
    if (time() - ($_SESSION['last_activity'] ?? time()) > SESSION_TIMEOUT) {
        end_session();
        session_start();
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Zaradi neaktivnosti ste bili odjavljeni. Prijavite se znova.'];
    } else {
        $_SESSION['last_activity'] = time();
    }
}

// Popolnoma zaključi sejo: izprazni podatke, izbriše piškotek in sejo na strežniku
function end_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// Uporabnika zapiše v sejo ob uspešni prijavi
function login_user(array $user): void
{
    session_regenerate_id(true); // nova ID seje – zaščita pred prevzemom seje
    $_SESSION['user'] = [
        'id'         => (int) $user['id_user'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
        'email'      => $user['email'],
        'role'       => $user['role'],
    ];
    $_SESSION['login_time']    = time();
    $_SESSION['last_activity'] = time();
}

// Osnovna pot projekta (npr. /kmetija-skledar/), da povezave delujejo tudi iz podmape admin/
$root    = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? $root));
define('BASE', rtrim(substr($root, strlen($docRoot)), '/') . '/');

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Znesek v evrih po slovensko: 1.234,50 €
function money($amount): string
{
    return number_format((float) $amount, 2, ',', '.') . ' €';
}

// Slovenska množina po številu: 1 izdelek, 2 izdelka, 3 izdelki, 5 izdelkov
function plural(int $n, string $one, string $two, string $few, string $many): string
{
    $mod = $n % 100;
    $word = $mod === 1 ? $one : ($mod === 2 ? $two : ($mod === 3 || $mod === 4 ? $few : $many));
    return $n . ' ' . $word;
}

function url(string $path = ''): string
{
    return BASE . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

// Vlogo prebere iz baze, ne zaupa samo seji: če admin uporabniku spremeni vlogo
// ali ga izbriše, to velja takoj in ne šele ob naslednji prijavi
function refresh_user(): void
{
    global $pdo;
    if (!current_user()) {
        return;
    }
    $stmt = $pdo->prepare(
        'SELECT r.name FROM users u JOIN roles r ON r.id_role = u.id_role WHERE u.id_user = ?'
    );
    $stmt->execute([current_user()['id']]);
    $role = $stmt->fetchColumn();

    if ($role === false) { // uporabnika ni več v bazi
        end_session();
        session_start();
    } else {
        $_SESSION['user']['role'] = $role;
    }
}

function require_login(): void
{
    refresh_user();
    if (!current_user()) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI']; // po prijavi se vrne na to stran
        set_flash('error', 'Za ogled te strani se prijavite.');
        redirect('prijava.php');
    }
}

// Prijavljen, a ni admin: 403 (prepovedano), tudi če URL vpiše neposredno
function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        $pageTitle = 'Dostop zavrnjen';
        require __DIR__ . '/header.php';
        require __DIR__ . '/403.php';
        require __DIR__ . '/footer.php';
        exit;
    }
}

// CSRF: vsak obrazec pošlje skriti žeton, ki ga preverimo na strežniku
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// Gumb za odjavo – obrazec POST s CSRF žetonom, da te tuja stran ne more odjaviti z obično povezavo
function logout_button(string $class = 'link-btn'): string
{
    return '<form method="post" action="' . url('odjava.php') . '" class="logout-form">'
        . csrf_field()
        . '<button type="submit" class="' . $class . '">Odjava</button></form>';
}

// Enkratno sporočilo, ki preživi preusmeritev
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
