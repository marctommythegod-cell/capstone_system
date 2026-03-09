<?php

return [
    'smtp_host' => $_ENV['SMTP_HOST'],
    'smtp_port' => $_ENV['SMTP_PORT'],
    'smtp_secure' => $_ENV['SMTP_SECURE'],
    'smtp_auth' => $_ENV['SMTP_AUTH'],

    'from_email' => $_ENV['MAIL_FROM_EMAIL'],
    'from_name' => $_ENV['MAIL_FROM_NAME'],
    'admin_email' => $_ENV['MAIL_ADMIN_EMAIL'],

    'username' => $_ENV['MAIL_USERNAME'],
    'password' => $_ENV['MAIL_PASSWORD'],

    'no_reply_email' => $_ENV['MAIL_NO_REPLY']
];