<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->addPathToScan(__DIR__ . '/src', false)
    ->ignoreErrorsOnPackage('deployer/deployer', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreUnknownFunctions([
        'Deployer\after',
        'Deployer\ask',
        'Deployer\askConfirmation',
        'Deployer\before',
        'Deployer\get',
        'Deployer\has',
        'Deployer\input',
        'Deployer\invoke',
        'Deployer\output',
        'Deployer\run',
        'Deployer\task',
        'Deployer\test',
        'Deployer\upload',
    ])
;
