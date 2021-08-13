<?php declare(strict_types=1);

assert(isset($settings) && is_array($settings));

// If the filesystem isn't local, move Twig to the temporary directory.
$settings['php_storage']['twig']['directory'] = $settings['file_temp_path'];

// We cannot support a private file path if using object storage.
unset($settings['file_private_path']);

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
