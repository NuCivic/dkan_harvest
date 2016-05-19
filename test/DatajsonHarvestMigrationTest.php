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
    $source = self::getTestSource();

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

    // Load the node object.
    return entity_load_single('node', array_pop($dataset_nids));
  }

  /**
   * @depends testDatasetCount
   */
  public function testTitle($dataset) {
    $this->assertEquals('Afghanistan Election Districts TEST', $dataset->title);
  }

  /**
   * @depends testDatasetCount
   */
  public function testTags($dataset) {
    $dataset = entity_metadata_wrapper('node', $dataset);
    $tags_expected = array(
      "country-afghanistan",
      "election",
      "politics",
      "transparency",
    );

    foreach($dataset->field_tags->value() as $tag) {
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
    $this->assertEquals('Wye_2015-03-18T20-20-53', $dataset->uuid->value());
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
    if (module_exists('dkan_dataset_metadata_source')) {
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
    // change.
    $migrationOld = dkan_harvest_get_migration(self::getOriginalTestSource());
    $datasetMapOld = $this->getDatasetFromMap($migrationOld->getMap());
    $globalDatasetCountOld = $this->getGlobalNodeCount();

    // Rerun the harvest without changing the source XML docs.
    // Harvest cache the test source.
    dkan_harvest_cache_sources(array(self::getOriginalTestSource()));
    // Harvest Migration of the test data.
    dkan_harvest_migrate_sources(array(self::getOriginalTestSource()));

    $migrationNew = dkan_harvest_get_migration(self::getOriginalTestSource());
    $datasetMapNew = $this->getDatasetFromMap($migrationNew->getMap());
    $globalDatasetCountNew = $this->getGlobalNodeCount();

    $importedCount = $migrationNew->getMap()->importedCount();
    $this->assertEquals($importedCount, '1');

    $datasetMapNew = $this->getDatasetFromMap($migrationNew->getMap());

    $this->assertEquals($datasetMapOld, $datasetMapNew);
    $this->assertEquals($globalDatasetCountOld, $globalDatasetCountNew);
  }

  /**
   * Simulate a harvest of a source with updated content.
   *
   * Harvest the same source but with different content. Make sure that:
   * - the dataset record in the migration map is updated.
   * - The dataset update time is greated.
   * - The global node count have not changed (No content is leaked).
   */
  public function testHarvestSourceChanged() {
    // Get the current values.
    $migrationOld = dkan_harvest_get_migration(self::getOriginalTestSource());
    $datasetMapOld = $this->getDatasetFromMap($migrationOld->getMap());
    $globalDatasetCountOld = $this->getGlobalNodeCount();

    // Rerun the harvest (cache + migration) with the alternative source. the
    // source XML docs. Harvest cache the test source.
    dkan_harvest_cache_sources(array(self::getAlternativeTestSource()));
    dkan_harvest_migrate_sources(array(self::getAlternativeTestSource()));

    $migrationAlternative = dkan_harvest_get_migration(self::getAlternativeTestSource());
    $datasetMapAlternative = $this->getDatasetFromMap($migrationAlternative->getMap());

    // The count check si to make sure that it is the saem record that we are
    // checking.
    $importedCount = $migrationAlternative->getMap()->importedCount();
    $this->assertEquals($importedCount, '1');

    // Get the values from the new alternative source.
    $datasetMapAlternative = $this->getDatasetFromMap($migrationAlternative->getMap());
    $globalDatasetCountAlternative = $this->getGlobalNodeCount();

    // Compare the new values to the old ones.
    $this->assertNotEquals($datasetMapOld, $datasetMapAlternative);
    // Specifically check that the last_imported in the new alternative dataset
    // record is greater then the previous old dataset record.
    $this->assertGreaterThan($datasetMapOld->last_imported,
      $datasetMapAlternative->last_imported);
    $this->assertEquals($globalDatasetCountOld, $globalDatasetCountAlternative);
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
    // Clean all harvest migration traces.
    $source = self::getTestSource();
    $source->getCacheDir(TRUE);
    dkan_harvest_rollback_sources(array($source));
    dkan_harvest_deregister_sources(array($source));
  }

  /**
   * Test Harvest Source.
   */
  public static function getTestSource() {
    return new HarvestSource(
      'dkan_harvest_datajson_test_single',
      array (
        'uri' => DRUPAL_ROOT . "/" . drupal_get_path('module', 'dkan_harvest') .
        "/test/data/dkan_harvest_datajson_test_single.json",
        'type' => 'datajson_v1_1_json',
        'label' => 'Dkan Harvest datajson Test Source',
      )
    );
  }

  /**
   *
   */
  private function getTestDatasetNid() {
    $migration = dkan_harvest_get_migration(self::getTestSource());

    if ($migration) {
      $query = $migration->getMap()->getConnection()->select($migration->getMap()->getMapTable(), 'map')
        ->fields('map')
        ->condition("needs_update", MigrateMap::STATUS_IMPORTED, '=');
      $result = $query->execute();

      return array_keys($result->fetchAllAssoc('destid1'));
    }
    else {
      return array();
    }
  }
}
