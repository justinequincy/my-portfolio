<?php

define(
    'PASSWORD_RESET_BASE_URL',
    rtrim(getenv('PASSWORD_RESET_BASE_URL') ?: 'http://localhost/secure_file_system', '/')
);
define(
    'PASSWORD_RESET_FROM_EMAIL',
    getenv('PASSWORD_RESET_FROM_EMAIL') ?: 'no-reply@example.com'
);

if (
    filter_var(PASSWORD_RESET_BASE_URL, FILTER_VALIDATE_URL) === false
    || !in_array(parse_url(PASSWORD_RESET_BASE_URL, PHP_URL_SCHEME), ['http', 'https'], true)
) {
    throw new RuntimeException('PASSWORD_RESET_BASE_URL must be a valid HTTP or HTTPS URL.');
}

if (filter_var(PASSWORD_RESET_FROM_EMAIL, FILTER_VALIDATE_EMAIL) === false) {
    throw new RuntimeException('PASSWORD_RESET_FROM_EMAIL must be a valid email address.');
}
