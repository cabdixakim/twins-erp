<?php

return [
    // max companies a single user can have access to (0 = unlimited)
    'max_companies_per_user' => (int) env('TWINS_MAX_COMPANIES_PER_USER', 1),

    // max companies in the whole app/db (0 = unlimited)
    'max_companies_app' => (int) env('TWINS_MAX_COMPANIES_APP', 0),

    // multi-company feature enabled/disabled
    'multi_company_enabled' => (bool) env('TWINS_MULTI_COMPANY_ENABLED', true),
];