<?php

namespace Drupal\lightning_core;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\lightning_core\Annotation\Update;
use phpDocumentor\Reflection\DocBlock;
use Symfony\Component\Console\Style\StyleInterface;

class UpdateManager {

  /**
   * The update discovery object.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * The class resolver service.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * UpdateCommand constructor.
   *
   * @param \Traversable $namespaces
   *   The namespaces to scan for updates.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver service.
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   (optional) The update discovery handler.
   */
  public function __construct(\Traversable $namespaces, ClassResolverInterface $class_resolver, DiscoveryInterface $discovery = NULL) {
    $this->classResolver = $class_resolver;
    $this->discovery = $discovery ?: new AnnotatedClassDiscovery('Update', $namespaces, Update::class);
  }

  /**
   * Returns all available update definitions since a given version.
   *
   * @param string $since_version
   *   The version from which to update.
   *
   * @return array[]
   *   The available update definitions.
   */
  public function getAvailable($since_version) {
    $definitions = $this->discovery->getDefinitions();
    ksort($definitions);

    $filter = function (array $definition) use ($since_version) {
      return version_compare($definition['id'], $since_version, '>');
    };
    return array_filter($definitions, $filter);
  }

  /**
   * Returns all available tasks for a specific update.
   *
   * @param object $handler
   *   The task handler.
   *
   * @return \Generator
   *   An iterable of UpdateTask objects.
   */
  protected function getTasks($handler) {
    $methods = (new \ReflectionObject($handler))->getMethods(\ReflectionMethod::IS_PUBLIC);

    foreach ($methods as $method) {
      $doc_comment = trim($method->getDocComment());

      if ($doc_comment) {
        $doc_block = new DocBlock($doc_comment);

        if ($doc_block->hasTag('update')) {
          yield new UpdateTask($handler, $method, $doc_block);
        }
      }
    }
  }

  public function executeAllInConsole($since_version, StyleInterface $style) {
    $definitions = $this->getAvailable($since_version);

    if (sizeof($definitions) === 0) {
      return $style->text('There are no updates available.');
    }
    $style->text("Executing all updates since version $since_version.");

    $module_info = system_rebuild_module_data();
    $provider = NULL;

    foreach ($definitions as $id => $update) {
      if ($update['provider'] != $provider) {
        $provider = $update['provider'];
        $style->text($module_info[$provider]->info['name'] . ' ' . $update['id']);
      }

      $handler = $this->classResolver
        ->getInstanceFromDefinition($update['class']);

      /** @var \Drupal\lightning_core\UpdateTask $task */
      foreach ($this->getTasks($handler) as $task) {
        $task->execute($style);
      }
    }
  }

}
