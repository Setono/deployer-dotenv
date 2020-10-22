# DotEnv handling with Deployer

If you use [Deployer](https://deployer.org/) as your deployment tool
and [Symfony](https://symfony.com/) in your application this library is for you.

Are you still accessing your server to update environment variables manually after a deployment?
We also did that and that's the main reason why we built this library.

Now we have a very specific, but simple, strategy for updating the `.env` files during deployment:

* We do **not** share the `.env.local.php`, `.env.local` files as is the default by Deployer.
Instead, we have a `.env.[stage].local` and `.env.local.php` in each release folder.

* When deploying we copy the `.env.[stage].local` file from the previous release (if there was a previous relesae).

* If you are deploying interactively (i.e. manually) you are presented with a dialog asking if you want to update any
environment variables.

* Finally we run `composer symfony:dump-env [stage]` to generate the `.env.local.php` file for the current release.
