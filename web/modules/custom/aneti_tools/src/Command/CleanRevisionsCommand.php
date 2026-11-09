<?php

namespace Drupal\aneti_tools\Command;

use False\MyClass;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Class CleanRevisionsCommand.
 *
 * @DrupalCommand (
 *     extension="aneti_tools",
 *     extensionType="module"
 * )
 */
class CleanRevisionsCommand extends ContainerAwareCommand {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('clean:rev')
      ->setDescription($this->trans('commands.aneti_tools.clean_rev.description'))
      ->addArgument("type", InputArgument::REQUIRED, "Provide the node type." );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $type = $input->getArgument('type');
    if (!in_array($type, ['curriculum_vitae'])) {
      $this->getIo()->error('This node type is not supported yet.');
      return FALSE;
    }


    // Load Nodes with provided type.
    /** @var \Drupal\Core\Entity\EntityTypeManager */
    $entityManager = $this->container->get('entity_type.manager');

    $query = $entityManager->getStorage('node');

    $results = $query->getQuery()
      ->condition('type', $type)
      //->condition('nid', 438)
      //->range(0, 1000)
      ->execute();

    $this->getIo()->info(count($results) . ' items Found.');

    $nids = array_map(function($item) {
      return $item;
    }, $results);

    $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $this->cleanupRevision($node);
    }
    $this->getIo()->info(count($results) . ' items processed.');

  }


  protected function cleanupRevision($node) {
//    try {
      $this->getIo()->info('Processing: node=>' . $node->id());
      $curent_vid = $node->get('vid')->getString();
      $vids = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($node);
      //var_dump($vids);
      var_dump($curent_vid);
      if (count($vids) == 1) {
        return FALSE;
      }
      foreach($vids as $vid){
        if ($vid != $curent_vid) {
          var_dump($vid);
          \Drupal::entityTypeManager()->getStorage('node')->deleteRevision($vid);
        }
      }
//    } catch (Exception $e) {
//      // Latest revision will not be deleted but throws an error
//      return $e->getMessage();
//    }
  }
}
