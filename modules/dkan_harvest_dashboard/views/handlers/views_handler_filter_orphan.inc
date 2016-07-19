<?php

/**
 * Handler class implementation for Dkan Harvest Migration Dataset Orphan field.
 */
//class views_handler_filter_orphan extends views_handler_filter {

  /**
   * Options form subform for setting exposed filter options.
   */
//   function value_form(&$form, &$form_state) {
//     parent::value_form($form, $form_state);

//     // Get ids of all harvested datasets.
//     $result = db_select('field_data_field_harvest_source_ref', 'hsr')
//       ->fields('hsr', array('entity_id', 'field_harvest_source_ref_target_id'))
//       ->isNotNull('hsr.field_harvest_source_ref_target_id')
//       ->execute();

//     // Get the machine names of the harvest sources.
//     $datasets = array();
//     while ($row = $result->fetchAssoc()) {
//       $s = getHarvestSourceFromNode($row->entity_id);
//       $datasets[$row['entity_id']] = $s;
//     }

//     // Query each migration table to get the 'needs_update' values.
//     foreach ($datasets as $id => $source) {
//       $table = 'migrate_map_dkan_harvest_migrate_' . $source;
//       $query = db_select($table, 't')
//         ->fields('t', array('destid1'))
//         ->condition('needs_update', 20, '=');
//       $result = $query->execute();
//       $orphaned[] = $result;
//     }
    
//     $orphan_options = array(
//       '0' => 'Not Orphaned',
//       '20' => 'Orphaned',
//     );

//     // Create form element with options retrieved from database.
//     $form['value']['orphan'] = array(
//       '#title' => t('Show Orphaned datasets')
//       '#type' => 'select',
//       '#options' => $orphan_options,
//       '#default_value' => 0,
//     );
//   }

//   /**
//    * Alters Views query when filter is used.
//    */
//   function query() {
//     // make sure base table is included in the query
//     $this->ensure_my_table();

//     // retrieve real filter name from view options
//     // this requires 'real field' filter option to be set (see code above)
//     $real_field_name = $this->real_field;
//     // get the value of the submitted filter
//     $value = $this->view->exposed_data[$real_field_name];

//     // finally, alter Views query
//     if (is_numeric($value) && $value != 0) {
//       /* 
//         Having several custom exposed filters, make sure subsitution patterns
//         (e.g. :filtername_value below) don't match across different filters.
//         I spent some time figuring out why all my filters had the same value.
//         It looks like the query skeleton is built first and then all replacements
//         are made in bulk. Prefixing value with filter name looks good imo.
//       */
//       $this->query->add_where_expression($this->options['group'],
//         "FROM_UNIXTIME(node.created, '%Y') = :filtername_value",
//         array(':filtername_value' => $value));
//     }
//   }
// }