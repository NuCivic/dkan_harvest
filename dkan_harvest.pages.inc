<?php

/**
 * Display a harvest source node's event log history.
 *
 * @param $node The harvest source node being acted upon.
 *
 * @return A themed table of data and links for harvest source node events.
 */
function dkan_harvest_page_event_log($node) {
  // Set the table header.
  $header = array(
    'mlid' => array('data' => t('Event ID'), 'field' => 'mlid'),
    'starttime' => array('data' => t('Date'), 'field' => 'starttime', 'sort' => 'desc'),
    'duration' => array('data' => t('Duration'), 'field' => 'duration'),
    'created' => array('data' => t('Created'), 'field' => 'created'),
    'updated' => array('data' => t('Updated'), 'field' => 'updated'),
    'failed' => array('data' => t('Failed'), 'field' => 'failed'),
    'orphaned' => array('data' => t('Orphaned'), 'field' => 'orphaned'),
    'unchanged' => array('data' =>t('Unchanged'), 'field' => 'unchanged'),
  );

  $harvestSource = HarvestSource::getHarvestSourceFromNode($node);
  $harvestSourceMigration = dkan_harvest_get_migration($harvestSource);
  $logTable = $harvestSourceMigration->getMap()->getLogTable();

  $query = db_select('migrate_log', 'log');

  $query->join($logTable, 'harvest_source_log', 'log.mlid = harvest_source_log.mlid');

  $query_result = $query
    ->fields('log')
    ->fields('harvest_source_log')
    ->condition('machine_name', $harvestSource->getMigrationMachineName())
    // Add table sort extender.
    ->extend('TableSort')
    // Add order by headers.
    ->orderByHeader($header);

  $query->addExpression('log.endtime - log.starttime', 'duration');

  $query_result = $query->execute();

  $results = $query_result->fetchAllAssoc('mlid');

  // Convert from microsecnod to seconds.
  function timefloor ($microtime) {
    return floor($microtime/1000);
  }

  // Build the table rows.
  $rows = array();
  foreach ($results as $result) {
    $row = array(
      'data' => array(
        'mlid' => $result->mlid,
        'date' => format_date(timefloor($result->starttime)),
        'duration' => format_interval(timefloor($result->duration), 2),
        'created' => $result->created,
        'updated' => $result->updated,
        'failed' => $result->failed,
        'orphaned' => $result->orphaned,
        'unchanged' => $result->unchanged,
      ),
      'class' => array('harvest-event'),
    );

    $rows[] = $row;
  }


  // Return properly styled output.
  $build['pager_table'] = array(
    '#attached' => array(
      'css' => array(),
    ),
    '#theme' => 'table',
    '#attributes' => array(
       'class' => array('harvest-event-log'),
    ),
    '#header' => $header,
    '#rows' => $rows,
  );

  // Attach the pager theme.
  $build['pager_pager'] = array('#theme' => 'pager');

  return $build;
}

/**
 * Determines whether the current user may access the harvest source event log
 * page.
 *
 * @param $node The harvest source node being acted upon.
 *
 * @return TRUE if access is granted. Otherwise FALSE.
 */
function dkan_harvest_page_error_log_access($node) {
  // Only available for harves source type nodes.
  if ($node->type != 'harvest_source') {
    return FALSE;
  }

  // If the user have access to the harvest node. Grant her/him access to the
  // event log.
  return node_access('view', $node);
}


/**
 * Display a harvest source node's error log history.
 *
 * @param $node The harvest source node being acted upon.
 *
 * @return A themed table of data and links for harvest source node events.
 */
function dkan_harvest_page_error_log($node) {
  // Set the table header.
  $collapsible_inner_header = array(
    'msgid' => array('data' => t('Message ID'), 'field' => 'msgid'),
    'sourceid1' => array('data' => t('Source ID'), 'field' => 'sourceid1'),
    'dataset_link' => array('data' => t('Dataset'),),
    'message' => array('data' => t('Error Message'), 'field' => 'message'),
  );

  $harvestSource = HarvestSource::getHarvestSourceFromNode($node);
  $harvestSourceMigration = dkan_harvest_get_migration($harvestSource);
  $harvestSourceMigrationMap = $harvestSourceMigration->getMap();

  $query = db_select($harvestSourceMigrationMap->getMessageTable(), 'harvest_source_message')
    ->fields('harvest_source_message')
    ->condition('level', Migration::MESSAGE_ERROR)
    // Add table sort extender.
    ->extend('TableSort')
    // Add order by headers.
    ->orderByHeader($collapsible_inner_header);

  $query_result = $query->execute();

  $results = $query_result->fetchAllAssoc('msgid');

  // Build the table rows.
  $collapsible_items = array();
  foreach ($results as $result) {
    if (isset($result->sourceid1)){
      // Lookup the dataset nid. lookupDestinationID() takes an array and
      // returns an array, But really we are interested in one sincle result.
      $sourceid = $result->sourceid1;
      $destids = $harvestSourceMigrationMap->lookupDestinationID(array($sourceid));
      $destid = array_pop($destids);
      if (!is_null($destid)) {
        $result->dataset_link = l(t('Dataset link'), 'node/'. $destid);
      }
    }

    $row = array(
      'data' => array(
        'msgid' => $result->msgid,
        'sourceid1' => $result->sourceid1,
        'dataset_link' => isset($result->dataset_link) ? $result->dataset_link : "N/A",
        'message' => $result->message,
      ),
      'class' => array('harvest-error'),
    );

    $collapsible_items[$result->mlid][] = $row;
  }

  $build = array();
  $collapsible_item_offset = 0;
  foreach ($collapsible_items as $collapsible_item_mlid => $collapsible_item) {
    $collapsible_inner_table = array(
      '#attached' => array(
        'css' => array(),
    ),
    '#theme' => 'table',
    '#attributes' => array(
      'class' => array(
        'harvest-error-log-table',
        'harvest-error-log-table-' . $collapsible_item_mlid,
      ),
    ),
    '#header' => $collapsible_inner_header,
    '#rows' => $collapsible_item,
  );

    // Return properly styled output.
    $handle = t('@errors_count Error(s) encountered on @date',
      array(
        '@errors_count' => count($collapsible_item),
        '@date' => format_date(HarvestSource::getMigrationTimestampFromMlid($collapsible_item_mlid)),
      ));

    $build['collapsible_row_' . $collapsible_item_mlid] = array(
      '#attached' => array(
        'css' => array(
          drupal_get_path('module', 'dkan_harvest') . '/css/dkan_harvest_pages_error_log_collapsible.css'
        ),
      ),
      '#theme' => 'ctools_collapsible',
      '#attributes' => array(
        'class' => array(
          'harvest-error-log-collapse'
        ),
      ),
      '#handle' => $handle,
      '#content' => drupal_render($collapsible_inner_table),
      '#collapsed' => $collapsible_item_offset > 0 ? TRUE : FALSE,
    );

    $collapsible_item_offset++;
  }



  // Attach the pager theme.
  $build['pager_pager'] = array('#theme' => 'pager');

  return $build;
}
