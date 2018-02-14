<?php
namespace Drupal\lightning_core\Commands;

use Drupal\lightning_core\UpdateManager;
use Drush\Commands\DrushCommands;
use Drush\Style\DrushStyle;

class LightningCoreCommands extends DrushCommands {

  /**
   * The update manager service.
   *
   * @var \Drupal\lightning_core\UpdateManager
   */
  protected $updateManager;

  /**
   * LightningCoreCommands constructor.
   *
   * @param \Drupal\lightning_core\UpdateManager $update_manager
   *   The update manager service.
   */
  public function __construct(UpdateManager $update_manager) {
    $this->updateManager = $update_manager;
  }

  /**
   * Executes Lightning configuration updates from a specific version.
   *
   * @command update:lightning
   *
   * @param string $since_version Argument description.
   *   The semantic version from which to update, e.g. 2.1.7. To run all updates
   *   from the beginning of time, use 0.0.0.
   *
   * @usage update:lightning 2.1.7
   *   Runs all configuration updates since and including Lightning 2.1.7.
   * @usage update:lightning 0.0.0
   *   Runs all configuration updates since the beginning of time.
   */
  public function commandName($since_version) {
    $io = new DrushStyle($this->input(), $this->output());
    $this->updateManager->executeAllInConsole($since_version, $io);
  }

}
