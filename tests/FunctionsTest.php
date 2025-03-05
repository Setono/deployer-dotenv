<?php

declare(strict_types=1);

namespace Setono\Deployer\DotEnv;

use Deployer\Deployer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;

final class FunctionsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        new Deployer(new Application());

        require_once __DIR__ . '/../src/task/setono_dotenv.php';
    }

    /**
     * @test
     */
    public function it_evaluates_php_env(): void
    {
        $env = evaluatePhpEnv(<<<ENV
<?php
return array (
  'APP_ENV' => 'prod',
  'HOSTNAME' => 'example.com',
);
ENV);

        self::assertSame('prod', $env['APP_ENV']);
        self::assertSame('example.com', $env['HOSTNAME']);
    }

    /**
     * @test
     */
    public function it_gets_stage1(): void
    {
        $stage = getStage('random');
        self::assertSame('prod', $stage);
    }

    /**
     * @test
     */
    public function it_gets_stage2(): void
    {
        $stage = getStage(['key' => 'random']);
        self::assertSame('prod', $stage);
    }

    /**
     * @test
     */
    public function it_gets_stage3(): void
    {
        $stage = getStage(['stage' => 'dev']);
        self::assertSame('dev', $stage);
    }
}
