<?php

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/dashboard_service.php';

$cache = app_bootstrap(__DIR__);
$viewModel = app_build_dashboard_view_model($_GET, $cache);

extract($viewModel);

require __DIR__ . '/views/dashboard.php';
