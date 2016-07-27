<?php

/**
 * @file
 * Code for the DKAN Harvest pages.
 */

/**
 * Display a node's moderation history.
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
 * Display a node's preview migration.
 *
 * @param $node The harvest source node being acted upon.
 *
 * @return A themed table with a migration preview.
 */
function dkan_harvest_page_preview($node) {
  $harvestSource = HarvestSource::getHarvestSourceFromNode($node);
  $output = '';

  // Get the cache callback for the source.
  // This will run a cache operation for the harvest source. Download the file and processed and create cache directory
    $harvestCache = call_user_func(
      $harvestSource->type->cache_callback,
      $harvestSource,
      NULL
    );

  foreach ($harvestCache->getProcessed() as $id => $dataset_item) {
    $rows[] = array($id, $dataset_item['title']);
  }

  $form_import = drupal_get_form('dkan_harvest_form_import_preview', $node);
  $caption = drupal_render($form_import);
  $header = array(t('Identifier'), t('Title'));
  $output .= theme('table', array('rows' => $rows, 'header' => $header, 'caption' => $caption));
  return $output;
}

function dkan_harvest_form_import_preview($form, &$form_state, $node) {
  $form = array();
  $form['#node'] = $node;

  $form['import'] = array(
    '#type' => 'submit',
    '#value' => t('Harvest now'),
  );

  return $form;
}

function dkan_harvest_form_import_preview_submit($form, &$form_state) {
  $harvestSource = HarvestSource::getHarvestSourceFromNode($form['#node']);
  dkan_harvest_cache_sources(array($harvestSource));
  dkan_harvest_migrate_sources(array($harvestSource));
  $form_state['redirect'] = 'admin/dkan/harvest/dashboard';
}
