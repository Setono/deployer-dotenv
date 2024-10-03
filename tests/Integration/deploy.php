<?php

declare(strict_types=1);

namespace Deployer;

require_once '../../vendor/autoload.php';

require_once 'recipe/common.php';
require_once 'recipe/setono_dotenv.php';

// Config
set('repository', 'https://github.com/Setono/deployer-dotenv.git');

// Hosts
host('127.0.0.1')
    ->setPort(2222)
    ->setRemoteUser('root')
    ->setIdentityFile(__DIR__ . '/../docker/ssh/id_rsa')
    ->setSshArguments(['-o UserKnownHostsFile=/dev/null', '-o StrictHostKeyChecking=no'])
    ->set('deploy_path', '~/deployer');

// Hooks
after('deploy:failed', 'deploy:unlock');
