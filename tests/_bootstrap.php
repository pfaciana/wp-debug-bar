<?php

$kernel = \AspectMock\Kernel::getInstance();
$kernel->init( [
	'debug'              => TRUE,
	'appDir'             => __DIR__ . '/../',
	'cacheDir'           => __DIR__ . '/_data/cache',
	'includePaths'       => [ __DIR__ . '/../src' ],
	'excludePaths'       => [ __DIR__ . '/../tests' ],
	'interceptFunctions' => TRUE,
] );
