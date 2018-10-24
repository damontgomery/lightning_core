<?php

// Create the administrator role if it doesn't already exist.
$role = entity_load('user_role', 'administrator');
if ($role == NULL) {
  /** @var \Drupal\user\RoleInterface $role */
  $role = entity_create('user_role', [
    'id' => 'administrator',
    'label' => 'Administrator'
  ]);
  $role->setIsAdmin(TRUE)->save();
}

Drupal::service('theme_installer')->install(['seven']);

Drupal::configFactory()
  ->getEditable('system.theme')
  ->set('admin', 'seven')
  ->set('default', 'seven')
  ->save();

// Place the main content block if it's not already there.
$block_storage = Drupal::entityTypeManager()->getStorage('block');
$block = $block_storage->load('seven_content') ?: $block_storage->create([
  'id' => 'seven_content',
  'theme' => 'seven',
  'region' => 'content',
  'plugin' => 'system_main_block',
  'settings' => [
    'label_display' => '0',
  ],
]);
$block_storage->save($block);

if (Drupal::moduleHandler()->moduleExists('lightning_search')) {
  /** @var \Drupal\block\BlockInterface $block */
  $block = entity_create('block', [
    'id' => 'seven_search',
    'theme' => 'seven',
    'region' => 'content',
    'plugin' => 'views_exposed_filter_block:search-page',
  ]);
  $block->setVisibilityConfig('request_path', [
    'pages' => '/search',
  ]);
  $block->save();

  /** @var \Drupal\views\ViewEntityInterface $view */
  $view = entity_load('view', 'search');
  $display = &$view->getDisplay('default');
  $display['display_options']['cache'] = [
    'type' => 'none',
    'options' => [],
  ];
  $view->save();
}
