<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
	'@PHP82Migration' => true,
	'php_unit_test_class_requires_covers' => true,
    ])
    ->setFinder($finder)
;
