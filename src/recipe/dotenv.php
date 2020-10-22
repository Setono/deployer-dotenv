<?php

declare(strict_types=1);

namespace Setono\Deployer\DotEnv;

use function Deployer\before;

require_once 'task/dotenv.php';

before('deploy:release', 'dotenv:prepare');
