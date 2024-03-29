<?php

declare(strict_types=1);

namespace Setono\Deployer\DotEnv;

use function Deployer\after;
use function Deployer\before;
use Deployer\Deployer;

require_once __DIR__ . '/../task/setono_dotenv.php';

after('deploy:update_code', 'dotenv:prepare');

$deployer = Deployer::get();

/**
 * The task deploy:cache:clear is defined in Symfony related recipes and both the cache clear
 * and cache warmup tasks sometimes depend on environment variables. Therefore it's a good idea
 * to have environment variables defined before running these tasks.
 */
if ($deployer->tasks->has('deploy:cache:clear')) {
    before('deploy:cache:clear', 'dotenv:update');
} else {
    before('deploy:symlink', 'dotenv:update');
}

before('dotenv:update', 'dotenv:generate');
