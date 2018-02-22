<?php

namespace Drupal\Tests\lightning_core\Unit;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
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

    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('fubar')->willReturn('1.2.2');

    $config_factory = $this->prophesize(ConfigFactoryInterface::class);
    $config_factory->get('lightning.versions')->willReturn($config->reveal());

    $update_manager = new UpdateManager(
      new \ArrayIterator,
      new ClassResolver,
      $config_factory->reveal(),
      $discovery->reveal()
    );

    $definitions = $update_manager->getAvailable();
    $this->assertCount(1, $definitions);
    $this->assertArrayHasKey('fubar:1.2.3', $definitions);
  }

  /**
   * @covers ::toSemanticVersion
   *
   * @dataProvider providerSemanticVersion
   */
  public function testSemanticVersion($drupal_version, $semantic_version) {
    $this->assertSame($semantic_version, UpdateManager::toSemanticVersion($drupal_version));
  }

  public function providerSemanticVersion() {
    return [
      ['8.x-1.12', '1.12.0'],
      ['8.x-1.2-alpha3', '1.2.0-alpha3'],
      ['8.x-2.7-beta3', '2.7.0-beta3'],
      ['8.x-1.42-rc1', '1.42.0-rc1'],
      ['8.x-1.x-dev', '1.x-dev'],
      // This is a weird edge case only used by the Lightning profile.
      ['8.x-3.001', '3.001.0'],
    ];
  }

}
