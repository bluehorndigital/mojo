<?php

// phpcs:ignoreFile

/**
 * Location of the site configuration files.
 *
 * The $settings['config_sync_directory'] specifies the location of file system
 * directory used for syncing configuration data. It is set outside of the
 * document root.
 */

use Drupal\Core\Installer\InstallerKernel;

$settings['config_sync_directory'] = '../config';

/**
 * Private file path:
 *
 * A local file system path where private files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 */
$settings['file_private_path'] = '../private';

/**
 * Temporary file path:
 *
 * A local file system path where temporary files will be stored. This directory
 * must be absolute, outside the Drupal installation directory and not
 * accessible over the web.
 */
$settings['file_temp_path'] = sys_get_temp_dir();

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 *
 * Set in your .env file.
 */
$settings['hash_salt'] = $_ENV['DRUPAL_HASH_SALT'] ?: '';

/**
 * Deployment identifier.
 *
 * Drupal's dependency injection container will be automatically invalidated and
 * rebuilt when the Drupal core version changes. When updating contributed or
 * custom code that changes the container, changing this identifier will also
 * allow the container to be invalidated as soon as code is deployed.
 *
* Set in your .env file.
 */
$settings['deployment_identifier'] = $_ENV['DEPLOYMENT_IDENTIFIER'] ?: \Drupal::VERSION;

/**
 * Security hardening.
 */
$settings['update_free_access'] = FALSE;
$settings['allow_authorize_operations'] = FALSE;

/**
 * Default mode for directories and files written by Drupal.
 *
 * Ensures group level access.
 */
$settings['file_chmod_directory'] = 0775;
$settings['file_chmod_file'] = 0664;

/**
 * The default list of directories that will be ignored by Drupal's file API.
 *
 * By default, ignore node_modules and bower_components folders to avoid issues
 * with common frontend tools and recursive scanning of directories looking for
 * extensions.
 *
 * @see \Drupal\Core\File\FileSystemInterface::scanDirectory()
 * @see \Drupal\Core\Extension\ExtensionDiscovery::scanDirectory()
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
  'vendor',
];

/**
 * Load services definition file.
 */
assert(isset($app_root) && is_string($app_root));
assert(isset($site_path) && is_string($site_path));
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

/**
 * Database connection information.
 */
if ($_ENV['DB_CONNECTION'] !== 'sqlite') {
  $databases['default']['default'] = [
    'driver' => $_ENV['DB_CONNECTION'],
    'database' => $_ENV['DRUPAL_DATABASE_NAME'],
    'username' => $_ENV['DRUPAL_DATABASE_USERNAME'],
    'password' => $_ENV['DRUPAL_DATABASE_PASSWORD'],
    'host' => $_ENV['DRUPAL_DATABASE_HOST'],
    'port' => $_ENV['DRUPAL_DATABASE_PORT'],
  ];
}
else {
  $databases['default']['default'] = array (
    'database' => '../private/db.sqlite',
    'prefix' => '',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\sqlite',
    'driver' => 'sqlite',
  );
}

/**
 * Email server connection information
 */
$config['swiftmailer.transport']['transport'] = 'smtp';
$config['swiftmailer.transport']['smtp_host'] = $_ENV['SMTP_SERVER'];
$config['swiftmailer.transport']['smtp_port'] = $_ENV['SMTP_PORT'];
$config['swiftmailer.transport']['smtp_encryption'] = $_ENV['SMTP_PORT'] === '1025' ? '0' : 'ssl';
$config['swiftmailer.transport']['smtp_credentials']['swiftmailer']['username'] = $_ENV['SMTP_USERNAME'];
$config['swiftmailer.transport']['smtp_credentials']['swiftmailer']['password'] = $_ENV['SMTP_PASSWORD'];

// Reverse proxy detection.
// Stolen from the trusted_reverse_proxy module.
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['REMOTE_ADDR'])) {
  $settings['reverse_proxy'] = TRUE;

  // First hop is assumed to be a reverse proxy in its own right.
  $proxies = [$_SERVER['REMOTE_ADDR']];
  // We may be further behind another reverse proxy (e.g., Traefik, Varnish)
  // Commas may or may not be followed by a space.
  // @see https://tools.ietf.org/html/rfc7239#section-7.1
  $forwardedFor = explode(
    ',',
    str_replace(', ', ',', $_SERVER['HTTP_X_FORWARDED_FOR'])
  );
  if (count($forwardedFor) > 1) {
    // The first value will be the actual client IP.
    array_shift($forwardedFor);
    array_unshift($proxies, ...$forwardedFor);
  }

  $settings['reverse_proxy_addresses'] = $proxies;
}

if ($_ENV['FILESYSTEM_DRIVER'] === 's3') {
  $schemes = [
    's3' => [
      'driver' => 's3',
      'config' => [
        'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
        'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
        'region' => $_ENV['AWS_DEFAULT_REGION'],
        'bucket' => $_ENV['S3_BUCKET'],
        'endpoint' => $_ENV['S3_ENDPOINT'] ?? NULL,
        'protocol' => $_ENV['S3_PROTOCOL'] ?? 'https',
        'cname' => $_ENV['S3_CNAME'] ?? '',
        'use_path_style_endpoint' => (bool) $_ENV['S3_USE_PATH_STYLE_ENDPOINT'],
        'cname_is_bucket' => (bool) $_ENV['S3_CNAME_IS_BUCKET'],
        'public' => TRUE,
        'options' => [
          # @todo is this needed?
          'ACL' => 'public-read',
        ],
      ],
      'cache' => TRUE,
      // Without having this, the state value of `drupal_css_cache_files` and
      // `system.js_cache_files` will have a mapping of non-existent files as
      // disk storage is ephemeral in cloud environments.
       'serve_js' => TRUE,
       'serve_css' => TRUE,
    ]
  ];
  $settings['flysystem'] = $schemes;
  // Set the default scheme to be S3.
  $config['system.file']['default_scheme'] = 's3';
}

if ($_ENV['FILESYSTEM_DRIVER'] !== 'local') {
  // If the filesystem isn't local, move Twig to the temporary directory.
  $settings['php_storage']['twig']['directory'] = $settings['file_temp_path'];
}

if (!empty($_ENV['REDIS_HOST']) && !InstallerKernel::installationAttempted() && extension_loaded('redis')) {
  include $app_root . '/' . $site_path . '/settings.redis.php';
}

/**
 * Trusted host configuration.
 *
 * Drupal core can use the Symfony trusted host mechanism to prevent HTTP Host
 * header spoofing.
 *
 * Generally not required if the server only allows verified domains to be
 * routed to the server.
 *
 * @see default.settings.php for more information.
 */
$settings['trusted_host_patterns'][] = '.*';

/**
 * Include local environment overrides.
 */
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
   include $app_root . '/' . $site_path . '/settings.local.php';
}
