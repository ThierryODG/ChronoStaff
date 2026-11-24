<?php

return [
    'subject' => env('VAPID_SUBJECT', 'mailto:rawelgui02@gmail.com'),
    'public_key' => env('VAPID_PUBLIC_KEY'),
    'private_key' => env('VAPID_PRIVATE_KEY'),
];
