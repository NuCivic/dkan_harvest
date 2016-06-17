<?php

/**
 * @file
 * Migration File for DKAN Harvest Data.Json
 */

/**
 *
 */
class DatajsonHarvestMigration extends HarvestMigration {

  public $itemUrl;

  /**
   * Constructor.
   */
  public function __construct($arguments) {
    parent::__construct($arguments);
    $this->version = isset($arguments['version']) ? $arguments['version'] : '1.1';

    $this->itemUrl = drupal_realpath($this->dkanHarvestSource->getCacheDir()) .
      '/:id';

    $fields = $this->getDataJsonDatasetFields();

    $this->source = new MigrateSourceList(
      new HarvestList($this->dkanHarvestSource->getCacheDir()),
      new MigrateItemJSON($this->itemUrl),
      //$fields,
      array(),
      $this->sourceListOptions
    );
  }

/**
 *
 */
  public function setFieldMappings($version = '1.1') {
    // Set the default user as root. This will help avoid some of the issues
    // with anonymous nodes.
    $this->addFieldMapping('uid', 'uid')
      ->defaultValue(1);
    // Set the default dataset status as published.
    $this->addFieldMapping('status', 'status')
      ->defaultValue(1);

    // These are common to both 1.0 and 1.1
    $this->addFieldMapping('title', 'title');
    $this->addFieldMapping('body', 'description');
    $this->addFieldMapping('og_group_ref', 'group_id');

    $this->addFieldMapping('field_tags', 'keyword');
    $this->addFieldMapping('field_tags:create_term')
      ->defaultValue(TRUE);

    $this->addFieldMapping('changed', 'modified');
    $this->addFieldMapping('created', 'issued');
    $this->addFieldMapping('field_public_access_level', 'accessLevel');
    $this->addFieldMapping('field_resources', 'resources');
    $this->addFieldMapping('field_contact_name', 'contactPointName');
    $this->addFieldMapping('field_contact_email', 'mbox');
    $this->addFieldMapping('uuid', 'identifier');
    $this->addFieldMapping('field_license', 'license');
    $this->addFieldMapping('field_spatial_geographical_cover', 'spatial');
    $this->addFieldMapping('field_temporal_coverage', 'temporalBegin');
    $this->addFieldMapping('field_temporal_coverage:to', 'temporalEnd');
    $this->addFieldMapping('field_frequency', 'accrualPeriodicity');
    $this->addFieldMapping('field_data_dictionary', 'describedBy');
    if ($version == '1.0') {
      $this->addFieldMapping('field_data_dictionary', 'dataDictionary');
      $this->addFieldMapping('language', 'language');
    }
    else {
      // POD 1.1.
      $this->addFieldMapping('field_additional_info',  'field_additional_info_key');
      $this->addFieldMapping('field_additional_info:second', 'field_additional_info_value');
      $this->addFieldMapping('field_related_content', 'references');
    }
    if (module_exists('open_data_federal_extras')) {
      $this->addFieldMapping('field_odfe_bureau_code', 'bureauCode');
      $this->addFieldMapping('field_odfe_program_code', 'programCode');
      $this->addFieldMapping('field_odfe_landing_page', 'landingPage');
      $this->addFieldMapping('field_odfe_rights', 'rights');
      $this->addFieldMapping('field_odfe_conforms_to', 'conformsTo');
      $this->addFieldMapping('field_odfe_data_quality', 'dataQuality');
      $this->addFieldMapping('field_odfe_data_dictionary_type', 'describedByType');
      $this->addFieldMapping('field_odfe_is_part_of', 'isPartOf');
      $this->addFieldMapping('field_odfe_language', 'language');
      $this->addFieldMapping('field_odfe_investment_uii', 'primaryITInvestmentUII');
      $this->addFieldMapping('field_odfe_system_of_records', 'systemOfRecords');

      $this->addFieldMapping('field_odfe_category', 'theme');
      $this->addFieldMapping('field_odfe_category:create_term')
        ->defaultValue(TRUE);
    }
    // dkan_dataset_metadata_source.
    if (module_exists('dkan_dataset_metadata_source')) {
      $this->addFieldMapping('field_metadata_sources', 'metadata_sources');
    }
  }

  /**
   * Creates list of fields for Data.json Dataset.
   */
  public function getDataJsonDatasetFields($version = '1.1') {
    $common_fields = array(
        // “Common Core” Required Fields.
        "title" => "Title",
        "description" => "Description",
        "keyword" => "Tags",
        "modified" => "Last Update",
        "publisher" => "Publisher",
        "contactPoint" => "Contact Name",
        "identifier" => "Unique Identifier",
        "accessLevel" => "Public",
        // “Common Core” Required-if-Applicable Fields.
        "bureauCode" => " Bureau Code",
        "programCode" => "Program Code",
        "license" => "License",
        "spatial" => "Spatial",
        "temporal" => " Temporal",
        // Beyond Common Core.
        "dataQuality" => " Data Quality",
        "distribution" => "Distribution",
        "issued" => "Release Date",
        "language" => " Language",
        "references" => " Related Documents ",
        "systemOfRecords" => " System of Records",
    );
    if ($version == '1.0') {
      return $common_fields + array(
        "mbox" => "Contact Email",
        "accessLevelComment" => " Access Level Comment",
        "accessURL" => "Download",
        "webService" => " Endpoint ",
        "format" => " Format",
        // Beyond Common Core.
        "theme" => "Category",
        "dataDictionary" => "Data Dictionary",
        "accrualPeriodicity" => "Frequency",
        "landingPage" => "Homepage",
        "PrimaryITInvestmentUII" => "Primary IT Investment",
      );
    }
    elseif ($version == '1.1') {
      return $common_fields + array(
        "rights" => " Rights",
        "accrualPeriodicity" => " Frequency",
        "conformsTo" => " Data Standard URI",
        "describedBy" => "Data Dictionary",
        "describedByType" => "Data",
        "isPartOf" => " Collection",
        "landingPage" => "Homepage",
        "primaryITInvestmentUII" => "Primary",
        "theme" => "Theme",
      );
    }
  }

  /**
   * Implements prepareRow.
   */
  public function prepareRow(&$row) {
    parent::prepareRow($row);
  }

  /**
   *
   */
  public function prepare(&$dataset_prepare, stdClass &$row) {
    parent::prepare($dataset_prepare, $row);
  }
}
