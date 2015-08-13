<?php

use Symfony\Component\Yaml\Yaml;
include_once implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'vendor', 'autoload.php']);
$config = Yaml::parse(file_get_contents('config.yml'));
SAP_Factory::init($config['credentials'], $config['compile-dir']);
