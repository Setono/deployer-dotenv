<?php

declare(strict_types=1);

namespace Setono\Deployer\DotEnv;

use function Deployer\ask;
use function Deployer\askConfirmation;
use function Deployer\has;
use function Deployer\input;
use function Deployer\invoke;
use function Deployer\output;
use function Deployer\run;
use function Deployer\set;
use function Deployer\task;
use function Deployer\test;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Webmozart\Assert\Assert;

/**
 * This step has to come AFTER the deploy:update_code step because
 *
 * 1. We use the parameter previous_release which is set during the deploy:release step
 *
 * 2. The deploy:update_code step uses git clone to create the release directory and that command expects an empty dir
 */
task('dotenv:prepare', static function (): void {
    if (!has('stage')) {
        // if a stage isn't set then we presume the stage to be prod since you are only deploying to one place
        set('stage', 'prod');
    }

    if (has('previous_release') && test('[ -f {{previous_release}}/.env.{{stage}}.local ]')) {
        run('cp {{previous_release}}/.env.{{stage}}.local {{release_path}}');
    } else {
        run('touch {{release_path}}/.env.{{stage}}.local');
    }
})->desc('Copies .env.[stage].local from previous release folder or creates a new one');

/**
 * This task should be called BEFORE dotenv:update because that task needs the .env.local.php file
 */
task('dotenv:generate', static function (): void {
    run('cd {{release_path}} && {{bin/composer}} symfony:dump-env {{stage}}');
})->desc('Generates the .env.local.php file');

/**
 * This task should be called BEFORE deploy:symlink
 */
task('dotenv:update', static function (): void {
    if (!input()->isInteractive()) {
        return;
    }

    $output = output();

    $outputVariablesFunction = static function (OutputInterface $output, array $variables): void {
        ksort($variables);

        $table = new Table($output);
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
    };

    /**
     * We want two arrays to begin with. This allows us to easily compare the two arrays later on
     * when the $variables may have been changed by the user
     */
    $variables = $initialVariables = evalEnv(run('cat {{release_path}}/.env.local.php'));

    while (true) {
        $outputVariablesFunction($output, $variables);

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
        $overriddenValues = (new Dotenv())->parse(run('cat {{release_path}}/.env.{{stage}}.local'));

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

        /**
         * This will generate a $command variable that will save a multiline text into a file
         * See https://stackoverflow.com/questions/10969953/how-to-output-a-multiline-string-in-bash
         */
        $command = "cat <<EOT > {{release_path}}/.env.{{stage}}.local\n";
        foreach ($overriddenValues as $key => $val) {
            $command .= $key . '=' . $val . "\n";
        }
        $command .= 'EOT';
        run($command);

        // Now we rerun the generation because we changed the environment variables
        invoke('dotenv:generate');
    }
})->desc('Allows the user to update environment variables');

/**
 * @return array<string, scalar>
 */
function evalEnv(string $envContents): array
{
    /** @var array<string, scalar> $res */
    $res = eval('?>' . $envContents);
    Assert::isArray($res);
    Assert::allScalar($res);

    return $res;
}
