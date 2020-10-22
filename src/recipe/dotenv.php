<?php

declare(strict_types=1);

namespace Setono\Deployer\DotEnv;

use function Deployer\after;
use function Deployer\before;

require_once 'task/dotenv.php';

after('deploy:update_code', 'dotenv:prepare');
before('deploy:symlink', 'dotenv:generate-php');
