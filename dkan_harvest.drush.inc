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
    'callback' => 'dkan_harvest_cache',
    'drupal dependencies' => array('dkan_harves'),
  );
  // Runs the migration.
  $items['dkan-migrate-cached-data'] = array(
    'aliases' => array('dkan-mcd'),
    'description' => 'Migrates cached data.json endpoints',
    'callback' => 'dkan_harvest_migrate',
    'drupal dependencies' => array('dkan_harvest'),
  );
  // Cache + Migration run.
  $items['dkan-harvest-run'] = array(
    'aliases' => array('dkan-hr'),
    'description' => 'Caches data and runs the migration for data.json endpoints',
    'callback' => 'dkan_harvest_run',
    'drupal dependencies' => array('dkan_harvest'),
  );

  // Run harvest cache operation on the source passed as argument.
  $items['dkan-harvest-cache'] = array(
    'aliases' => array('dkan-hc'),
    'description' => 'Run a harvest cache on a single source or all the available sources if the argument is not provided.',
    'callback' => 'dkan_harvest_drush_command_cache',
    'arguments' => array(
      'source_machine_name' =>
      'The source machine name to run the harvest caching on',
    ),
    'drupal dependencies' => array('dkan_harvest'),
  );

  // Run harvest cache operation on the source passed as argument.
  $items['dkan-harvest-migrate'] = array(
    'aliases' => array('dkan-hm'),
    'description' => 'Run a harvest migrate on a single source or all the available sources if the argument is not provided.',
    'callback' => 'dkan_harvest_drush_command_migrate',
    'arguments' => array(
      'source_machine_name' => 'The source machine name to run the harvest migration on. If not provided run the harvest ',
    ),
    'options' => array(
      'skip-hash' => 'Skip hash checking and update all the datasets available in the source.',
    ),
    'drupal dependencies' => array('dkan_harvest'),
  );

  // Run harvest cache operation on the source passed as argument.
  $items['dkan-harvest-rollback'] = array(
    'aliases' => array('dkan-hr'),
    'description' => 'Run a harvest rollback on a single source.',
    'callback' => 'dkan_harvest_drush_command_rollback',
    'arguments' => array(
      'source_machine_name' => 'The source machine name to run the harvest migration on. If not provided run the harvest ',
    ),
    'drupal dependencies' => array('dkan_harvest'),
  );

  // Run harvest cache operation on the source passed as argument.
  $items['dkan-harvest-status'] = array(
    'aliases' => array('dkan-hs'),
    'description' => 'Status Harvest Sources available',
    'callback' => 'dkan_harvest_drush_command_status',
    'drupal dependencies' => array('dkan_harvest'),
  );

  // Run harvest cache operation on the source passed as argument.
  $items['dkan-harvest-deregister'] = array(
    'aliases' => array('dkan-hdr'),
    'description' => 'Run a harvest rollback on a single source.',
    'callback' => 'dkan_harvest_drush_command_deregister',
    'arguments' => array(
      'source_machine_name' => 'The source machine name to run the harvest migration on. If not provided run the harvest ',
    ),
    'drupal dependencies' => array('dkan_harvest'),
  );

  return $items;
}

/**
 * Run harvest cache operation on the source passed as argument.
 *
 * @param $source_machine_name Source machine name to cache
 */
function dkan_harvest_drush_command_cache($source_machine_name) {
  if (isset($source_machine_name)) {
    if ($source = HarvestSource::getSourceByMachineName($source_machine_name)) {
      drush_log("Running the harvest migration on " . $source_machine_name, 'notice');
      return dkan_harvest_cache_sources(array($source), microtime());
    } else {
      drush_log("No source with machine name " . $source_machine_name . " found.", 'error');
    }
  } else {
    drush_log("Running the harvest caching on all the available sources.", 'notice');
    dkan_harvest_cache();
  }
}

/**
 * Run harvest migrate operation on the source passed as argument.
 *
 * @param $source_machine_name Source machine name to migrate
 */
function dkan_harvest_drush_command_migrate($source_machine_name) {
  $options = array(
    'dkan_harvest_skip_hash' => drush_get_option('skip-hash', FALSE),
    // TODO add default migrate options
  );
  if (isset($source_machine_name)) {
    if ($source = HarvestSource::getSourceByMachineName($source_machine_name)) {
      drush_log("Running the harvest migration on " . $source_machine_name, 'notice');
      dkan_harvest_migrate_sources(array($source), $options);
    }
    else {
      drush_log("No source with machine name " . $source_machine_name . " found.", 'error');
    }
  }
  else {
    drush_log("Running the harvest migration on all the available sources.", 'notice');
    dkan_harvest_migrate();
  }
}

/**
 * Run harvest migrate operation on the source passed as argument.
 *
 * @param $source_machine_name Source machine name to migrate
 */
function dkan_harvest_drush_command_rollback($source_machine_name) {
  if (!isset($source_machine_name)) {
    $message = t("No source to rollback.");
    drush_log($message, 'error');
    return FALSE;
  }

  $source = HarvestSource::getSourceByMachineName($source_machine_name);
  if (!$source) {
    $message = t("No source with machine name %source_machine_name found.",
      array(
        '%source_machine_name' => $source_machine_name,
      ));
    drush_log($message, 'error');
    return FALSE;
  }

  $message = t("Running the harvest migration on %source_machine_name",
    array(
      '%source_machine_name' => $source_machine_name,
    ));
  drush_log($message, 'notice');
  dkan_harvest_rollback_sources(array($source), $options);
}

/**
 * Relevent Info about the available Harvest Sources.
 */
function dkan_harvest_drush_command_status() {
  $harvest_sources = dkan_harvest_sources_definition();

  $rows = array();

  $rows[] = array(
    'machine name' => 'Machine Name',
    'label' => 'Label',
    'uri' => 'URI',
    'type' => 'Type',
  );

  foreach ($harvest_sources as $harvest_source) {
    $rows[] = array(
      'machine name' => $harvest_source->machine_name,
      'label' => $harvest_source->label,
      'uri' => $harvest_source->uri,
      'type' => $harvest_source->type->label,
    );
  }

  drush_print_table($rows, TRUE);
}

/**
 * Drop the harvest migrate information about a harvest source.
 *
 * @param $source_machine_name
 */
function dkan_harvest_drush_command_deregister($source_machine_name) {
  if (!isset($source_machine_name)) {
    $message = t("No source to deregister");
    drush_log($message, 'error');
    return FALSE;
  }

  $harvestSource = HarvestSource::getSourceByMachineName($source_machine_name);

  if (!$harvestSource) {
    drush_log(t("Harvest Source not found!"), 'error');
    return FALSE;
  }

  dkan_harvest_deregister_sources(array($harvestSource));
}
