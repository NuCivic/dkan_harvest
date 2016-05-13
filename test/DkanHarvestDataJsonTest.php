<?php
/**
 * @file
 */

/**
 *
 */
class DkanHarvestDataJsonTest extends PHPUnit_Framework_TestCase {

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    // Harvest cache the test source.
    dkan_harvest_cache_sources(array(self::getTestSource()));

    // Harvest Migration of the test data.
    dkan_harvest_migrate_sources(array(self::getTestSource()));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
  }

  /**
   * @covers dkan_harvest_prepare_item_id().
   */
  public function testDKANHarvestPrepareItemId()
  {
    $url = 'http://example.com/what';
    $dir = dkan_harvest_prepare_item_id($url);
    $this->assertEquals($dir, 'what');

    $url = 'http://example.com/what/now';
    $dir = dkan_harvest_prepare_item_id($url);
    $this->assertEquals($dir, 'now');

    $url = 'http://example.com';
    $dir = dkan_harvest_prepare_item_id($url);
    $this->assertEquals($dir, '');
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
    dkan_harvest_rollback_sources(array(self::getTestSource()));
    dkan_harvest_deregister_sources(array(self::getTestSource()));
  }

  /**
   * Test Harvest Source.
   */
  public static function getTestSource() {
    return new HarvestSource(
      'dkan_harvest_pod_test',
      array (
        'uri' => DRUPAL_ROOT . "/" . drupal_get_path('module', 'dkan_harvest') .
        "/test/data/testData.json",
        'type' => 'pod_v1_1_json',
        'label' => 'Dkan Harvest POD Test Source',
      )
    );
  }
}
