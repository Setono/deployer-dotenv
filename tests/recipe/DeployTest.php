<?php

declare(strict_types=1);

namespace Setono\Deployer\DotEnv\recipe;

use Deployer\Console\Application;
use Deployer\Deployer;
use PHPUnit\Framework\TestCase;
use function Safe\sprintf;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;

final class DeployTest extends TestCase
{
    private ApplicationTester $tester;

    private Deployer $deployer;

    public static function setUpBeforeClass(): void
    {
        // init repository
        $repository = __DIR__ . '/repository';

        exec("cd $repository && git init");
        exec("cd $repository && git add .");
        exec("cd $repository && git config user.name 'Joachim Løvgaard'");
        exec("cd $repository && git config user.email 'joachim@setono.com'");
        exec("cd $repository && git commit -m 'first commit'");
    }

    protected function setUp(): void
    {
        // init deployer and application tester
        $console = new Application();
        $console->setAutoExit(false);
        $this->tester = new ApplicationTester($console);

        $this->deployer = new Deployer($console);
        Deployer::loadRecipe(__DIR__ . '/deploy.php');
        $this->deployer->init();
    }

    protected function tearDown(): void
    {
        foreach ($this->deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');
            exec("rm -rf $deployPath");
        }
    }

    public static function tearDownAfterClass(): void
    {
        $git = __DIR__ . '/repository/.git';
        exec("rm -rf $git");
    }

    /**
     * @test
     */
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
        echo "\n\n\n$display\n\n\n";
        self::assertEquals(0, $this->tester->getStatusCode(), $display);

        foreach ($this->deployer->hosts as $host) {
            $deployPath = $host->get('deploy_path');

            self::assertFileExists($deployPath . '/current/README.md');
        }
    }
}
