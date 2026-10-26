<?php

return [

    // A public, read-only copy: only the board is served, to anyone, and no sign-in, upload or admin
    // route exists, so nothing can be written (routes/web.php, FortifyServiceProvider). Fill it with
    // `php artisan db:seed --class=DemoSeeder`; pair it with SESSION_DRIVER=file so visitors leave no
    // rows behind.
    'demo' => (bool) env('DEMO_MODE', false),

    // Linked from the demo banner.
    'repository_url' => env('SHIPLOG_REPOSITORY_URL', 'https://github.com/mohamadsyukri/shiplog'),

];
