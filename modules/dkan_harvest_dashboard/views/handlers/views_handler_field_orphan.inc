<?php

/**
 * Handler class implementation for Dkan Harvest Migration Dataset Orphan field.
 */
class views_handler_field_orphan extends views_handler_field {

  /**
   * {@inheritdoc}
   */
  function query() {
    // do nothing -- to override the parent query since we are not using a
    // direct database query.
  }

  /**
   * {@inheritdoc}
   */
  function get_value($values, $field = NULL) {
    // This will get executed quite often especially if there is sorting
    // involved. Instead of loading the hole node object and getting the
    // machine_name from it but use a direct database query instead.
    $result = db_query("SELECT needs_update FROM migrate_map_dkan_harvest_migrate_harvest_test WHERE destid1 = :nid;",
      array(
        ':nid' => $values->nid,
      ))->fetchField();
    // const STATUS_IGNORED_NO_SOURCE = 20;
    if ($result == 20) {
      $orphan_status = 'Yes';
    }
    else {
      $orphan_status = '';
    }
    return $orphan_status;
  }

  /**
   * {@inheritdoc}
   */
  function click_sort($order) {
    // Usually this method will add a condition to the fields sql query. Since
    // we are dealing with a psudo field with no direct database query we just
    // store this and we process the sorting in the self::post_execute()
    // methode.
    $this->order = $order;
  }

  /**
   * {@inheritdoc}
   */
  function post_execute(&$values) {
    // Make sure to sort the proccessed views rows if this field is set to be
    // sortable.
    if (property_exists($this, 'order')) {
      foreach($values as &$value) {
        $value->dkan_harvest_orphan_status = $this->get_value($value);
      }

      usort($values, function ($a, $b) {
        if ($a->dkan_harvest_orphan_status == $b->dkan_harvest_orphan_status) {
          return 0;
        }
        else {
          return ($a->dkan_harvest_orphan_status > $b->dkan_harvest_orphan_status) ? 1 : -1;
        }
      });

      if ($this->order == 'desc') {
        $values = array_reverse($values);
      }
    }
  }
}
