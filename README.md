# DotEnv handling with Deployer

[![Latest Version][ico-version]][link-packagist]
[![Latest Unstable Version][ico-unstable-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]

If you use [Deployer](https://deployer.org/) as your deployment tool
and `.env` files to handle environment variables (i.e. Symfony) this library is for you.

Are you still accessing your server to update environment variables manually after a deployment?
We also did that and that's the main reason why we built this library.

Now we have a very specific, but simple, strategy for updating the `.env` files during deployment:

* We do **not** share the `.env.local.php`, `.env.local` files as is the default by Deployer.
Instead, we have a `.env.[stage].local` and `.env.local.php` in each release folder.

* When deploying we copy the `.env.[stage].local` file from the previous release
(if there was a previous release, else we create it).

* If you are deploying interactively (i.e. manually) you are presented with a dialog asking if you want to update any
environment variables.

* Finally we run `composer symfony:dump-env [stage]` to generate the `.env.local.php` file for the current release.

## Installation

```bash
$ composer require setono/deployer-dotenv
```

## Usage

In your `deploy.php` file require the recipe:

```php
<?php

namespace Deployer;

require_once 'recipe/dotenv.php';

// ...
```

This will automatically hook into the default flow of Deployer.

[ico-version]: https://poser.pugx.org/setono/deployer-dotenv/v/stable
[ico-unstable-version]: https://poser.pugx.org/setono/deployer-dotenv/v/unstable
[ico-license]: https://poser.pugx.org/setono/deployer-dotenv/license
[ico-github-actions]: https://github.com/Setono/deployer-dotenv/workflows/build/badge.svg

[link-packagist]: https://packagist.org/packages/setono/deployer-dotenv
[link-github-actions]: https://github.com/Setono/deployer-dotenv/actions
