<?php

/**
 * Thin bridge to the shared wordofgod.in/auth service (sibling repo
 * github-projects/auth, deployed at wordofgod.in/auth — a sibling folder of
 * this app, hence the ../../ path). Same approach as bible-quiz-app.
 *
 * Testing mode (config.php 'test_mode' => true, localhost only) skips Google
 * and returns a fixed user, so the app can be exercised without signing in.
 */
require_once __DIR__ . '/app-config.php';

function btCurrentUrl(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
}

function btAuthSessionFile(): ?string
{
    $f = __DIR__ . '/../../auth/session.php';
    return is_file($f) ? $f : null;
}

/** @return array{sub:string,email:string,name:string,picture:string}|null */
function btGoogleUser(): ?array
{
    if (btTestMode()) {
        $u = btConfig()['test_user'] ?? [];
        return [
            'sub' => (string) ($u['sub'] ?? 'test-user'),
            'email' => (string) ($u['email'] ?? 'yesudas.rs@gmail.com'),
            'name' => (string) ($u['name'] ?? 'Yesudas Solomon'),
            'picture' => (string) ($u['picture'] ?? ''),
        ];
    }
    $file = btAuthSessionFile();
    if (!$file) {
        return null;
    }
    require_once $file;
    try {
        $u = authUser();
        return $u && !empty($u['email']) ? $u : null;
    } catch (Throwable $e) {
        return null;
    }
}

function btGoogleLoginUrl(string $returnTo): ?string
{
    $file = btAuthSessionFile();
    if (!$file) {
        return null;
    }
    require_once $file;
    return authLoginUrl($returnTo);
}

function btGoogleLogoutUrl(string $returnTo): ?string
{
    if (btTestMode()) {
        return null; // nothing to sign out of
    }
    $file = btAuthSessionFile();
    if (!$file) {
        return null;
    }
    require_once $file;
    return authLogoutUrl($returnTo);
}
