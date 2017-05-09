<?php
function uit_filter_form($filters) {
  $form = array();
  $options = array('type', 'title', 'size', 'options', 'default_value', 'description', 'autocomplete_path');
  foreach ($filters as $key => $filter) {
    foreach ($options as $k => $v) {
      isset($filter[$v]) ? $form[$key]["#$v"] = $filter[$v] : '';
    }
  }
  return $form;
}

/**
 * Tạo fields cho filter
 * Updating! autocomplete_path
 * @param $state
 * @return array $filters
 */
function uit_filters($state) {
  $filters = array();
  $form = $state['filter'];
  $option_change = $state['option_filter'];
  $description = isset($state['description']) ? $state['description'] : array();

  for ($i = 0; $i < count($form['field']); $i++) {
    if (is_array($option_change[$i])) {
      $filters[$form['field'][$i]] = array(
        'type' => 'select',
        'title' => $form['header'][$i],
        'options' => $option_change[$i],
        'description' => (isset($description[$i]) ? $description[$i] : ''),
        'default_value' => uit_filter_is_value_session($form['field'][$i])
      );
    }
    if ($option_change[$i] == 'textfied' || is_string($option_change[$i])) {
      $filters[$form['field'][$i]] = array(
        'type' => 'textfield',
        'title' => $form['header'][$i],
        'autocomplete_path' => (($option_change[$i] != 'textfied') ? $option_change[$i] : ''),
        'description' => (isset($description[$i]) ? $description[$i] : ''),
        'default_value' => uit_filter_is_value_session($form['field'][$i])
      );
    }
  }
  $filters['sodong'] = array(
    'type' => 'select',
    'title' => 'Số dòng',
    'options' => (array('25' => t('25'), '50' => t('50'), '100' => t('100'), '200' => t('200'), '500' => t('500'))),
    'default_value' => (uit_filter_is_value_session('sodong') ? uit_filter_is_value_session('sodong') : 25),
  );
  return $filters;
}

/**
 * Bố cục của excel
 * @param $active_sheet
 */
function __sweet_export_excel(&$active_sheet) {
  $col = array();
  foreach (range('A','Z') as $item) {
    array_push($col, $item);
  }
  foreach ($col as $item){
    $active_sheet ->getHeaderFooter()->setOddFooter('&L&B'. $item);
    $active_sheet ->getColumnDimension($item)->setAutoSize(true);

  }
  $active_sheet->getStyle('A1:Z1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
  ->getStartColor()->setARGB('FFFF0000');
}

//charts
function uit_filter_set_graph(&$form_state, $chart){
  $rows0 = (isset($chart['rows']) ? $chart['rows'] : array());
  $header0 = (isset($chart['header']) ? $chart['header'] : array());
  $columns0 = (isset($chart['columns']) ? $chart['columns'] : array());
  if (!$rows0 || !$header0 || !$columns0) {
    drupal_set_message(t('Dữ liệu không chính xác, không thể vẻ được biểu đồ.'));
    return;
  }
  $value = (isset($form_state['values']) ? $form_state['values']: array());
  $view = isset($value['view_chart']) ? array_filter($value['view_chart']) :  drupal_map_assoc(array('all'));
  //filter
  if (empty($view['all'])) {
    for($m = 0 ; $m < count($view) ; $m++){
      $rows[$m] = $rows0[$m];
      $header[$m] = $header0[$m];
      $columns[$m] = $columns0[$m];
    }
  } else{
    $rows = $rows0;
    $header = $header0;
    $columns = $columns0;
  }

  $settings = array();
  $type = (isset($value['type_chart']) ? $value['type_chart'] : 'LineChart');
  $settings['chart']['chartOne'] = array(
    'header' => $header,
    'rows' => $rows,
    'columns' => $columns,
    'chartType' => $type, //'ColumnChart': 'LineChart'
    'containerId' =>  'uit_filter_graph',
    'options' => array( // Optionals.
      'curveType' => "function",
      'forceIFrame' => FALSE,
      'title' => 'title',//$title
      'width' => 800,
      'height' => 400
    )
  );

  uit_filter_draw_chart($settings);
  //($settings,'$settings');
  return '<br><br><br><div id="uit_filter_graph"></div>';
}
function uit_filter_draw_chart($settings) {
  drupal_alter('draw_chart', $settings);
  foreach ($settings['chart'] as $id => $chart) {
    if (!isset($settings['chart'][$id]['containerId'])) {
      $settings['chart'][$id]['containerId'] = drupal_strtolower(str_replace(" ", "-", $id));
    }
  }
  drupal_add_js(drupal_get_path('module', 'uit_filter') . '/js/uit_filter_graph.js');
  drupal_add_js($settings, array('type' => 'setting'));
  $rs = array(
    'title' =>  $chart['options']['title'],
    'id' =>  $id,
    'weight' => isset($chart['weight']) ? $chart['weight'] : 0,
    'markup' =>  "<div id='{$settings['chart'][$id]['containerId']}'></div>"
  );
  return $rs;
}

/**
 * Các loại charts
 * @return array
 */
function uit_filter_load_types() {
  return array(
    'LineChart' => t('Biểu đồ Đường'),
    'PieChart' => t('Biểu đồ Mảnh'),
    'ColumnChart' => t('Biểu đồ Cột'),
    'AreaChart' => t('Biểu đồ Vùng'),
    'BarChart' => t('Biểu đồ Then'),
    //'OrgChart' => t('Tree')
  );
}