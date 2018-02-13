<?php

namespace Drupal\lightning_core;

use phpDocumentor\Reflection\DocBlock;
use Symfony\Component\Console\Style\StyleInterface;

class UpdateTask {

  /**
   * The task handler.
   *
   * @var object
   */
  protected $handler;

  /**
   * The reflector for the task method.
   *
   * @var \ReflectionMethod
   */
  protected $reflector;

  /**
   * The doc block for the task method.
   *
   * @var \phpDocumentor\Reflection\DocBlock
   */
  protected $docBlock;

  /**
   * UpdateTask constructor.
   *
   * @param object $handler
   *   The task handler.
   * @param \ReflectionMethod $reflector
   *   The reflector for the task method.
   * @param \phpDocumentor\Reflection\DocBlock $doc_block
   *   The doc block for the task method.
   */
  public function __construct($handler, \ReflectionMethod $reflector, DocBlock $doc_block) {
    $this->handler = $handler;
    $this->reflector = $reflector;
    $this->docBlock = $doc_block;
  }

  /**
   * Asks for confirmation before executing the task.
   *
   * @param \Symfony\Component\Console\Style\StyleInterface $out
   *   The output style.
   *
   * @return bool
   *   TRUE if the task is confirmed, FALSE otherwise.
   */
  protected function confirm(StyleInterface $out) {
    if ($this->docBlock->hasTag('ask')) {
      $tags = $this->docBlock->getTagsByName('ask');
      $tag = reset($tags);

      return $out->confirm($tag->getContent());
    }
    return TRUE;
  }

  /**
   * Prompts for confirmation and executes the task.
   *
   * @param \Symfony\Component\Console\Style\StyleInterface $out
   *   The output style.
   * @param bool $force
   *   (optional) If TRUE, the task is executed without confirmation.
   */
  public function execute(StyleInterface $out, $force = FALSE) {
    $proceed = $force ? TRUE : $this->confirm($out);

    if ($proceed) {
      $this->reflector->invoke($this->handler, $out);
    }
  }

}
