<?php

$config = new kr0lik\CodeStyleFixer\Config;
$config->getFinder()->in(__DIR__.'/src');
$config->setCacheFile(__DIR__ . '/.php_cs.cache');

return $config;