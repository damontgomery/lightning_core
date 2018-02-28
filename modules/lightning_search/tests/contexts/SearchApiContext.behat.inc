<?php

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;

class SearchApiContext extends DrupalSubContextBase {

  /**
   * Reacts when a node is created.
   *
   * If any Search API indexes are configured to index items immediately, they
   * will normally defer the indexing until the end of the page request. This
   * will break Drupal Extension, though, since content created via the API is
   * not done as part of a request and therefore is never indexed. The
   * workaround is to manually invoke Search API's entity insert hook and
   * trigger a kernel termination event on Search API's post-request indexer.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope $scope
   *   The hook scope.
   *
   * @AfterNodeCreate
   */
  public function onNodeCreate(AfterNodeCreateScope $scope) {
    $node = entity_load('node', $scope->getEntity()->nid);
    search_api_entity_insert($node);
    \Drupal::service('search_api.post_request_indexing')->onKernelTerminate();
  }

}
