<?php

/**
 * @file
 * dkan_harvest.drush.inc.
 */

/**
 * Implements hook_drush_command().
 */
function dkan_harvest_drush_command() {
  // Cache the remote ckan endpoint.
  $items['dkan-cache-harvested-data'] = array(
    'aliases' => array('dkan-chd'),
    'description' => 'Caches remote data.json endpoints locally',
    'callback' => 'dkan_harvest_drush_chd',
    'options' => array(
      'sources' => 'A list of comma delimited source keys as provided in hook_harvest_sources.',
    ),
  );
  // Runs the migration.
  $items['dkan-migrate-cached-data'] = array(
    'aliases' => array('dkan-mcd'),
    'description' => 'Migrates cached data.json endpoints',
    'callback' => 'dkan_harvest_migrate_data',
  );
  // Cache + Migration run.
  $items['dkan-harvest-run'] = array(
    'aliases' => array('dkan-hr'),
    'description' => 'Caches data and runs the migration for data.json endpoints',
    'callback' => 'dkan_harvest_run',
  );
  return $items;
}


/**
 * Callback function for dkan-chd command.
 */
function dkan_harvest_drush_chd() {
  $options = array();
  $options['sources'] = drush_get_option('sources', FALSE);
  $options['sources'] = $options['sources'] ? explode(',', $options['sources']) : array();
  return dkan_harvest_cache_data($options);
}
