<?php

return [
    'app_name'                  => env('APP_NAME', 'Helfer*innen'),
    'footer_items'              => [
        'FAQ'       => env('FAQ_URL', 'https://kontakt-bamberg.de/helfen'),
        'Contact'   => env('CONTACT_EMAIL', 'mailto:helfen@kontakt-bamberg.de'),
    ],
    'theme'                     => env('THEME', 0),
    'last_unsubscribe'          => 24,
    'enable_dect'               => false,
    'enable_planned_arrival'    => false,
    'enable_tshirt_size'        => false,
    'max_freeloadable_shifts'   => 1,
    'locales'                   => [
        'de_DE.UTF-8@kontakt' => 'Deutsch',
    ],
    'default_locale'            => env('DEFAULT_LOCALE', 'de_DE.UTF-8@kontakt'),
];
