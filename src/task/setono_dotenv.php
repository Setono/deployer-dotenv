<?php

declare(strict_types=1);

namespace Setono\Deployer\DotEnv;

use function Deployer\ask;
use function Deployer\askConfirmation;
use function Deployer\get;
use function Deployer\has;
use function Deployer\input;
use function Deployer\invoke;
use function Deployer\output;
use function Deployer\run;
use function Deployer\task;
use function Deployer\test;
use function Deployer\upload;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Dotenv\Dotenv;
use Webmozart\Assert\Assert;

/**
 * This step has to come AFTER the deploy:update_code step because
 *
 * 1. We use the parameter previous_release which is set during the deploy:release step
 *
 * 2. The deploy:update_code step can use git clone to create the release directory and that command expects an empty dir
 */
task('dotenv:prepare', static function (): void {
    $stage = getStage();

    // this small trick will make sure the environment (i.e. for the console) is set to the expected environment
    // when running commands before the generation of the .env.local.php is run
    if (!test('[ -f {{release_path}}/.env.local ]')) {
        run(sprintf('echo "APP_ENV=%s" > {{release_path}}/.env.local', $stage));
    }

    // if the .env.[stage].local file exists, we don't need to do anything
    if (test(sprintf('[ -f {{release_path}}/.env.%s.local ]', $stage))) {
        return;
    }

    if (has('previous_release') && test(sprintf('[ -f {{previous_release}}/.env.%s.local ]', $stage))) {
        run(sprintf('cp {{previous_release}}/.env.%s.local {{release_path}}', $stage));
    } else {
        run(sprintf('touch {{release_path}}/.env.%s.local', $stage));
    }
})->desc('Copies .env.[stage].local from previous release folder or creates a new one');

/**
 * This task should be called BEFORE dotenv:update because that task needs the .env.local.php file
 */
task('dotenv:generate', static function (): void {
    $stage = getStage();

    run(sprintf('cd {{release_path}} && {{bin/composer}} symfony:dump-env %s', $stage));
})->desc('Generates the .env.local.php file');

/**
 * This task should be called BEFORE deploy:symlink
 */
task('dotenv:update', static function (): void {
    if (!input()->isInteractive()) {
        return;
    }

    /**
     * We want two arrays to begin with. This allows us to easily compare the two arrays later on
     * when the $variables may have been changed by the user
     */
    $variables = $initialVariables = evaluatePhpEnvFile('{{release_path}}/.env.local.php');

    while (true) {
        outputEnvironmentVariables($variables);

        $confirmation = askConfirmation('Do you want to update ' . (isset($confirmation) ? 'more' : 'any') . ' environment variables?');
        if (false === $confirmation) {
            break;
        }

        while (true) {
            $newValue = ask('Input environment variable and value (ENV_VAR=value). Press <return> when you are finished adding', '', array_keys($variables));
            if ('' === $newValue) {
                break;
            }

            [$key, $val] = explode('=', $newValue, 2);

            // Here we add/overwrite the value from the user
            $variables[$key] = $val;
        }
    }
    unset($confirmation);

    while (true) {
        outputEnvironmentVariables($variables);

        $confirmation = askConfirmation('Do you want to remove ' . (isset($confirmation) ? 'more' : 'any') . ' environment variables?');
        if (false === $confirmation) {
            break;
        }

        while (true) {
            $variable = ask('Input environment variable. Press <return> when you are finished removing', '', array_keys($variables));
            if ('' === $variable) {
                break;
            }

            unset($variables[$variable]);
        }
    }

    $stage = getStage();

    /**
     * Notice that this comparison will return false if the two arrays have different key/value pairs
     * See https://www.php.net/manual/en/language.operators.array.php
     */
    if ($initialVariables != $variables) {
        /**
         * This array contains the environment variables already overridden
         *
         * @var array<string, string> $overriddenValues
         */
        $overriddenValues = (new Dotenv())->parse(run(sprintf('cat {{release_path}}/.env.%s.local', $stage)));

        /**
         * The difference between the $variables array and the $initialVariables array
         * are the variables that the user has overridden in the dialog above
         */
        $newOverriddenValues = array_diff_assoc($variables, $initialVariables);

        /**
         * Now we merge the new overridden values with the old ones which will
         * give us the values we need to save to the .env.[stage].local file
         */
        $overriddenValues = array_merge($overriddenValues, $newOverriddenValues);

        $filename = sprintf(__DIR__ . '/.env.%s.local', $stage);

        $data = '';
        foreach ($overriddenValues as $key => $val) {
            $data .= $key . '=' . $val . "\n";
        }

        file_put_contents($filename, $data);

        upload($filename, '{{release_path}}');

        unlink($filename);

        // Now we rerun the generation because we changed the environment variables
        invoke('dotenv:generate');
    }
})->desc('Allows the user to update environment variables');

/**
 * Returns the current stage or 'prod' if no stage is set
 */
function getStage(): string
{
    $labels = get('labels');
    if (!is_array($labels)) {
        return 'prod';
    }

    if (!isset($labels['stage'])) {
        return 'prod';
    }

    $stage = $labels['stage'];
    Assert::stringNotEmpty($stage);

    return $stage;
}

function outputEnvironmentVariables(array $variables): void
{
    ksort($variables);

    $table = new Table(output());
    $table->setRows([
        ['Variable', 'Value'],
        new TableSeparator(),
    ]);

    /**
     * @var string $key
     * @var string $val
     */
    foreach ($variables as $key => $val) {
        $table->addRow([$key, $val]);
    }

    $table->render();
}

/**
 * @return array<string, scalar>
 */
function evaluatePhpEnvFile(string $path): array
{
    $data = run(sprintf('cat %s', $path));
    Assert::stringNotEmpty($data);

    /** @var array<string, scalar> $res */
    $res = eval('?>' . $data);
    Assert::isArray($res);
    Assert::allScalar($res);

    return $res;
}
