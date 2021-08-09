#!/usr/bin/env php
<?php

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Command\ServerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$classloader = require __DIR__ . '/vendor/autoload.php';

$application = new Application('mojo', \Drupal::VERSION);

$application->add(new class ('env:generate') extends Command {
  protected function configure() {
    $this->addOption('sqlite', null, InputOption::VALUE_NONE, 'Set the DB_CONNECTION to SQLite by default');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) {
      copy(__DIR__ . '/.env.example', $envPath);
    }

    $key = Crypt::randomBytesBase64(55);
    $existingKey = $_ENV['DRUPAL_HASH_SALT'] ?? '';
    $escapedHashSalt = preg_quote('=' . $existingKey, '/');

    $patterns = ["/^DRUPAL_HASH_SALT{$escapedHashSalt}/m"];
    $replacements = ['DRUPAL_HASH_SALT='.$key];

    if ($input->getOption('sqlite')) {
      $patterns[] = "/^DB_CONNECTION=mysql/m";
      $replacements[] = 'DB_CONNECTION=sqlite';
    }


    file_put_contents($envPath, preg_replace(
      $patterns,
      $replacements,
      file_get_contents($envPath)
  ));

    return 0;
  }
});
$application->add(new class ($classloader) extends ServerCommand {

  protected function configure() {
    parent::configure();
    $this->setName('serve');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    // @todo this should error if the site is not installed.
    // Change the directory to the Drupal root.
    chdir('web');
    return parent::execute($input, $output);
  }

});

$application->run();
