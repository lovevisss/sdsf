<?php

return [
    'base_url' => env('CAS_BASE_URL', 'https://cas.paas.zufedfc.edu.cn/cas'),
    'validate_path' => env('CAS_VALIDATE_PATH', '/serviceValidate'),
    'logout_path' => env('CAS_LOGOUT_PATH', '/logout'),
    'verify_ssl' => (bool) env('CAS_VERIFY_SSL', true),

    // Mapping CAS attributes to local user fields.
    'id_attribute' => env('CAS_ID_ATTRIBUTE', 'gh'),
    'name_attribute' => env('CAS_NAME_ATTRIBUTE', 'name'),
    'email_attribute' => env('CAS_EMAIL_ATTRIBUTE', 'email'),
    'email_domain' => env('CAS_EMAIL_DOMAIN', 'cas.local'),

    // Auto-create user on first CAS login when no local account matches.
    'auto_register' => (bool) env('CAS_AUTO_REGISTER', true),
];

