<?php

namespace Drupal\lightning_core\Command;

use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
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
   * The class resolver service.
   *
   * @var ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The version from which we are updating.
   *
   * @var string
   */
  protected $since = NULL;

  /**
   * UpdateCommand constructor.
   *
   * @param UpdateManager $update_manager
   *   The update manager service.
   * @param ClassResolverInterface $class_resolver
   *   The class resolver service.
   */
  public function __construct(UpdateManager $update_manager, ClassResolverInterface $class_resolver) {
    parent::__construct('update:lightning');
    $this->updateManager = $update_manager;
    $this->classResolver = $class_resolver;
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
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    $this->since = $input->getArgument('since');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $definitions = $this->updateManager->getAvailable($this->since);

    if (sizeof($definitions) === 0) {
      return $output->writeln('There are no updates available.');
    }
    $output->writeln("Executing all updates since version $this->since.");

    $io = new DrupalStyle($input, $output);
    $module_info = system_rebuild_module_data();
    $provider = NULL;

    foreach ($definitions as $id => $update) {
      if ($update['provider'] != $provider) {
        $provider = $update['provider'];
        $output->writeln($module_info[$provider]->info['name'] . ' ' . $update['id']);
      }

      $handler = $this->classResolver
        ->getInstanceFromDefinition($update['class']);

      /** @var \Drupal\lightning_core\UpdateTask $task */
      foreach ($this->updateManager->getTasks($handler) as $task) {
        $task->execute($io);
      }
    }
  }

}
