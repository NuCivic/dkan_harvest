<?php
/**
 * @file
 */

/**
 *
 */
class PODJSONHarvestMigrationTest extends PHPUnit_Framework_TestCase {

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
      'dkan_harvest_pod_test_single',
      array (
        'uri' => DRUPAL_ROOT . "/" . drupal_get_path('module', 'dkan_harvest') .
        "/test/data/dkan_harvest_pod_test_single.json",
        'type' => 'pod_v1_1_json',
        'label' => 'Dkan Harvest POD Test Source',
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
