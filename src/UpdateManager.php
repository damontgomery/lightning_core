<?php

namespace Drupal\lightning_core;

use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\lightning_core\Annotation\Update;
use phpDocumentor\Reflection\DocBlock;

class UpdateManager {

  /**
   * The update discovery object.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * UpdateCommand constructor.
   *
   * @param \Traversable $namespaces
   *   The namespaces to scan for updates.
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $discovery
   *   (optional) The update discovery handler.
   */
  public function __construct(\Traversable $namespaces, DiscoveryInterface $discovery = NULL) {
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
  public function getTasks($handler) {
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

}
