<?php

declare(strict_types=1);

namespace Setono\Deployer\DotEnv\recipe;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

final class DeployTest extends TestCase
{
    public function it_deploys(): void
    {
        $this->tester->run([
            'deploy',
            '-f' => __DIR__ . '/deploy.php',
        ], [
            'verbosity' => OutputInterface::VERBOSITY_NORMAL,
            'interactive' => false,
        ]);

        $display = $this->tester->getDisplay();
        self::assertEquals(0, $this->tester->getStatusCode(), $display);

        foreach ($this->deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertFileExists($deployPath . '/current/.env.prod.local');
            self::assertFileExists($deployPath . '/current/.env.local.php');

            $env = require $deployPath . '/current/.env.local.php';
            self::assertArrayHasKey('APP_ENV', $env);
            self::assertArrayHasKey('ENV_VAR', $env);

            self::assertSame('prod', $env['APP_ENV']);
            self::assertSame('value', $env['ENV_VAR']);
        }
    }
}
