<?php declare(strict_types=1);

namespace BluehornDigital\Mojo;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

final class DrupalScaffoldPlugin implements PluginInterface, EventSubscriberInterface
{

  private const PACKAGE_NAME = 'bluehorndigital/mojo-drupal-scaffold';

  private Composer $composer;

  public function activate(Composer $composer, IOInterface $io)
  {
    $this->composer = $composer;
    $this->io = $io;
  }

  public function deactivate(Composer $composer, IOInterface $io)
  {
  }

  public function uninstall(Composer $composer, IOInterface $io)
  {
    $package = $this->composer->getPackage();
    $extra = $package->getExtra();
    if (!empty($extra['drupal-scaffold']['allowed-packages'])) {
      $key = array_search(self::PACKAGE_NAME, $extra['drupal-scaffold']['allowed-packages'], true);
      if ($key !== false) {
        unset($extra['drupal-scaffold']['allowed-packages'][$key]);
        $package->setExtra($extra);
        $configSource = $this->composer->getConfig()->getConfigSource();
        $configSource->addProperty('extra.drupal-scaffold.allowed-packages', $extra['drupal-scaffold']['allowed-packages']);
      }
    }
  }

  public static function getSubscribedEvents()
  {
    return [
      PackageEvents::POST_PACKAGE_INSTALL => 'postPackage',
    ];
  }

  public function postPackage(PackageEvent $event)
  {
    $operation = $event->getOperation();
    assert($operation instanceof InstallOperation);
    if ($operation->getPackage()->getName() === self::PACKAGE_NAME) {
      $package = $this->composer->getPackage();
      $extra = $package->getExtra();
      if (empty($extra['drupal-scaffold']['allowed-packages'])) {
        $extra['drupal-scaffold']['allowed-packages'] = [];
      }
      if (!in_array(self::PACKAGE_NAME, $extra['drupal-scaffold']['allowed-packages'], true)) {
        $extra['drupal-scaffold']['allowed-packages'][] = self::PACKAGE_NAME;
      }
      if (empty($extra['drupal-scaffold']['file-mapping'])) {
        $extra['drupal-scaffold']['file-mapping'] = [];
      }

      $fileMappingExclude = [
        '[web-root]/.gitignore',
        '[web-root]/example.gitignore',
        '[web-root]/INSTALL.txt',
        '[web-root]/README.md',
      ];
      foreach ($fileMappingExclude as $fileMapping) {
        $extra['drupal-scaffold']['file-mapping'][$fileMapping] = false;
      }

      $package->setExtra($extra);
      $configSource = $this->composer->getConfig()->getConfigSource();
      $configSource->addProperty('extra.drupal-scaffold.allowed-packages', $extra['drupal-scaffold']['allowed-packages']);
      $configSource->addProperty('extra.drupal-scaffold.file-mapping', $extra['drupal-scaffold']['file-mapping']);

      $autoload = $package->getAutoload();
      if (empty($autoload['files'])) {
        $autoload['files'] = [];
      }
      if (!in_array('load.environment.php', $autoload['files'])) {
        $autoload['files'][] = 'load.environment.php';
      }
      $package->setAutoload($autoload);
      $configSource->addProperty('autoload', $autoload);
    }
  }

}
