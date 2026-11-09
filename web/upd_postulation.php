<?php
$node_storage = \Drupal::entityTypeManager()->getStorage('node');


$query = $node_storage->getQuery();
$query->accessCheck(FALSE);

$query->condition('type','postulation');

		
$group = $query->orConditionGroup()
  ->condition('field_candidature', 0)
  ->notExists('field_candidature');
  
$query->condition($group);

$one_year_ago_timestamp = strtotime('-1 year');

echo "Time :".$one_year_ago_timestamp."\n";
$date_or_group = $query->orConditionGroup()
  ->condition('created', $one_year_ago_timestamp, '<')
  ->condition('changed', $one_year_ago_timestamp, '<');
$query->condition($date_or_group);


$nids = $query->execute();

if (empty($nids)) {
  echo "[INFO] Aucun contenu à mettre à jour.\n";
  exit;
}

echo "[INFO] " . count($nids) . " nœuds détectés (Dry-run: " . ($dry_run ? 'OUI' : 'NON') . ").\n";
  			

$chunks = array_chunk($nids, 20); // Groupes de 20 pour préserver la mémoire
$processed = 0;

foreach ($chunks as $chunk) {
  $nodes = $node_storage->loadMultiple($chunk);
  
  foreach ($nodes as $node) {
    echo "ID: " . $node->id() . " | Titre: " . $node->getTitle() . "\n";
    if (!$dry_run) {
    	 if ($node->hasField('field_candidature') && intval($node->get('field_candidature')->value) == 0) {
		      // Modification du champ liste cible  Non Retenu par l'ANETI
		      $node->set('field_candidature', 4);
		      
		      // Conservation de la date de modification historique
		      //$node->setChangedTime($node->getChangedTime()); 
		      $originalChangedTimestamp = $node->getChangedTime();

		      
		      //Empêcher la création d'une nouvelle révision
		      if ($node->get('vid')->value) {
		        $node->setNewRevision(FALSE);
		      }
		      
		      //$node->setSyncing(TRUE);
		      //$node->changed->preserve = TRUE;
		      
		      // Sauvegarde du nœud
		      $node->save();
		      
		      $node->setChangedTime($originalChangedTimestamp)->save();
		      
		      $processed++;		      
		 }
    }    
  }
  
  // Nettoyage du cache mémoire Drupal après chaque lot
  \Drupal::entityTypeManager()->getStorage('node')->resetCache($chunk);
  
  print "Progression : $processed / $total nœuds mis à jour.\n";
  
}

echo "\n--- Traitement terminé avec succès ! ---\n";