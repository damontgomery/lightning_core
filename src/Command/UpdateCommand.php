<?php

namespace Drupal\lightning_core\Command;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\lightning_core\UpdateManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command {

  /**
   * The update manager service.
   *
   * @var \Drupal\lightning_core\UpdateManager
   */
  protected $updateManager;

  /**
   * UpdateCommand constructor.
   *
   * @param UpdateManager $update_manager
   *   The update manager service.
   */
  public function __construct(UpdateManager $update_manager) {
    parent::__construct('update:lightning');
    $this->updateManager = $update_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();

    $this->addArgument(
      'since',
      InputArgument::REQUIRED,
      'The version of Lightning you are updating from, in semantic version format (major.minor.patch). To run all updates since the beginning of time, use 0.0.0.'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    $this->updateManager->executeAllInConsole($input->getArgument('since'), $io);
  }

}
