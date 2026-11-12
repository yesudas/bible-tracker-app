<?php

/** Loads config.php (gitignored) if present, else config.example.php defaults. */
function btConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $file = __DIR__ . '/../config.php';
        $loaded = is_file($file) ? require $file : [];
        $cfg = is_array($loaded) ? $loaded : [];
    }
    return $cfg;
}

function btIsLocalhost(): bool
{
    // The Host header is client-controlled, so also require the connection itself to come from the local machine.
    $host = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    $loopback = in_array((string) ($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);
    return $loopback && in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
}

/** Testing mode = flag on in config.php AND request is really on localhost. */
function btTestMode(): bool
{
    return !empty(btConfig()['test_mode']) && btIsLocalhost();
}

/** Admin emails: yesudas.rs@gmail.com always, plus any in config.php 'admins'. */
function btAdmins(): array
{
    $extra = array_map('strval', (array) (btConfig()['admins'] ?? []));
    return array_values(array_unique(array_map(
        static fn (string $e): string => strtolower(trim($e)),
        array_merge(['yesudas.rs@gmail.com'], $extra)
    )));
}

function btIsAdmin(?array $user): bool
{
    return $user && !empty($user['email']) && in_array(strtolower(trim((string) $user['email'])), btAdmins(), true);
}
