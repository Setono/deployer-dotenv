<?php

declare(strict_types=1);

namespace Setono\Deployer\DotEnv;

use function Deployer\after;
use function Deployer\before;

require_once 'task/setono_dotenv.php';

after('deploy:update_code', 'dotenv:prepare');
after('deploy:vendors', 'dotenv:update');
before('dotenv:update', 'dotenv:generate');
