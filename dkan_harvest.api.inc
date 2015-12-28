<?php

/**
 * @file
 * dkan_harvest.api.inc.
 */

function dkan_harvest_harvest_sources() {
  return array(
    'source_id' => array(
      'remote' => 'http://data_json_remote',
      'type' => 'data.json',
      // Filter items preseting the following values (Optional).
      'filters' => array('keyword' => array('health')),
      // Exclude items presenting the following values (Optional).
      'excludes' => array('keyword' => array('tabacco')),
      // Provide defaults (Optional).
      'defaults' => array('keyword' => array('harvested dataset'),
      // Provide overrides (Optional).
      'overrides' => array('author' => 'Author'),
    ),
  );
}

function dkan_harvest_harvest_sources_types() {
  return array(
    'data.json' => array(
      'callback' => 'php_callback_function',
      'migration' => 'migration_identifier',
      'identifier' => 'key_to_use_as_identifier',
    ),
  );
}
