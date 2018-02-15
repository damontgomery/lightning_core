<?php

namespace Drupal\Tests\lightning_core\Unit;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\lightning_core\UpdateManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\lightning_core\UpdateManager
 *
 * @group lightning
 * @group lightning_core
 */
class UpdateManagerTest extends UnitTestCase {

  /**
   * @covers ::getAvailable
   */
  public function testGetAvailable() {
    $discovery = $this->prophesize(DiscoveryInterface::class);
    $discovery->getDefinitions()->willReturn([
      'fubar:1.2.1' => [
        'id' => '1.2.1',
        'provider' => 'fubar',
      ],
      'fubar:1.2.2' => [
        'id' => '1.2.2',
        'provider' => 'fubar',
      ],
      'fubar:1.2.3' => [
        'id' => '1.2.3',
        'provider' => 'fubar',
      ],
    ]);

    $update_manager = new UpdateManager(
      new \ArrayIterator,
      new ClassResolver,
      $this->prophesize(ConfigFactoryInterface::class)->reveal(),
      $discovery->reveal());

    $definitions = $update_manager->getAvailable('1.2.2');
    $this->assertCount(1, $definitions);
    $this->assertArrayHasKey('fubar:1.2.3', $definitions);
  }

}
