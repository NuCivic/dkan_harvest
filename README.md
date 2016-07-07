DKAN Harvest is a module that provides a common harvesting framework for Dkan
extensions and adds a CLI and a WUI to DKAn to manage harvesting sources and
jobs.

### How does it works?

DKAN Harvest is built on top of the legendary
[migrate](https://www.drupal.org/project/migrate) module that consume a source
api to download the data locally to the local drupal `public://` and process it
to create the associated `dataset` and `resource` DKAN nodes.

## Usage
### Add a Harvest Source

Harvest Sources are available as content of type `harvest-source`. The easiest
way, as an admin, to add a new harvest source node is to go to
`node/add/harvest-source` and fill the form.

![Add Harvest Source](node_add_harvest_source.png)

If the Harvest Source type you are looking for is not available, please refer
to the **Define a new Harvest Source Type** section in the developers docs.

### Run a Harvest from the Dashboard

To run and manage harvest operations from the Web interface one can use the
Dashboard available via `admin/dkan/harvest/dashboard`. This is a view of all
available (added and published) harvest sources in the system. Apart from the
title and the type additonal columns displaying the last time a harvest
migration was run for a specific harvest source and the number of datsets
imported are available.

![Harvest Dashboard](harvest_dashboard.png)

To run a full harvest operation or a harvest cache operation or a harvest
migration operation, select all or a number of harvest sources listed in the
dashboard and select a task from the **Operations** dropdown.

![Harvest Dashboard Operations](harvest_dashboard_operations.png)

### Harvest Source Page
#### Main page

Harvest Source nodes have a public page that the public can use to view,
filter, and search datasets harvested from a local or remote source. The nodes
are accesable via the `harvest_source/<title>`url.

![Harvest Source Page](harvest_source_page.png)

Adding support for the harvest sources in the default DKAN search page is in the TODO list.

#### Event log

The event log page is accecable from the **Event Log** tab available on the
Harvest Source page and provides a way to review the evolution of the harvest
source during time.

![Harvest Source Event Log Page](harvest_source_event_log.png)

The information is managed by the core `dkan_harvest` via a per-harvest source
`migrate_log` table that tracks the number of datasets created, updated,
failed, orphaned, and unchanged.

Presenting the event log via some easy to parse charts is in the TODO list.
### Drush

DKAN Harvest provides multiple drush commands to manage harvest sources and
control harvest jobs. It is recommanded to pass the `--user=1` drush option to
harvest operation (especially harvest migration jobs) to make sure that the
entities created have a proper user as author.

### List Harvest sources available

```sh
# List all available Harvest Sources
$ drush --user=1 dkan-harvest-status
# Alias
$ drush --user=1 dkan-hs
```

### Run a full harvest (Cache & Migration)

```sh
# Harvest data and run migration on all the harvest sources available.
$ drush --user=1 dkan-harvest
# Alias
$ drush --user=1 dkan-h

# Harvest specific  harvest source.
$ drush --user=1 dkan-harvest test_harvest_source
# Alias
$ drush --user=1 dkan-h test_harvest_source
```

### Run a harvest cache

```sh
# Run a harvest cache operation on all the harvest sources available.
$ drush --user=1 dkan-harvest-cache
# Alias
$ drush --user=1 dkan-hc

# Harvest cache specific harvest source.
$ drush --user=1 dkan-harvest-cache test_harvest_source
# Alias
$ drush --user=1 dkan-hc test_harvest_source
```

### Run a harvest migration job

```sh
# Run a harvest migrate operation on all the harvest sources available.
$ drush --user=1 dkan-harvest-migrate
# Alias
$ drush --user=1 dkan-hm

# Harvest migrate specific harvest source.
$ drush --user=1 dkan-harvest-migrate test_harvest_source
# Alias
$ drush --user=1 dkan-hm test_harvest_source
```

## Developer Documentations

DKAN developers can use the api provided by DKAN Harvest to add support for
additioanl harvest source types. The `dkan_harvest_datajson` module encapsulate
the reference implementation providing support for POD type sources.

If you need to harvest from an end point type other then POD. You can extend
the DKAN Harvest APIs to implement said support by following a simple
checklist:
* Define a new Harvest Source Type via `hook_harvest_source_types`.
* Implement the Harvest Source Type cache callback.
* Implement the Harvest Source Type Migration Class.
* (Optional) Write tests for your source type implementation.

### Define a new Harvest Source Type

DKAN Harvest leverage drupal's hook system to provide a way to extend the
Source types that DKAN Harvest supports. To add a new harvest source type the
we return their definitions as array items via the
`hook_harvest_source_types()` hook.

```php
/**
 * Implements hook_harvest_source_types().
 */
function dkan_harvest_test_harvest_source_types() {
  return array(
    'harvest_test_type' => array(
      'machine_name' => 'harvest_test_type',
      'label' => 'Dkan Harvest Test Type',
      'cache callback' => 'dkan_harvest_cache_default',
      'migration class' => 'HarvestMigration',
    ),

    // Define another harvest source type.
    'harvest_another_test_type' => array(
      'machine_name' => 'harvest_another_test_type',
      'label' => 'Dkan Harvest Another Test Type',
      'cache callback' => 'dkan_harvest_cache_default',
      'migration class' => 'HarvestMigration',
    ),
  );
}
```

Each array item define one single harvest source. Each harvest source item consist of an array with 4 keyed values:
* 'machine_name'
* 'label'
* 'cache callback'
* 'migration class'

#### Machine Name
Unique string identifying the harvest source type.

#### Label
This label wil be used on the harvest add node form.

#### Cache Callaback
This is the function called by the core DKAN harvest to perform a harvest cache
operation on a source with a specific type. This callback takes a HarvestSource
object and a timestamp of the harvest start time. This callback returns a
HarvestCache object which contains the result of the cache operation.

```php
/**
 * @param HarvestSource $source
 * @param $harvest_updatetime
 *
 * @return HarvestCache
 */
function dkan_harvest_datajson_cache(HarvestSource $source, $harvest_updatetime)
```

This callback takes care of downloading/filtering/altering the data from the
source end-point to the local file directory provided by the
HarvestSource::getCacheDir() method. The recommended folder structure for
cached data is to have one dataset per uniqly named file. The cache folder
directory structure is processed later during the migrate task and a lot of
curretly developed tooling was build with the one-dataset-per-file organization
in mind.

```sh
$ tree
.
├── 5251bc60-02e2-4023-a3fb-03760551ab4a
├── 80756f84-894f-4796-bb52-33dd0a54164e
├── 846158bd-1821-48d8-80c8-bb23a98294a9
└── 84cada83-2382-4ba2-b9be-97634b422a07

0 directories, 4 files

$ cat 84cada83-2382-4ba2-b9be-97634b422a07
/* JSON content of the cached dataset data */
```

The harvest cache function needs to support the source alteration condition
available from the harvest source via the `field_dkan_harvest_filters`,
`field_dkan_harvest_excludes`, `field_dkan_harvest_overrides`,
`field_dkan_harvest_defaults`fields. Each of this configuration is available
from the HarvestSource object via the `HarvestSource::filters`,
`HarvestSource::excludes`, `HarvestSource::overrides`,
`HarvestSource::defaults` methodes.

#### Migration Class

The common harvest migration logic is encapsulated in the `HarvestMigration`
class. Core DKAN Harvest will support only migration classes extended from
`HarvestMigration`. This class is responsible for consuming the downloaded data
during the harvest cache step to create the DKAN `dataset` and associated
nodes.

Implementing a Harvest Source Type Migration class is the matter of checking
couple of boxes:
* Wire the cached files on the `HarvestMigration::__construct()` method.
* Override the fields mapping on the `HarvestMigration::setFieldMappings()` method.
* Add alternate logic for existing default DKAN fields or extra logic for
  custom fields on the `HarvestMigration::prepareRow()` and the
  `HarvestMigration::prepare()`.

Working on the Migration Class for Harvest Source Type should be straitforward,
but a good knowladge on how [migrate
works](https://www.drupal.org/node/1006982) is a big help.

##### `HarvestMigration::__construct()`

Setting the `MigrateSourceList` is the only logic required during the
construction of the extended `HarvestMigration`. During the harvest migration
we can't reliably determin and parse the type of cache file (JSON, XML, etc..)
so we still need to provide this information to the Migration class via the
`MigrateItem` variable. the Migrate module provide different helpful class for
different input file parsing (`MigrateItemFile`, `MigrateItemJSON`,
`MigrateItemXML`). For the the POD `dkan_harvest_datajson` reference
implementation we use the `MigrateItemJSON` class to read the JSON files
downloaded from data.json end-points.

```php
public function __construct($arguments) {
  parent::__construct($arguments);
  $this->itemUrl = drupal_realpath($this->dkanHarvestSource->getCacheDir()) .
    '/:id';

  $this->source = new MigrateSourceList(
    new HarvestList($this->dkanHarvestSource->getCacheDir()),
    new MigrateItemJSON($this->itemUrl),
    array(),
    $this->sourceListOptions
  );
}
```

##### `HarvestMigration::setFieldMappings()`
The default Mapping for all the default DKAN fields and properties is done on
the `HarvestMigration::setFieldMapping()` method. Overriding one or many field
mapping is done by overrrding the `setFieldMapping()` in the child class and
add/update the new/changed fields.

For example to override the mapping for the `og_group_ref` field.
```php
  public function setFieldMappings() {
    parent::setFieldMappings();
    $this->addFieldMapping('og_group_ref', 'group_id');
```

##### Resources import
The base `HarvestMigration` class will (by default) look for a `$row->resources` objects
array that should contain all the data needed for constructing the resource
node(s) associated with the dataset. the helper method
`HarvestMigration::prepareResourceHelper()` should make creating the
`resources` array items more streamlined.

Example code snippet:
```php
/**
 * Implements prepareRow.
 */
public function prepareRow($row) {
  // Redacted code
  
  $row->resources = $this->prepareRowResources($row->xml);
  
  // Redacted code
}
```

##### [DKAN Dataset Metadata Source](https://github.com/NuCivic/dkan_dataset_metadata_source) support
If DKAN dataset metadata source is available. DKAN harvest will take care of
creating the `dkan_dataset_metadata_source` node and linking a copy of the
cached file to it if the `$row->metadata_source` object set setup with all the
needed info.

Example code snippet:
```php
/**
 * Implements prepareRow.
 */
public function prepareRow($row) {
  // Redacted code
  
  $row->metadata_source = self::prepareMetadataSourceHelper(
    $metadata_source_cached_filepath,
    'ISO-19115 Metadata for ' . $row->dkan_harvest_object_id,
    'ISO 19115-2'
  );
  
  // Redacted code
}
```
##### [DKAN Workflow](https://github.com/NuCivic/dkan_workflow) support
By default, DKAN Harvest will make sure that the harvested dataset node will be
set to the `published` moderation state if the DKAN Workflow module is enabled
on the DKAN site. This can be changed at the fields mapping level by overriding
the `workbench_moderation_state_new` field.

### Tests
#### PHPUnit tests

All the PHPUnit tests for DKAN Harvest are available in the
`test/phpunit/` folder. All the dependencies are available in the `composer.json` file in the root folder. To run the tests:
* Make sure that you are currently on a working DKAN site.
* Change the `$path` variable in the `test/phpunit/boot.php`to point to the
  current drupal root folder.
* `composer install`
* Inside the `test/phpunit` directory, run `../../bin/phpunit`

#### Behat tests

All the current Behat tests for DKAN Harvest are available in the `test/behat/`
folder. All the dependencies are available in the `composer.json` file in the
root folder. To run the tests:
* Make sure that you are currently on a working DKAN site.
* Change the `test/behat/behat.yml` to match your current envirement.
* `composer install`
* Inside the `test/behat` directory, run `../../bin/behat`

## Todo's

+ Move as much as possible from `DataJSONHarvest` class to **dkan_migrate_base** 's own `MigrateDataJsonDatasetBase`
+ Create drupal admin page to subscribe sources of data.
+ Better support for handling failed harvest migrations.
+ Greater Test coverage.
+ Extend drush commands.
