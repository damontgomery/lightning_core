<?php

namespace Drupal\Tests\lightning_core;

use Behat\Behat\Context\Context;
use Drupal\block\Entity\Block;
use Drupal\user\Entity\Role;
use Drupal\views\Entity\View;

/**
 * Performs set-up and tear-down tasks before and after test scenarios.
 */
final class FixtureContext implements Context {

  /**
   * The administrator role created for the test.
   *
   * @var \Drupal\user\RoleInterface
   */
  private $administrator;

  /**
   * Whether the Seven theme was installed during ::setUp().
   *
   * @var bool
   */
  private $sevenInstalled;

  /**
   * The original system.theme config data.
   *
   * @var array
   */
  private $themeConf;

  /**
   * The system_main_block instance placed in Seven during the test.
   *
   * @var \Drupal\block\BlockInterface
   */
  private $contentBlock;

  /**
   * The search block instance placed in Seven during the test.
   *
   * @var \Drupal\block\BlockInterface
   */
  private $searchBlock;

  /**
   * The cache configuration for the default display of search view.
   *
   * @var array
   */
  private $viewConfig;

  /**
   * @BeforeScenario
   */
  public function setUp() {
    // Create the administrator role if it does not already exist.
    if (! Role::load('administrator')) {
      $this->administrator = Role::create([
        'id' => 'administrator',
        'label' => 'Administrator'
      ]);
      $this->administrator->setIsAdmin(TRUE)->save();
    }

    // Install the Seven theme if not already installed.
    if (! \Drupal::service('theme_handler')->themeExists('seven')) {
      $this->sevenInstalled = \Drupal::service('theme_installer')->install(['seven']);
    }

    // Store existing system.theme config, to be restored during tearDown().
    $this->themeConf = \Drupal::config('system.theme')->get();

    // Use Seven as both the default and administrative theme.
    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('admin', 'seven')
      ->set('default', 'seven')
      ->save();

    // Place the main content block if it's not already there.
    if (! Block::load('seven_content')) {
      $this->contentBlock = Block::create([
        'id' => 'seven_content',
        'theme' => 'seven',
        'region' => 'content',
        'plugin' => 'system_main_block',
        'settings' => [
          'label_display' => '0',
        ],
      ]);
      $this->contentBlock->save();
    }

    if (\Drupal::moduleHandler()->moduleExists('lightning_search')) {
      /** @var \Drupal\block\BlockInterface $block */
      if (! Block::load('seven_search')) {
        $this->searchBlock = Block::create([
          'id' => 'seven_search',
          'theme' => 'seven',
          'region' => 'content',
          'plugin' => 'views_exposed_filter_block:search-page',
        ])
          ->setVisibilityConfig('request_path', [
            'pages' => '/search',
          ]);
        $this->searchBlock->save();
      }

      /** @var \Drupal\views\ViewEntityInterface $view */
      $view = View::load('search');
      $display = &$view->getDisplay('default');
      $this->viewConfig = $display['display_options']['cache'];
      $display['display_options']['cache'] = [
        'type' => 'none',
        'options' => [],
      ];
      $view->save();
    }
  }

  /**
   * @AfterScenario
   */
  public function tearDown() {
    if ($this->administrator) {
      $this->administrator->delete();
    }

    // Restore system.theme config.
    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->setData($this->themeConf)
      ->save(TRUE);

    // Delete blocks created during setUp().
    if ($this->contentBlock) {
      $this->contentBlock->delete();
    }
    if ($this->searchBlock) {
      $this->searchBlock->delete();
    }

    // Restore the cache configuration of the search view's default display.
    if ($this->viewConfig) {
      /** @var \Drupal\views\ViewEntityInterface $view */
      $view = View::load('search');
      $display = &$view->getDisplay('default');
      $this->viewConfig = $display['display_options']['cache'];
      $display['display_options']['cache'] = $this->viewConfig;
      $view->save();
    }

    // If setUp() installed Seven, uninstall it.
    if ($this->sevenInstalled) {
      \Drupal::service('theme_installer')->uninstall(['seven']);
    }
  }

}
