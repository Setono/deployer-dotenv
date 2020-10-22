<?php

declare(strict_types=1);

namespace Setono\Deployer\Systemd\recipe;

use function Deployer\desc;
use function Deployer\fail;
use function Deployer\localhost;
use function Deployer\set;
use function Deployer\task;

require_once 'vendor/deployer/deployer/recipe/common.php';
require_once 'recipe/dotenv.php';

// configuration
set('repository', __DIR__ . '/repository');
set('branch', null);

// Hosts
localhost()
    ->set('deploy_path', __DIR__ . '/../../.build/deployer');

// Tasks
desc('Deploy your project');
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success',
]);

// If deploy fails automatically unlock

fail('deploy_fail', 'deploy:unlock');
