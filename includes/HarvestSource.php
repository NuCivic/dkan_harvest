<?php

/**
 * @file
 * File for dkan_harvest HarvestSource class. This will serve as a in code
 * documentation as well, please update the comments if you update the class!
 */

/**
 * Dkan Harvest HarvestSource Object is user to store the sources properties needed to
 * indentify a source to harvest. Those properties are:
 *
 * - 'machine_name' (Required): Unique identifier for this source.
 * - 'uri' (Required): Location of the content to harvest for this source. This can be a
 * standard URL 'http://data_json_remote' or a local file path '/home/test/source/file'.
 * - 'type' (Required): Type of the endpoint protocol that this source is pulling from.
 * - 'name' (String, Optional): User friendly name used to display this source. If
 * empty will use the 'machine_name' property.
 * - 'filters' => array('keyword' => array('health')) (Optional): Filter items
 * preseting the following values.
 * - 'excludes' => array('keyword' => array('tabacco')) (Optional): Exclude
 * items presenting the following values (Optional).
 * - 'defaults' => array('keyword' => array('harvested dataset') (Optional):
 * Provide defaults.
 * - 'overrides' => array('author' => 'Author') (Optional): Provide overrides .
 */

class HarvestSource {
  public $machine_name;
  public $uri;
  public $type;
  public $label;
  public $filters = array();
  public $excludes = array();
  public $defaults = array();
  public $overrides = array();

  /**
   * Constructor for HarvestSource class.
   *
   * @param Array source: Source array containing atleast all the required
   * source elements (As documented above) and any other optional proprety.
   */
  public function __construct($machine_name, Array $source) {
    // Required properties.
    if (!is_string($machine_name) || empty($machine_name)) {
      throw new Exception('HarvestSource machine_name invalid!');
    }
    else {
      $this->machine_name = $machine_name;
    }

    if (!isset($source['uri']) || !is_string($source['uri'])) {
      throw new Exception('HarvestSource uri invalid!');
    }
    else {
      $this->uri = $source['uri'];
    }

    // TODO Make sure the type exists.
    if (!isset($source['type']) || !is_string($source['type'])) {
      throw new Exception('HarvestSource type invalid!');
    }
    else {
      // This should throw an exception if the type is not found.
      $this->type = HarvestSourceType::getSourceType($source['type']);
    }

    // Optional properties.
    if (!isset($source['label']) || !is_string($source['label'])) {
      $this->label = $this->machine_name;
    }
    else {
      $this->label = $source['label'];
    }

    foreach (array('filters', 'excludes', 'defaults', 'overrides') as $optional) {
      if (isset($source[$optional])) {
        $this->{$optional} = $source[$optional];
      }
    }
  }

  /**
   * Check if the source uri is a remote.
   */
  public function isRemote() {
    $remote = FALSE;
    $scheme = parse_url($this->uri, PHP_URL_SCHEME);
    if (($scheme == 'http' || $scheme == 'https')) {
      $remote = TRUE;
    }
    return $remote;
  }

/**
 * Get the cache directory for a specific source.
 *
 * @param Boolean $create: create the cache diretory if it does not exist.
 *
 * @return string
 * PHP filesteream location. Or FALSE if the cache directory does not exist.
 */
  public function getCacheDir($create_or_clear = FALSE) {
    $cache_dir_path = DKAN_HARVEST_CACHE_DIR . '/' . $this->machine_name;
    $options = FILE_MODIFY_PERMISSIONS;
    if ($create_or_clear) {
      file_unmanaged_delete_recursive($cache_dir_path);
      $options = FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY;
    }

    // Checks that the directory exists and is writable, create if
    // $create_or_clear is TRUE.
    return file_prepare_directory($cache_dir_path, $options) ?
      $cache_dir_path : FALSE;
  }

  /**
   * Generate a migration machine name from the source machine name suitable for in
   * MigrationBase::registerMigration().
   */
  public function getMigrationMachineName() {
    return self::getMigrationMachineNameFromName($this->machine_name);
  }

  /**
   * Generate a migration machine name from the source machine name suitable for in
   * MigrationBase::registerMigration().
   */
  public static function getMigrationMachineNameFromName($machine_name) {
    $migration_name = DKAN_HARVEST_MIGRATION_PREFIX . $machine_name;
    return self::getMachineNameFromName($migration_name);
  }

