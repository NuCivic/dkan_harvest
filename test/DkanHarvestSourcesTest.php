<?php

/**
 * @file
 * Base phpunit tests for dkan_harvest module.
 */

class DkanHarvestSourcesTest extends \PHPUnit_Framework_TestCase {

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
   * Make sure that the allowed harvest type values the the type machine name
   * as key and the type label as value.
   *
   * @covers ::dkan_harvest_field_dkan_harveset_type_allowed_values()
   */
  public function testDkanHarvestSourcesFieldDkanHarvesetTypeAllowedValues() {
    $allowed_values_expected = array(
      'harvest_test_type' => 'Dkan Harvest Test Type',
      'harvest_another_test_type' => 'Dkan Harvest Another Test Type',
    );

    $allowed_values = dkan_harvest_field_dkan_harveset_type_allowed_values();

    $this->assertNotNull($allowed_values['harvest_test_type']);
    $this->assertEquals($allowed_values['harvest_test_type'], $allowed_values_expected['harvest_test_type']);

    $this->assertNotNull($allowed_values['harvest_another_test_type']);
    $this->assertEquals($allowed_values['harvest_another_test_type'], $allowed_values_expected['harvest_another_test_type']);
  }

  /**
   * @covers ::dkan_harvest_field_attach_validate_source_uri()
   */
  public function testDkanHarvestSourcesFieldAttachValidateSourceUri() {
    // Invalid arguments.
    $errors = array();
    dkan_harvest_field_attach_validate_source_uri($uri, $langcode, $delta, $errors);
    $this->assertNotEmpty($errors);

    $langcode = LANGUAGE_NONE;
    $delta = 0;

    // Invalid Protocol
    $errors = array();
    $uri = 'wrong://data.mo.gov/data.json';
    dkan_harvest_field_attach_validate_source_uri($uri, $langcode, $delta, $errors);
    $this->assertNotEmpty($errors);

    // Invalid Local URI
    $errors = array();
    $uri = 'file://test/data/harvest_test_source_local_file/data.json';
    dkan_harvest_field_attach_validate_source_uri($uri, $langcode, $delta, $errors);
    $this->assertNotEmpty($errors);

    // Valid local URI
    $errors = array();
    $uri = 'file://' .
      getcwd() . '/' . drupal_get_path('module', 'dkan_harvest') .
      '/test/data/harvest_test_source_local_file/data.json';
    dkan_harvest_field_attach_validate_source_uri($uri, $langcode, $delta, $errors);
    $this->assertEmpty($errors);

    // Invalid Remote URI
    $errors = array();
    $uri = 'http://this_is_not_correct.wrong/data.json';
    dkan_harvest_field_attach_validate_source_uri($uri, $langcode, $delta, $errors);
    $this->assertNotEmpty($errors);

    // Valid Remote URI
    $errors = array();
    $uri = 'https://data.mo.gov/data.json';
    dkan_harvest_field_attach_validate_source_uri($uri, $langcode, $delta, $errors);
    $this->assertEmpty($errors);

    $errors = array();
    $uri = 'http://data.mo.gov/data.json';
    dkan_harvest_field_attach_validate_source_uri($uri, $langcode, $delta, $errors);
    $this->assertEmpty($errors);
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
}
