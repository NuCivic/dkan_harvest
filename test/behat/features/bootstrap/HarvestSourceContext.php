<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Drupal\DKANExtension\Context\RawDKANEntityContext;

/**
 * Defines application features from the specific context.
 */
class HarvestSourceContext extends RawDKANEntityContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
    parent::__construct(
      'node',
      'harvest_source'
    );
  }

  /**
   * Creates harvest sources from a table.
   *
   * @Given sources:
   */
  public function addHarvestSources(TableNode $harvestSourcesTable) {
    parent::addMultipleFromTable($harvestSourcesTable);
  }
  
  /**
  * @AfterScenario @harvest_rollback
  */
  public function harvestRollback(AfterScenarioScope $event)
  {
    $migrations = migrate_migrations();
    $harvest_migrations = array();
    foreach ($migrations as $name => $migration) {
      if(strpos($name , 'dkan_harvest') === 0) {
        $migration = Migration::getInstance($name);
        $migration->processRollback();
      }
    }
  }
}
