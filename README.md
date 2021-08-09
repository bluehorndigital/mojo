# Mojo

An opinionated Drupal build designed to run in a cloud native environment.

This is a work in progress, see the [TODO.md](./TODO.md) for initial tracking of items.

## Quick start

```
php mojo env:generate --sqlite
php vendor/bin/drush si --account-pass=admin --yes
php mojo serve
```

## Credits

Thanks to [Brad Jones](https://www.drupal.org/u/bradjones1) and [Alexander Sluiter](https://www.drupal.org/u/alexandersluiter)
who shared their tips and configurations for running Drupal in a cloud-native environment.

Thanks to [Randy Fay](https://github.com/rfay) for the support with getting the Redis and MonIO services configured with
[DDEV](https://github.com/drud/ddev)
