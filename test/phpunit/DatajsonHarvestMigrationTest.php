<?php
/**
 * @file
 */

/**
 *
 */
class DatajsonHarvestMigrationTest extends PHPUnit_Framework_TestCase {

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    $source = self::getOriginalTestSource();

    // Harvest cache the test source.
    dkan_harvest_cache_sources(array($source));
    // Harvest Migration of the test data.
    dkan_harvest_migrate_sources(array($source));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
  }

  /**
   *
   */
  public function testDatasetCount() {
    $dataset_nids = $this->getTestDatasetNid();
    $this->assertEquals(1, count($dataset_nids));

    // Load the node emw.
    $dataset_node = entity_load_single('node', array_pop($dataset_nids));
    return entity_metadata_wrapper('node', $dataset_node);
  }

  /**
   * @depends testDatasetCount
   */
  public function testTitle($dataset) {
    $this->assertEquals('Afghanistan Election Districts TEST', $dataset->title->value());
  }

  /**
   * @depends testDatasetCount
   */
  public function testTags($dataset) {
    $tags_expected = array(
      "country-afghanistan",
      "election",
      "politics",
      "transparency",
    );

    foreach ($dataset->field_tags->value() as $tag) {
      $this->assertContains($tag->name, $tags_expected, $tag->name . ' keyword was not expected!');
      // Remove the processed tag from the expected values array.
      $key = array_search($tag->name, $tags_expected);
      if ($key !== FALSE) {
        unset($tags_expected[$key]);
      }
    }

    // Make sure that all the expected tags were found.
    $this->assertEmpty($tags_expected, 'Some expected keywords were not found.');
  }

  /**
   * @depends testDatasetCount
   */
  public function testIdentifer($dataset) {
    $this->assertEquals("c2150dce-db96-4007-ba3f-fb4f3774902d", $dataset->uuid->value());
  }

  /**
   * @depends testDatasetCount
   */
  public function testResources($dataset) {
    $expected_resources = array(
      'NTN Site MD13 - Data' => 'http://nadp.isws.illinois.edu/data/sites/sitedetails.aspx?id=MD13&net=NTN',
      'NTN Site MD13 - Photos' => 'http://nadp.isws.illinois.edu/data/sites/sitedetails.aspx?id=MD13&net=NTN',
      'NTN Site MD13' => 'http://nadp.isws.illinois.edu/sites/ntn/ntntrends.html?siteID=MD13',
    );

    foreach ($dataset->field_resources->getIterator() as $delta => $resource) {
      $this->assertNotEmpty($expected_resources[$resource->title->value()]);
      $this->assertEquals($expected_resources[$resource->title->value()], $resource->field_link_api->url->value());
    }
  }

  /**
   * @depends testDatasetCount
   */
  public function testMetadataSources($dataset) {
    if (!module_exists('dkan_dataset_metadata_source')) {
      $this->markTestSkipped('dkan_dataset_metadata_source module is not available.');
    } else {
      // This should never be empty as it is set from the cached file during the harvest.
      // Title
      $this->assertEquals($dataset->field_metadata_sources->title->value(),
        'ISO-19115 Metadata for Wye_2015-03-18T20-20-53');

      // Schema name
      $this->assertEquals($dataset->field_metadata_sources->field_metadata_schema->name->value(),
        'ISO 19115-2');

      // File
      // TODO better way to test this?
      $this->assertNotNull($dataset->field_metadata_sources->field_metadata_file->value());
    }
  }

  /**
   * @depends testDatasetCount
   */
  public function testRelatedContent($dataset) {
    $this->assertEmpty($dataset->field_related_content->value());
  }

  /**
   * Simulate a harvest of a source with unchanged content.
   *
   * Harvest the same source with the same content. Make sure that:
   * - the dataset record in the migration map is not updated
   * - The global node count have not changed (No content is leaked).
   */
  public function testHarvestSourceUnchanged() {
    // We want to make sure the dataset record in the migration map did not
    // change. Collect various harvest migration data before running the
    // migration again.
    $migrationOld = dkan_harvest_get_migration(self::getOriginalTestSource());
    $migrationOldMap = $this->getMapTableFromMigration($migrationOld);
    $migrationOldLog = $this->getLogTableFromMigration($migrationOld);
    $globalDatasetCountOld = $this->getGlobalNodeCount();

    /**
     * Tests for the initial log table status.
     */
    // Since the harvest was run only once. We should have exactly one record
    // in the database.
    $this->assertEquals(1, count($migrationOldLog));

    // We are interested in comparing only the Nth and the Nth - 1 record from
    // the log table.
    // - "created" record should have decreased by 1.
    // - "unchanged" record should have increased by 1.
    // - Every other record should be the same.
    $migrationOldLogLast = end($migrationOldLog);
    $this->assertEquals(1, $migrationOldLogLast->created);

    // Nothing else should have changed.
    foreach (array('updated', 'failed', 'orphaned', 'unchanged') as $property) {
      $this->assertEquals(0, $migrationOldLogLast->{$property});
    }

    // Rerun the harvest without changing the source data.
    // Harvest cache the test source.
    dkan_harvest_cache_sources(array(self::getOriginalTestSource()));
    // Harvest Migration of the test data.
    dkan_harvest_migrate_sources(array(self::getOriginalTestSource()));

    $migrationNew = dkan_harvest_get_migration(self::getOriginalTestSource());
    $migrationNewMap = $this->getMapTableFromMigration($migrationNew);
    $migrationNewLog = $this->getLogTableFromMigration($migrationNew);
    $globalDatasetCountNew = $this->getGlobalNodeCount();

    $importedCount = $migrationNew->getMap()->importedCount();
    $this->assertEquals($importedCount, '1');

    $migrationNewMap = $this->getMapTableFromMigration($migrationNew);

    $this->assertEquals($migrationOldMap, $migrationNewMap);
    $this->assertEquals($globalDatasetCountOld, $globalDatasetCountNew);

    /**
     * Map table evolution.
     */
    // The log table should have a new recod by now.
    $this->assertEquals(count($migrationNewLog), count($migrationOldLog) + 1);

    /**
     * Log table evolution.
     */
    // The log table should have exactly one additional record by now.
    $this->assertEquals(count($migrationNewLog),
      count($migrationOldLog) + 1);

    // We are interested in comparing only the Nth and the Nth - 1 record from
    // the log table.
    // - "created" record should have decreased by 1.
    // - "unchanged" record should have increased by 1.
    // - Every other record should be the same.
    $migrationOldLogLast = end($migrationOldLog);
    $migrationNewLogLast = end($migrationNewLog);
    $this->assertEquals($migrationNewLogLast->created + 1,
      $migrationOldLogLast->created);
    $this->assertEquals($migrationNewLogLast->unchanged - 1,
      $migrationOldLogLast->unchanged);

    // Nothing else should have changed.
    foreach (array('updated', 'failed', 'orphaned') as $property) {
      $this->assertEquals($migrationNewLogLast->{$property},
        $migrationOldLogLast->{$property});
    }
  }

  /**
   * Simulate a harvest of a source with updated content.
   *
   * Harvest the same source but with different content. Make sure that:
   * - the dataset record in the harvest source migration map is updated.
   * - the dataset record in the harvest source migration log table is updated.
   * - The dataset update time is greated.
   * - The global node count have not changed (No content is leaked).
   */
  public function testHarvestSourceAlternative() {
    // Get the current values.
    $migrationOld = dkan_harvest_get_migration(self::getOriginalTestSource());
    $migrationOldMap = $this->getMapTableFromMigration($migrationOld);
    $migrationOldLog = $this->getLogTableFromMigration($migrationOld);
    $globalDatasetCountOld = $this->getGlobalNodeCount();

    // We track the last time a record (ie. a dataset) is updated by a
    // timestamp. For less havier harvests like the example used for the test
    // it can take less then one second to run multiple takes with different
    // data and that can mess with the tests. To workaround that we introduce a
    // artificial 1 second delay.
    sleep(1);

    // Rerun the harvest (cache + migration) with the alternative source. the
    // source XML docs. Harvest cache the test source.
    dkan_harvest_cache_sources(array(self::getAlternativeTestSource()));
    dkan_harvest_migrate_sources(array(self::getAlternativeTestSource()));

    $migrationAlternative = dkan_harvest_get_migration(self::getAlternativeTestSource());
    $migrationAlternativeMap = $this->getMapTableFromMigration($migrationAlternative);
    $migrationAlternativeLog = $this->getLogTableFromMigration($migrationAlternative);

    // Get the map table post alternative source harvest.
    $migrationAlternativeMap = $this->getMapTableFromMigration($migrationAlternative);

    // The number of managed datasets record should stay the same.
    $this->assertEquals(count($migrationAlternativeMap), '1');
    // The number of nodes as a hole should not have changed.
    $globalDatasetCountAlternative = $this->getGlobalNodeCount();
    $this->assertEquals($globalDatasetCountOld, $globalDatasetCountAlternative);

    // The harvest source map table should not be same after harvesting a
    // different content.
    $this->assertNotEquals($migrationOldMap, $migrationAlternativeMap);

    // Specifically check that the last_imported in the new alternative dataset
    // record is greater then the previous old dataset record.
    foreach (array_keys($migrationAlternativeMap) as $index) {
      $this->assertGreaterThan($migrationOldMap[$index]->last_imported,
        $migrationAlternativeMap[$index]->last_imported);
    }

    /**
     * Test Log table evolution.
     */
    // The log table should have exactly one additional record by now.
    $this->assertEquals(count($migrationAlternativeLog),
      count($migrationOldLog) + 1);
    // We are interested in comparing only the Nth and the Nth - 1 record from
    // the log table.
    // - "orphaned" record should have increased by 1.
    // - "unchanged" record should have decreased by 1.
    // - Every other record should be the same.
    $migrationOldLogLast = end($migrationOldLog);
    $migrationAlternativeLogLast = end($migrationAlternativeLog);

    $this->assertEquals($migrationAlternativeLogLast->updated,
      $migrationOldLogLast->updated + 1);
    $this->assertEquals($migrationAlternativeLogLast->unchanged,
      $migrationOldLogLast->unchanged - 1);
    // Nothing else should have changed.
    foreach (array('created', 'failed', 'orphaned') as $property) {
      $this->assertEquals($migrationOldLogLast->{$property},
        $migrationAlternativeLogLast->{$property});
    }
  }

  /**
   * Simulate a harvest of a source with updated content.
   *
   * Harvest the same source but with different content. Make sure that:
   * - the dataset record in the harvest source migration map is updated.
   * - the dataset record in the harvest source migration log table is updated.
   * - The dataset update time is greated.
   * - The global node count have not changed (No content is leaked).
   */
  public function testHarvestSourceError() {
    // Get the current values.
    $migrationOld = dkan_harvest_get_migration(self::getOriginalTestSource());
    $migrationOldMap = $this->getMapTableFromMigration($migrationOld);
    $migrationOldLog = $this->getLogTableFromMigration($migrationOld);
    $globalDatasetCountOld = $this->getGlobalNodeCount();

    // We track the last time a record (ie. a dataset) is updated by a
    // timestamp. For less havier harvests like the example used for the test
    // it can take less then one second to run multiple takes with different
    // data and that can mess with the tests. To workaround that we introduce a
    // artificial 1 second delay.
    sleep(1);

    // Rerun the harvest (cache + migration) with the error source. the
    // source XML docs. Harvest cache the test source.
    dkan_harvest_cache_sources(array(self::getErrorTestSource()));
    dkan_harvest_migrate_sources(array(self::getErrorTestSource()));

    $migrationError = dkan_harvest_get_migration(self::getErrorTestSource());
    $migrationErrorMap = $this->getMapTableFromMigration($migrationError);
    $migrationErrorLog = $this->getLogTableFromMigration($migrationError);

    // Get the map table post error source harvest.
    $migrationErrorMap = $this->getMapTableFromMigration($migrationError);

    // The number of managed datasets record should stay the same.
    $this->assertEquals(count($migrationErrorMap), '1');

    // The number of nodes as a hole should not have changed.
    $globalDatasetCountError = $this->getGlobalNodeCount();
    $this->assertEquals($globalDatasetCountOld, $globalDatasetCountError);

    // The harvest source map table should not be same after harvesting a
    // different content.
    $this->assertNotEquals($migrationOldMap, $migrationErrorMap);

    // Specifically check that the last_imported in the new error dataset
    // record is greater then the previous old dataset record.
    foreach (array_keys($migrationErrorMap) as $index) {
      $this->assertGreaterThan($migrationOldMap[$index]->last_imported,
        $migrationErrorMap[$index]->last_imported);
    }

    /**
     * Test Log table evolution.
     */
    // The log table should have exactly one additional record by now.
    $this->assertEquals(count($migrationErrorLog),
      count($migrationOldLog) + 1);
    // We are interested in comparing only the Nth and the Nth - 1 record from
    // the log table.
    // - "updated" record should have decreased by 1.
    // - "failed" record should have increased by 1.
    // - Every other record should be the same.
    $migrationOldLogLast = end($migrationOldLog);
    $migrationErrorLogLast = end($migrationErrorLog);

    $this->assertEquals($migrationErrorLogLast->updated,
      $migrationOldLogLast->updated - 1);
    $this->assertEquals($migrationErrorLogLast->failed,
      $migrationOldLogLast->failed + 1);
    // Nothing else should have changed.
    foreach (array('created', 'unchanged', 'orphaned') as $property) {
      $this->assertEquals($migrationOldLogLast->{$property},
        $migrationErrorLogLast->{$property});
    }
  }

  /**
   * Simulate a harvest of a source with updated content.
   *
   * Harvest the same source but with different content. Make sure that:
   * - the dataset record in the harvest source migration map is updated.
   * - the dataset record in the harvest source migration log table is updated.
   * - The dataset update time is greated.
   * - The global node count have not changed (No content is leaked).
   */
  public function testHarvestSourceEmpty() {
    // Get the current values.
    $migrationOld = dkan_harvest_get_migration(self::getOriginalTestSource());
    $migrationOldMap = $this->getMapTableFromMigration($migrationOld);
    $migrationOldLog = $this->getLogTableFromMigration($migrationOld);
    $globalDatasetCountOld = $this->getGlobalNodeCount();

    // We track the last time a record (ie. a dataset) is updated by a
    // timestamp. For less havier harvests like the example used for the test
    // it can take less then one second to run multiple takes with different
    // data and that can mess with the tests. To workaround that we introduce a
    // artificial 1 second delay.
    sleep(1);

    // Rerun the harvest (cache + migration) with the empty source. the
    // source XML docs. Harvest cache the test source.
    dkan_harvest_cache_sources(array(self::getEmptyTestSource()));
    dkan_harvest_migrate_sources(array(self::getEmptyTestSource()));

    $migrationEmpty = dkan_harvest_get_migration(self::getEmptyTestSource());
    $migrationEmptyMap = $this->getMapTableFromMigration($migrationEmpty);
    $migrationEmptyLog = $this->getLogTableFromMigration($migrationEmpty);

    // Get the map table post empty source harvest.
    $migrationEmptyMap = $this->getMapTableFromMigration($migrationEmpty);

    // The number of managed datasets record should stay the same.
    $this->assertEquals(count($migrationEmptyMap), count($migrationOldMap));
    // The number of nodes as a hole should not have changed.
    $globalDatasetCountEmpty = $this->getGlobalNodeCount();
    $this->assertEquals($globalDatasetCountOld, $globalDatasetCountEmpty);

    // The harvest source map table should not be same after harvesting a
    // different content.
    $this->assertNotEquals($migrationOldMap, $migrationEmptyMap);

    // For empty source no update happened so the last_imported column should
    // match.
    foreach (array_keys($migrationEmptyMap) as $index) {
      $this->assertEquals($migrationOldMap[$index]->last_imported,
        $migrationEmptyMap[$index]->last_imported);
    }

    /**
     * Test Log table evolution.
     */
    // The log table should have exactly one additional record by now.
    $this->assertEquals(count($migrationEmptyLog),
      count($migrationOldLog) + 1);
    // We are interested in comparing only the Nth and the Nth - 1 record from
    // the log table.
    // - "orphaned" record should have increased by 1.
    // - "failed" record should be decreased by 1.
    // - Every other record should be the same.
    $migrationOldLogLast = end($migrationOldLog);
    $migrationEmptyLogLast = end($migrationEmptyLog);

    $this->assertEquals($migrationEmptyLogLast->orphaned - 1,
      $migrationOldLogLast->orphaned);
    $this->assertEquals($migrationEmptyLogLast->failed,
      $migrationOldLogLast->failed - 1);
    // Nothing else should have changed.
    foreach (array('created', 'updated', 'unchanged') as $property) {
      $this->assertEquals($migrationOldLogLast->{$property},
        $migrationEmptyLogLast->{$property});
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
  }

  /**
   * {@inheritdoc}
   */
  public static function tearDownAfterClass() {
    // Clean all harvest migrations data from the test site. Since the Original
    // and Alternative test source are the same harvest source but with
    // different data we only need to clean one of them.
    $source = self::getOriginalTestSource();
    $source->getCacheDir(TRUE);
    dkan_harvest_rollback_sources(array($source));
    dkan_harvest_deregister_sources(array($source));
  }

  /**
   * Test Harvest Source.
   */
  public static function getOriginalTestSource() {
    return new HarvestSource(
      'dkan_harvest_datajson_test',
      array(
        'uri' => DRUPAL_ROOT . "/" . drupal_get_path('module', 'dkan_harvest') .
        "/test/data/dkan_harvest_datajson_test_original.json",
        'type' => 'datajson_v1_1_json',
        'label' => 'Dkan Harvest datajson Test Source',
      )
    );
  }

  /**
   *
   */
  public static function getAlternativeTestSource() {
    return new HarvestSource(
      'dkan_harvest_datajson_test',
      array(
        'uri' => DRUPAL_ROOT . "/" . drupal_get_path('module', 'dkan_harvest') .
        "/test/data/dkan_harvest_datajson_test_alternative.json",
        'type' => 'datajson_v1_1_json',
        'label' => 'Dkan Harvest datajson Test Source',
      )
    );
  }

  /**
   * Test Harvest Source.
   */
  public static function getErrorTestSource() {
    return new HarvestSource(
      'dkan_harvest_datajson_test',
      array(
        'uri' => DRUPAL_ROOT . "/" . drupal_get_path('module', 'dkan_harvest') .
        "/test/data/dkan_harvest_datajson_test_error.json",
        'type' => 'datajson_v1_1_json',
        'label' => 'Dkan Harvest datajson Test Source',
      )
    );
  }

  /**
   * Test Harvest Source.
   */
  public static function getEmptyTestSource() {
    return new HarvestSource(
      'dkan_harvest_datajson_test',
      array(
        'uri' => DRUPAL_ROOT . "/" . drupal_get_path('module', 'dkan_harvest') .
        "/test/data/dkan_harvest_datajson_test_empty.json",
        'type' => 'datajson_v1_1_json',
        'label' => 'Dkan Harvest datajson Test Source',
      )
    );
  }

  /**
   *
   */
  private function getTestDatasetNid() {
    $migration = dkan_harvest_get_migration(self::getOriginalTestSource());

    if ($migration) {
      $query = $migration->getMap()->getConnection()->select($migration->getMap()->getMapTable(), 'map')
        ->fields('map')
        ->condition("needs_update", MigrateMap::STATUS_IMPORTED, '=');
      $result = $query->execute();

      $return = array_keys($result->fetchAllAssoc('destid1'));
      return $return;
    } else {
      return array();
    }
  }

  /**
   * Helper method to get a harvest migration map table from the harvest
   * migration.
   *
   * @param HarvestMigration $migration
   *
   * @return Array of records of the harvest source migration map table keyed
   * by destid1.
   */
  private function getMapTableFromMigration(HarvestMigration $migration) {
    $map = $migration->getMap();
    $result = $map->getConnection()->select($map->getMapTable(), 'map')
      ->fields('map')
      ->execute();

    return $result->fetchAllAssoc('destid1');
  }

  /**
   * Helper method to get a harvest migration log table from the harvest
   * migration.
   *
   * @param HarvestMigration $migration
   *
   * @return Array of records of the harvest source migration log table keyed
   * by destid1.
   */
  private function getLogTableFromMigration(HarvestMigration $migration) {
    $map = $migration->getMap();
    $result = $map->getConnection()->select($map->getLogTable(), 'log')
      ->fields('log')
      ->execute();

    return $result->fetchAllAssoc('mlid');
  }

  /**
   *
   */
  private function getGlobalNodeCount() {
    $query = "SELECT COUNT(*) amount FROM {node} n";
    $result = db_query($query)->fetch();
    return $result->amount;
  }
}