<?php
include_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

$arrIncludes = [
	[
		dirname(__DIR__),
		'cli',
		'compile.php'
	]
];

foreach ($arrIncludes as $arrPath) {
	include_once implode(DIRECTORY_SEPARATOR, $arrPath);
}


