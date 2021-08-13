# mojo-drupal-scaffold

Scaffolding to add some Mojo to your Drupal projects.

This package adds scaffolding files for your Drupal project by integrating with `drupal/core-composer-scaffold`.

## Why?

Composer project templates are great, except they are a "fork and forget" model. This means various project files can drift and lose updates when there are upstream improvements.

The `mojo-drupal-scaffold` package aims to solve that problem by providing up to date files for your Drupal projects.

## Batteries included

This package provides a `settings.php` that is powered by environment variables and supports Redis configuration and Flystem object storage for assets.

It also contains a preconfigured `phpunit.xml.dist` for testings and a `development.services.yml` for local development.


## Credits

It was inspired by [`amazeeio/drupal_integrations`](https://github.com/amazeeio/drupal-integrations) which provides files to setup yuour Drupal project for Lagoon hosting.

