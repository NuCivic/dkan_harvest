<?php

/**
 * @file
 * Base phpunit tests for dkan_harvest module.
 */

class DkanHarvestTest extends \PHPUnit_Framework_TestCase {

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    // Make sure the test module exporting the test source type.
    if (!module_exists('dkan_harvest_test')) {
      self::setDkanHarvestTestBeforClassStatus(FALSE);
      module_enable(array('dkan_harvest_test'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
  }

  /**
   *
   */
  public function testPrepareResourceHelper() {
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
    if (!self::getDkanHarvestTestBeforClassStatus()) {
      module_disable(array('dkan_harvest_test'));
    }
  }
}
