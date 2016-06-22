<?php

/**
 * Base MigrateItem class for Harvest Migrations.
 *
 * Should be a simpler files retriving impletation for locally stored files.
 */
class HarvestMigrateSQLMap extends MigrateSQLMap {

  /**
   * Codes reflecting the orphaned status of a map row.
   */
  const STATUS_IGNORED_NO_SOURCE = 20;

  /**
   * Names of tables created for tracking the migration.
   *
   * @var string
   */
  protected $logTable;

  /**
   * Get the log table name.
   */
  public function getLogTable() {
    return $this->logTable;
  }

  /**
   * Qualifying the log table name with the database name makes cross-db joins
   * possible. Note that, because prefixes are applied after we do this (i.e.,
   * it will prefix the string we return), we do not qualify the table if it has
   * a prefix. This will work fine when the source data is in the default
   * (prefixed) database (in particular, for simpletest), but not if the primary
   * query is in an external database.
   *
   * @return string
   *
   * @see self::getQualifiedMapTable()
   */
  public function getQualifiedLogTable() {
    $options = $this->connection->getConnectionOptions();
    $prefix = $this->connection->tablePrefix($this->logTable);
    if ($prefix) {
      return $this->logTable;
    }
    else {
      return $options['database'] . '.' . $this->logTable;
    }
  }

  /**
   * We don't need to check the log table more than once per request.
   *
   * @var boolean
   */
  protected $ensuredTableLog;

  /**
   *
   */
  public function __construct($machine_name, array $source_key,
    array $destination_key, $connection_key = 'default', $options = array()) {
    parent::__construct($machine_name, $source_key, $destination_key, $connection_key, $options);

    // Generate log table name. Limited to 63 characters.
    $prefixLength = strlen($this->connection->tablePrefix());
    $this->logTable = 'migrate_log_' . drupal_strtolower($machine_name);
    $this->logTable = drupal_substr($this->logTable, 0, 63 - $prefixLength);

    $this->ensureTableLog();
  }

 /**
  * Create the log table if they don't already exist.
  *
  * {@see self::ensureTables()}
  */
  protected function ensureTableLog() {
    if (!$this->ensuredTableLog) {
      if (!$this->connection->schema()->tableExists($this->logTable)) {
        // Generate appropriate schema info for the log table, and
        // map from the migrationid  field name to the log field name.
        $fields = array();

        $fields['mlid'] = array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'Primary key for migrate_log table',
        );
        $fields['created'] = array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Number of created items',
        );
        $fields['updated'] = array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Number of updated items',
        );
        $fields['unchanged'] = array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Number of unchanged items',
        );
        $fields['failed'] = array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Number of items that failed to import',
        );
        $fields['orphaned'] = array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Number of previously imported items that are not provided by the source anymore',
        );

        $schema = array(
          'description' => t('Mappings from source key to destination key'),
          'fields' => $fields,
          // For documentation purposes only; foreign keys are not created in the
          // database.
          'foreign keys' => array(
            'migrate_log' => array(
              'table' => 'migrate_log',
              'columns' => array('mlid' => 'mlid'),
            ),
          ),
          'primary key' => array('mlid'),
        );

        $this->connection->schema()->createTable($this->logTable, $schema);
      }
    }

    $this->ensuredTableLog = TRUE;
  }

  /**
  * {@inheritdoc}
  *
  * Remove the associated map and message tables.
  */
  public function destroy() {
    parent::destroy();
    $this->connection->schema()->dropTable($this->logTable);
  }

 /**
  * Get the number of source records which were previously imported but not
  * available from the source anymore.
  *
  * @return int
  *  Number of records errored out.
  */
  public function orphanedCount() {
    $query = $this->connection->select($this->mapTable);
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('needs_update', self::STATUS_IGNORED_NO_SOURCE);
    $count = $query->execute()->fetchField();
    return $count;
  }

  /**
   * More generic method to query the map table.
   *
   * @parm $needs_update_value
   * @param $sourceid1_values
   * @param $sourceid1_condition
   * @param $destid1_values
   * @param $destid1_condition
   *
   * @result Array with the result keyed by 'sourceid1'
   */
  public function lookupMapTable($needs_update_value = HarvestMigrateSQLMap::STATUS_IMPORTED, $sourceid1_values = array(), $sourceid1_condition = "IN", $destid1_values = array(), $destid1_condition = "IN") {
    migrate_instrument_stop('lookupMapTable');
    $query = $this->connection->select($this->mapTable, 'map');
    $query->fields('map');

    if ($needs_update_value !== FALSE) {
      $query->condition("needs_update", $needs_update_value) ;
    }

    if (is_array($sourceid1_values) && !empty($sourceid1_values) && in_array($sourceid1_condition, array("IN", "NOT IN"))) {
      $query->condition('sourceid1', $sourceid1_values, $sourceid1_condition);
    }

    if (is_array($destid1_values) && !empty($sourceid1_values) && in_array($sourceid1_condition, array("IN", "NOT IN"))) {
      $query->condition('destid1', $destid1_values, $destid1_condition);
    }

    $result = $query->execute();
    $return = $result->fetchAllAssoc('sourceid1');
    migrate_instrument_stop('lookupMapTable');
    return $return;
  }
}
