<?php

declare(strict_types=1);

namespace Setono\Deployer\DotEnv;

use function Deployer\has;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;

/**
 * This step has to come AFTER the deploy:update_code step because
 *
 * 1. We use the parameter previous_release which is set during the deploy:release step
 *
 * 2. The deploy:update_code step uses git clone to create the release directory and that command expects an empty dir
 */
task('dotenv:prepare', static function (): void {
    if (!has('stage')) {
        // if a stage isn't set then we presume the stage to be prod since you are only deploying to one place
        set('stage', 'prod');
    }

    if (has('previous_release') && test('[ -f {{previous_release}}/.env.{{stage}}.local ]')) {
        run('cp {{previous_release}}/.env.{{stage}}.local {{release_path}}');
    } else {
        run('touch {{release_path}}/.env.{{stage}}.local');
    }
})->desc('Copies .env.[stage].local from previous release folder or creates a new one');

task('dotenv:generate-php', static function (): void {
    run('cd {{release_path}} && {{bin/composer}} symfony:dump-env {{stage}}');
})->desc('Generates the .env.local.php file');
