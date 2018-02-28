<?php

namespace Drupal\Tests\lightning_core\Kernel\Update;

use Drupal\KernelTests\KernelTestBase;
use Drupal\lightning_core\UpdateManager;

/**
 * @group lightning_core
 * @group lightning
 */
class Update8006Test extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lightning_core', 'system', 'user'];

  public function testUpdate() {
    module_load_install('lightning_core');
    lightning_core_update_8006();

    $config = $this->container->get('config.factory')
      ->get(UpdateManager::CONFIG_NAME);

    foreach (static::$modules as $module) {
      $this->assertSame(UpdateManager::VERSION_UNKNOWN, $config->get($module));
    }
  }

}