  /**
   * Generic function to convert a string to a Drupal machine name.
   *
   * @param String $human_name string to convert to machine name.
   *
   * TODO Not sure that this is needed anymore.
   */
  public static function getMachineNameFromName($human_name) {
    return preg_replace('@[^a-z0-9-]+@', '_', strtolower($human_name));
  }

  /**
   * Query a single harvest source by machine_name.
   *
   * @param $machine_name: String source machine_name.
   *
   * @return HarvestSource source if found. Else return FALSE.
   */
  public static function getSourceByMachineName($machine_name) {
    $query = new EntityFieldQuery();

    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'harvest_source')
      ->propertyCondition('status', NODE_PUBLISHED)
      ->fieldCondition('field_dkan_harvest_machine_name', 'machine', $machine_name);

    $result = $query->execute();

    if (isset($result['node'])) {
      $harvest_source_nids = array_keys($result['node']);
      $harvest_source_node = entity_load_single('node', array_pop($harvest_source_nids));
      return self::getHarvestSourceFromNode($harvest_source_node);
    }

    // Something went wrong.
    // TODO log this?
    return FALSE;
  }

  /**
   * Get a HarvestSource object from a harvest_source node.
   *
   * @param $harvest_source_node harvest_source node.
   *
   * @return HarvestSource object.
   *
   * @throws Exception if HarvestSource creation fail.
   */
  public static function getHarvestSourceFromNode(stdClass $harvest_source_node) {
    $harvest_source_emw = entity_metadata_wrapper('node', $harvest_source_node);

    $source = array();
    $source['label'] = $harvest_source_emw->title->value();

    $machine_name = $harvest_source_emw->field_dkan_harvest_machine_name->machine->value();

    if (isset($harvest_source_emw->field_dkan_harvest_source_uri)) {
      $source['uri'] = $harvest_source_emw->field_dkan_harvest_source_uri->value();
    }

    if (isset($harvest_source_emw->field_dkan_harveset_type)) {
      $source['type'] = $harvest_source_emw->field_dkan_harveset_type->value();
    }

    $optionals = array(
      'filters' => 'field_dkan_harvest_filters',
      'excludes' => 'field_dkan_harvest_excludes',
      'overrides' => 'field_dkan_harvest_overrides',
      'defaults' => 'field_dkan_harvest_defaults',
    );

    foreach ($optionals as $property => $field) {
      $property_value = array();
      $field_double = $harvest_source_emw->{$field}->value();
      foreach ($field_double as $key => $value) {
        $property_value[$value['first']] = explode(',', $value['second']);
      }
      $source[$property] = $property_value;
    }

    return new HarvestSource($machine_name, $source);
  }

  /**
   * Query the migrate_log table to get the last time the harvest source
   * migration run.
   *
   * @param string @machine_name Harvest Source machine name.
   *
   * @return Timestamp of the last Harvest Migration run. Or NULL if source not
   * found or not run yet.
   */
  public static function getMigrationTimestampFromMachineName($machine_name) {
   $migration_machine_name = HarvestSource::getMigrationMachineNameFromName($machine_name);

   // Get the last time (notice the MAX) the migration was run.
   $result = db_query("SELECT MAX(starttime) FROM {migrate_log} WHERE machine_name =
     :migration_machine_name ORDER BY starttime ASC limit 1;", array(':migration_machine_name' =>
     $migration_machine_name));

   $result_array = $result->fetchAssoc();

   if (!empty($result_array)) {
     $harvest_migrate_date = array_pop($result_array);
     // Migrate saves the timestamps with microseconds. So we drop the extra
     // info and get only the usual timestamp.
     $harvest_migrate_date = floor($harvest_migrate_date/1000);
     return $harvest_migrate_date;
    }
  }

  /**
   * Query the migration map table to get the last time the harvest source
   * migration run.
   *
   * @param string @machine_name Harvest Source machine name.
   *
   * @return number of datasets imported by the Harvest Source.
   */
  public static function getMigrationCountFromMachineName($machine_name) {
    // Construct the migrate map table name.
    $migration_machine_name = HarvestSource::getMigrationMachineNameFromName($machine_name);
    $migrate_map_table = 'migrate_map_' . $migration_machine_name;

    // In case the migration was not run and the table was not created yet.
    if (!db_table_exists($migrate_map_table)) {
      return 0;
    }

    // Only count for successful dataset imports.
   $result = db_query("SELECT sourceid1 FROM {" . $migrate_map_table . "} WHERE needs_update = :needs_update;",
     array(
       ':needs_update' => MigrateMap::STATUS_IMPORTED,
     )
   );

   return $result->rowCount();
  }
}