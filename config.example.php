<?php
// Copy to config.php (gitignored). Never enable test_mode on a public server.
return [
    // Extra admin emails (a.php). <YOUR-EMAIL>@gmail.com is always an admin.
    'admins' => [],
    // Testing mode: only honoured when the request host is localhost / 127.0.0.1.
    // Pretends the user below is already signed in with Google.
    'test_mode' => false,
    'test_user' => [
        'sub'     => 'test-user',
        'name'    => 'Yesudas Solomon',
        'email'   => '<YOUR-EMAIL>@gmail.com',
        'picture' => '',
    ],
];
