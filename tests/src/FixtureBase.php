<?php

namespace Drupal\Tests\lightning_core;

use Behat\Behat\Context\Context;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for contexts which set up and tear down a complete test fixture.
 */
abstract class FixtureBase implements Context, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * Modules installed during the scenario.
   *
   * @var string[]
   */
  private $modules = [];

  /**
   * Themes installed during the scenario.
   *
   * @var string[]
   */
  private $themes = [];

  /**
   * @var EntityInterface[]
   */
  private $entities = [];

  /**
   * @var \Drupal\Core\Config\Config[]
   */
  private $config = [];

  /**
   * FixtureBase constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   (optional) The service container.
   */
  public function __construct(ContainerInterface $container = NULL) {
    $this->setContainer($container ?: \Drupal::getContainer());
  }

  /**
   * Returns a config object and caches its data for automatic restoration.
   *
   * @param string $id
   *   The name of the config object to return.
   *
   * @see ::tearDown()
   *
   * @return \Drupal\Core\Config\Config
   */
  protected function config($id) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->container->get('config.factory')->getEditable($id);

    if (empty($this->config[$id])) {
      $this->config[$id] = $config->getRawData();
    }
    return $config;
  }

  /**
   * Saves an entity and marks it for automatic deletion.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to save.
   *
   * @see ::tearDown()
   */
  protected function save(EntityInterface $entity) {
    $entity->save();
    array_push($this->entities, $entity);
  }

  /**
   * Installs a module if not already present.
   *
   * @param string $module
   *   The machine name of the module to install.
   */
  protected function installModule($module) {
    if ($this->container->get('module_handler')->moduleExists($module)) {
      return;
    }
    elseif ($this->container->get('module_installer')->install([$module])) {
      array_push($this->modules, $module);
    }
  }

  /**
   * Installs a theme if not already present.
   *
   * @param string $theme
   *   The machine name of the theme to install.
   */
  protected function installTheme($theme) {
    if ($this->container->get('theme_handler')->themeExists($theme)) {
      return;
    }
    elseif ($this->container->get('theme_installer')->install([$theme])) {
      array_push($this->themes, $theme);
    }
  }

  /**
   * Tears down the fixture.
   */
  protected function tearDown() {
    while ($this->entities) {
      array_pop($this->entities)->delete();
    }

    foreach ($this->config as $id => $data) {
      $this->container->get('config.factory')
        ->getEditable($id)
        ->setData($data)
        ->save(TRUE);
    }

    if ($this->themes) {
      $this->container->get('theme_installer')->uninstall($this->themes);
    }

    if ($this->modules) {
      $this->container->get('module_installer')->uninstall($this->modules);
    }
  }

}
