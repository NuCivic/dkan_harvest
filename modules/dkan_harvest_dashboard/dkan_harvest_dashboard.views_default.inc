<?php
/**
 * @file
 * dkan_harvest_dashboard.views_default.inc
 */

/**
 * Implements hook_views_default_views().
 */
function dkan_harvest_dashboard_views_default_views() {
  $export = array();

  $view = new view();
  $view->name = 'dkan_harvest_dashboard';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'node';
  $view->human_name = 'DKAN Harvest Dashboard';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Harvest Dashboard';
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'views_bulk_operations' => 'views_bulk_operations',
    'title' => 'title',
    'field_dkan_harveset_type' => 'field_dkan_harveset_type',
    'dkan_harvest_harvest_date' => 'dkan_harvest_harvest_date',
    'dkan_harvest_harvest_count' => 'dkan_harvest_harvest_count',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'views_bulk_operations' => array(
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'title' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'field_dkan_harveset_type' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'dkan_harvest_harvest_date' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'dkan_harvest_harvest_count' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
  );
  /* Field: Bulk operations: Content */
  $handler->display->display_options['fields']['views_bulk_operations']['id'] = 'views_bulk_operations';
  $handler->display->display_options['fields']['views_bulk_operations']['table'] = 'node';
  $handler->display->display_options['fields']['views_bulk_operations']['field'] = 'views_bulk_operations';
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['display_type'] = '0';
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['enable_select_all_pages'] = 1;
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['row_clickable'] = 1;
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['force_single'] = 0;
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_settings']['entity_load_capacity'] = '10';
  $handler->display->display_options['fields']['views_bulk_operations']['vbo_operations'] = array(
    'rules_component::rules_dkan_harvest_source_cache' => array(
      'selected' => 1,
      'postpone_processing' => 0,
      'skip_confirmation' => 0,
      'override_label' => 0,
      'label' => '',
    ),
    'rules_component::rules_dkan_harvest_source_cache_and_migrate' => array(
      'selected' => 1,
      'postpone_processing' => 0,
      'skip_confirmation' => 0,
      'override_label' => 0,
      'label' => '',
    ),
    'rules_component::rules_dkan_harvest_source_migrate' => array(
      'selected' => 1,
      'postpone_processing' => 0,
      'skip_confirmation' => 0,
      'override_label' => 0,
      'label' => '',
    ),
  );
  /* Field: Content: Title */
  $handler->display->display_options['fields']['title']['id'] = 'title';
  $handler->display->display_options['fields']['title']['table'] = 'node';
  $handler->display->display_options['fields']['title']['field'] = 'title';
  $handler->display->display_options['fields']['title']['alter']['word_boundary'] = FALSE;
  $handler->display->display_options['fields']['title']['alter']['ellipsis'] = FALSE;
  /* Field: Content: Type */
  $handler->display->display_options['fields']['field_dkan_harveset_type']['id'] = 'field_dkan_harveset_type';
  $handler->display->display_options['fields']['field_dkan_harveset_type']['table'] = 'field_data_field_dkan_harveset_type';
  $handler->display->display_options['fields']['field_dkan_harveset_type']['field'] = 'field_dkan_harveset_type';
  /* Field: Dkan Harvest: Harvest Migrate Last Updated */
  $handler->display->display_options['fields']['dkan_harvest_harvest_date']['id'] = 'dkan_harvest_harvest_date';
  $handler->display->display_options['fields']['dkan_harvest_harvest_date']['table'] = 'dkan_harvest_dashboard';
  $handler->display->display_options['fields']['dkan_harvest_harvest_date']['field'] = 'dkan_harvest_harvest_date';
  $handler->display->display_options['fields']['dkan_harvest_harvest_date']['label'] = 'Last Updated';
  $handler->display->display_options['fields']['dkan_harvest_harvest_date']['empty'] = 'N/A';
  $handler->display->display_options['fields']['dkan_harvest_harvest_date']['date_format'] = 'time ago';
  $handler->display->display_options['fields']['dkan_harvest_harvest_date']['second_date_format'] = 'long';
  /* Field: Dkan Harvest: Harvest Migrate Datasets Count */
  $handler->display->display_options['fields']['dkan_harvest_harvest_count']['id'] = 'dkan_harvest_harvest_count';
  $handler->display->display_options['fields']['dkan_harvest_harvest_count']['table'] = 'dkan_harvest_dashboard';
  $handler->display->display_options['fields']['dkan_harvest_harvest_count']['field'] = 'dkan_harvest_harvest_count';
  $handler->display->display_options['fields']['dkan_harvest_harvest_count']['label'] = 'Datasets Count';
  /* Filter criterion: Content: Published */
  $handler->display->display_options['filters']['status']['id'] = 'status';
  $handler->display->display_options['filters']['status']['table'] = 'node';
  $handler->display->display_options['filters']['status']['field'] = 'status';
  $handler->display->display_options['filters']['status']['value'] = 1;
  $handler->display->display_options['filters']['status']['group'] = 1;
  $handler->display->display_options['filters']['status']['expose']['operator'] = FALSE;
  /* Filter criterion: Content: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'node';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['value'] = array(
    'harvest_source' => 'harvest_source',
  );

  /* Display: Harvest Dashboard */
  $handler = $view->new_display('page', 'Harvest Dashboard', 'page');
  $handler->display->display_options['defaults']['access'] = FALSE;
  $handler->display->display_options['access']['type'] = 'role';
  $handler->display->display_options['access']['role'] = array(
    30037204 => '30037204',
    27274083 => '27274083',
  );
  $handler->display->display_options['path'] = 'admin/dkan/harvest/dashboard';
  $handler->display->display_options['menu']['type'] = 'normal';
  $handler->display->display_options['menu']['title'] = 'DKAN Harvest Dashboard';
  $handler->display->display_options['menu']['weight'] = '0';
  $handler->display->display_options['menu']['name'] = 'management';
  $handler->display->display_options['menu']['context'] = 0;
  $handler->display->display_options['menu']['context_only_inline'] = 0;
  $export['dkan_harvest_dashboard'] = $view;

  return $export;
}
