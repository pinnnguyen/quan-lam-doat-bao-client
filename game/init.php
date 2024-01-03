<?php

require_once __DIR__ . '/vendor/autoload.php';

\App\Libs\Events\Initializers\ProcessInitializer::hook();
\App\Libs\Events\Initializers\GlobalInitializer::hook();
