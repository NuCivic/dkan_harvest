<?php

/**
 * @file
 * Base phpunit tests for HarvestSource class.
 */

class HarvestSourceTest extends \PHPUnit_Framework_TestCase {

  // dkan_harvest_test status.
  public static $dkanHarvestTestBeforClassStatus = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    // Make sure the test module exporting the test source type.
    if (!module_exists('dkan_harvest_test')) {
      self::$dkanHarvestTestBeforClassStatus = FALSE;
      module_enable(array('dkan_harvest_test'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
  }

  /**
   * @expectedException Exception
   * @expectedExceptionMessage HarvestSource machine_name invalid!
   */
  public function testHarvestSourceConstructMachineNameNULLException() {
    $source = new HarvestSource(NULL, array());
  }

  /**
   * @expectedException Exception
   * @expectedExceptionMessage HarvestSource machine_name invalid!
   */
  public function testHarvestSourceConstructMachineNameEmptyException() {
    $source = new HarvestSource('', array());
  }

  /**
   * @expectedException Exception
   * @expectedExceptionMessage HarvestSource uri invalid!
   */
  public function testHarvestSourceConstructURIException() {
    $source = new HarvestSource('harvest_source_test'
      , array(
        'type' => 'harvest_test_type',
      ));
  }

  /**
   * @expectedException Exception
   * @expectedExceptionMessage HarvestSource type invalid!
   */
  public function testHarvestSourceConstructTypeException() {
    $source = new HarvestSource('harvest_source_test'
      , array(
        'uri' => DRUPAL_ROOT . "/" . drupal_get_path('module', 'dkan_harvest') .
        "/test/data/harvest_test_source_local_dir/",
      ));
  }

  /**
   * Test a successful HarvestSource instantiation.
   */
  public function testHarvestSourceConstruct() {
    $source = new HarvestSource(
      'harvest_test_source_remote', array (
        'uri' => 'https://data.mo.gov/data.json',
        'type' => 'harvest_test_type',
        'label' => 'Dkan Harvest Test Source',
      ));
    $this->assertNotNull($source);
  }

  /**
   * covers HarvestSource::isRemote
   */
  public function  testIsRemote() {
    $source_remote = $this->getRemoteSource();
    $this->assertTrue($source_remote->isRemote());

    $source_local = $this->getLocalSource();
    $this->assertFalse($source_local->isRemote());
  }

  /**
   * covers HarvestSource::getCacheDir
   */
  public function testGetCacheDir() {
    $source_remote = $this->getRemoteSource();
    $source_remote_cachedir_path = DKAN_HARVEST_CACHE_DIR .
      '/' .
      $source_remote->machine_name;
    $rmdir = drupal_rmdir($source_remote_cachedir_path);
    $this->assertTrue($rmdir);

    $cacheDir = $source_remote->getCacheDir();
    $this->assertFALSE($cacheDir);

    $cacheDir = $source_remote->getCacheDir(TRUE);
    $this->assertEquals($cacheDir, $source_remote_cachedir_path);
  }

  /**
   *
   */
  public function testGetHarvestSourceFromNode() {
    // Stop here and mark this test as incomplete.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
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
    // Assuming the test module enabled by now. Restore original status of the
    // modules.
    if (!self::$dkanHarvestTestBeforClassStatus) {
      module_disable(array('dkan_harvest_test'));
    }
  }

  /**
   * Return Test HarvestSource object.
   */
  private function getRemoteSource() {
    return new HarvestSource(
      'harvest_test_source_remote', array (
        'uri' => 'https://data.mo.gov/data.json',
        'type' => 'harvest_test_type',
        'label' => 'Dkan Harvest Test Source',
      ));
  }

  /**
   * Return Test HarvestSource object.
   */
  private function getLocalSource() {
    return new HarvestSource(
      'harvest_test_source_local_file', array (
        'uri' => DRUPAL_ROOT . "/" . drupal_get_path('module', 'dkan_harvest') .
        "/test/data/harvest_test_source_local_file/data.json",
        'type' => 'harvest_test_type',
        'label' => 'Dkan Harvest Test Source',

      )
    );
  }
}
