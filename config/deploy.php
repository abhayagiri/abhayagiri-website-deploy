<?php

return [

    'repository_url' => 'https://github.com/abhayagiri/abhayagiri-website',
    'timeout' => 60 * 30, // 30 minutes
    'from_email' => env('DEPLOY_FROM_EMAIL'),
    'max_days' => 7,

];
