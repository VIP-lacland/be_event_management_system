<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_origins' => [
        'http://localhost:5173',  // fe-attendee
        'http://localhost:5174',  // fe-organizer
        'http://127.0.0.1:5173',  // fe-attendee
        'http://127.0.0.1:5174',  // fe-organizer
    ],
    
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'supports_credentials' => true, // Quan trọng cho cookie-based auth
];
