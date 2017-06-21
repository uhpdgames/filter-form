<?php

//defined('PHP_EXCEL_LIBRARIES') or define('PHP_EXCEL_LIBRARIES', libraries_get_path('Classes') . 'PHPExcel.php');

module_load_include('inc', drupal_get_path('module', 'uit_filter') . '/filter');
function uit_filter_help($path, $args){
  switch ($path) {
    case 'admin/help/uit-filters':
      return t('Đang cập nhật....');
      break;
  }
}

/**
 * Form filter mặc định được khởi tạo.
 * @param $form
 * @param $form_state
 * @param bool $list
 */
function uit_filter_form_default(&$form, $form_state, $list = false){
  $state = (isset($form_state['uit_filter']) ? $form_state['uit_filter'] : NULL);

  if (isset($state['session']))
    $_SESSION['session'] = $state['session'];
  else return; //exit module
  if (isset($state['filter']) && is_array($state['filter'])) {
    $form['filter_wraper'] = array(
      '#type' => 'fieldset',
      '#title' => t('Thông tin lọc'),
      '#collapsible' => TRUE,
      '#prefix' => '<div id="filter-wrapper" class="container-inline">',
      '#suffix' => '</div>',
    );
    $form['filter_wraper']['filter'] = uit_filter_form(uit_filters($state));
    $form['filter_wraper']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Lọc')
    );
    if ($list) {
      if (isset($state['list']) && is_array($state['list'])){
        $is_table_of_select = (isset($state['is_table_of_select']) ? $state['is_table_of_select'] : null);
        $select_all = (isset($state['select_all']) ? form_state['$select_all'] : false);
        $full_stack = (isset($state['full_stack']) ? form_state['$full_stack'] : false);
        $form['list'] = array('#markup' => uit_filter_render_list($form_state, $is_table_of_select, $select_all, $full_stack));
      }
    }
  }
}

/**
 * Form export excel được khởi tạo.
 * 'excel_list' 1 => 'Theo dữ liệu lọc', 0 => 'Tất cả dữ liệu'
 * @param $form
 * @param $form_state
 */
function uit_filter_export_excel_form(&$form, &$form_state) {
  $form['excel_wraper'] = array(
    '#type' => 'fieldset',
    '#title' => t('Xuất dữ liệu'),
    '#collapsible' => TRUE,
    '#prefix' => '<div id="filter-wrapper" class="container-inline">',
    '#suffix' => '</div>',
  );
  $form['excel_wraper']['excel_list'] = array(
    '#type' => 'select',
    '#options' => array( 1 => 'Theo dữ liệu lọc', 0 => 'Tất cả dữ liệu'),
  );
  $form['excel_wraper']['excel_submit'] = array(
    '#type' => 'submit',
    '#value' => 'Xuất file excel',
  );
}

/**
 * Form filter, ghi nhận dữ liệu
 * @param $form_state
 */
function uit_filter_set_filters($form_state){
  $state = (isset($form_state['uit_filter']) ? $form_state['uit_filter'] : NULL);
  if (!$state) {
    drupal_set_message(t('Chưa thiết lập form filer'));
    return;
  }
  $filters = uit_filters($state);
  $values = $form_state['values'];
  foreach ($filters as $filter => $options) {
    if (isset($values[$filter]) && $values[$filter] != '') {
      if (isset($filters[$filter]['options'])) {
        $flat_options = form_options_flatten($filters[$filter]['options']);
        if (isset($flat_options[$values[$filter]])) {
          $_SESSION[$_SESSION['session']][$filter] = $values[$filter];
        }
      } else {
        $_SESSION[$_SESSION['session']][$filter] = $values[$filter];
      }
    } else {
      unset($_SESSION[$_SESSION['session']][$filter]);
    }
  }
}

/**
 * Gắn giá trị session cho $name
 * @param $name
 * @return is_value_session by $name
 */
function uit_filter_is_value_session($name){
  if (!isset($_SESSION['session'])) return;
  return !empty($_SESSION[$_SESSION['session']][$name]) ? $_SESSION[$_SESSION['session']][$name] : NULL;
}

/**
 * Conditioned by $target
 * @param SelectQueryInterface $q
 * @param $target
 */
function uit_filter_search_value(SelectQueryInterface &$q, $target){
  if (!isset($target) || !is_array($target)) return;
  foreach ($target as $v) {
    $value = uit_filter_is_value_session($v);
    if (empty($value)) continue;
    switch ($v) {
      case 'sodong':
        break;
      default:
        $q->condition($v, '%' . db_like($value) . '%', 'LIKE');
    }
  }
}

#RENDER
/**
 * Trả vể mảng dữ liệu đã xử lý lọc
 * @param $form_state : require
 * @param null $is_table_of_select : Dạng chuỗi, là tên 1 field cần xữ lý
 * @param false $select_all : Lấy tất cả dữ liệu bởi filter bằng phương thức select * form table.. LÀ TRUE
 * @param false $full_stack : Lấy tất cả dữ liệu bởi filter là TRUE
 * @return array
 */
function uit_filter_render_items($form_state, $is_table_of_select = null, $select_all = false, $full_stack = false) {
  $state = (isset($form_state['uit_filter']) ? $form_state['uit_filter'] : NULL);
  if (!isset($state['db_select']) || !is_string($state['db_select'])) return array(); //not available for query. Exit!

  $stt = 1;
  $items = $header = array();
  $header['stt']['data'] = 'STT';
  $values = $state['list'];
  for ($i = 0; $i < count($values['header']); $i++) {
    $header[$i]['data'] = $values['header'][$i];
    $header[$i]['field'] = $values['field'][$i];
  }
  $query = db_select($state['db_select'], 'uit')
    ->extend('PagerDefault')
    ->extend('TableSort');
  uit_filter_search_value($query, $state['filter']['field']);
  $limit = uit_filter_is_value_session('sodong') ? uit_filter_is_value_session('sodong') : 25;

  $field = (isset($is_table_of_select) ? array_merge(array($is_table_of_select), $values['field']) : $values['field']);
  $header = (isset($is_table_of_select) ? array_merge(array(''), $header) : $header);

  if ($select_all) $field = $header = array();
  $query->fields('uit', $field);

  if ($full_stack) $limit = $query->countQuery()->execute()->fetchField();
  $query->limit($limit)->orderByHeader($header);
  $result = $query->execute();
  if (count($result) == 0) return $items;

  foreach ($result as $i => $row) {
    if ($is_table_of_select) {
      $id = $row->$is_table_of_select;
      //unset($row->$is_table_of_select);
      //$items[$i][] ="<input type='checkbox' name='' value=$id>";
      $items[$i]['id']['data'] = array(
        '#type' => 'checkbox',
        '#values' => $id,
      );
      $items[$i]['stt'] = $stt++;
    } else $items[$i]['stt'] = $stt++;
    foreach ($field as $v) {
      $items[$i][] = $row->$v;
    }
  }
  $items['header'] = $header;
  return $items;
}

/**
 * Trả về danh sách dữ liệu đã xử lý lọc
 * @param $form_state
 * @param null $is_table_of_select
 * @param bool $select_all
 * @param bool $full_stack
 * @return list
 */
function uit_filter_render_list($form_state, $is_table_of_select = null, $select_all = false, $full_stack = false) {
  $items = uit_filter_render_items($form_state, $is_table_of_select, $select_all, $full_stack);
  $output = 'Không có dữ liệu';
  global $pager_total_items;

  if (count($items) > 0) {
    $header = $items['header'];
    unset($items['header']);
    $output = t('Tổng danh sách: @total', array('@total' => $pager_total_items[0]));
    $output .= theme('table', array('header' => $header, 'rows' => $items, 'empty' => 'Không có dữ liệu'));
    $output .= theme('pager');
  }
  return $output;
}

/**
 * Xuất dữ liệu bằng 4 phương thức tùy chọn.
 *   #METHOD 1: Xuất dữ liệu được lọc (trang hiển thị hiện tại)
 *   #METHOD 2: Xuất tất cả dữ liệu (được lọc)
 *   #METHOD 3: Xuất tất cả dữ liệu (không lọc)
 *   #METHOD 4: Xuất dữ liệu bất kỳ, theo truy vấn con được truyền vào bởi $data
 *
 * @param $data
 * @param null $path
 * @param null $filename
 */
function uit_filter_render_export_excel($data, $path = null, $filename = null){
  require_once 'sites/all/libraries/PHPExcel/Classes/PHPExcel.php';
  //PHP_EXCEL_LIBRARIES

  if (count($data) == 0) {
    drupal_set_message(t('Không có dữ liệu để xuất.'));
    return;
  }
  if (!isset($path)) {
    $path = variable_get('file_public_path', conf_path() . '/files') . '/exports';
  }
  if (!file_exists($path)) mkdir($path);
  if (!isset($filename)) $filename = date('Y-m-d-') . '.xlsx';
  $path .= '/' . $filename;

  $objPHPExcel = new PHPExcel();
  //$objReader->setReadDataOnly(true);
  $active_sheet = $objPHPExcel->getActiveSheet();

  $r = 2;
  $c = 0;
  //title
  if (isset($data['header'])) {
    foreach ($data['header'] as $title) {
      $active_sheet->setCellValueExplicitByColumnAndRow($c, 1, $title['data'], PHPExcel_Cell_DataType::TYPE_STRING);
      $c++;
    }
  }
  //values
  $c = 0;
  foreach ($data as $rows) {
    foreach ($rows as $row) {
      $active_sheet->setCellValueExplicitByColumnAndRow($c++, $r, $row, PHPExcel_Cell_DataType::TYPE_STRING);
    }
    $c = 0;
    $r++;
  }
  __sweet_export_excel($active_sheet);
  $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
  ob_end_clean();
  $objWriter->save($path);

  if($path){
    drupal_add_http_header('Pragma', 'public');
    drupal_add_http_header('Expires', '0');
    drupal_add_http_header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
    drupal_add_http_header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    drupal_add_http_header('Content-Disposition', 'attachment; filename=' . basename($path) . ';');
    drupal_add_http_header('Content-Transfer-Encoding', 'binary');
    drupal_add_http_header('Content-Length', filesize($path));
    readfile($path);
    unlink($path);
    drupal_exit();
  }
}

/**
 * Xuất PDF
 */
function uit_filter_render_export_pdf($html, $filename = null, $information_pdf = array()){
  //Tạo PDF
  $pdf = uit_filter_createPdf($information_pdf);
  $filename = (isset($filename) ? $filename : '');
  $html = (isset($html) ? $html : null);
  $pdf->AddPage();
  $pdf->writeHTML($html, true, false, true);
  $pdf->endPage();
  $pdf->lastPage(); //set cursor at last page, because autopagebreak not do it

  //Close and output PDF document
  ob_end_clean();
  $fileName =  date("Y-m-d"). filter_xss($filename).'.pdf';
  $pdf->Output($fileName, 'I');
  drupal_exit();
}

/**
 * Hàm Khởi Tạo PDF
 * Thông tin PDF $information_pdf
 * $information_pdf['author']
 * $information_pdf['title']
 * $information_pdf['subject']
 * $information_pdf['keywords']
 * @param array $information_pdf
 * @return TCPDF
 */
function uit_filter_createPdf($information_pdf = array()) {
  //ob_start();
  require_once('sites/all/libraries/tcpdf/tcpdf.php');
  $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

  //Set $information_pdf
  $info_pdf = (isset($information_pdf) ? $information_pdf : array());
  $author = (isset($info_pdf['author']) ?$info_pdf['author'] : '');
  $title = (isset($info_pdf['title']) ?$info_pdf['title'] : '');
  $subject = (isset($info_pdf['subject']) ?$info_pdf['subject'] : '');
  $keywords = (isset($info_pdf['keywords']) ?$info_pdf['keywords'] : '');

  // set document information
  $pdf->SetCreator(PDF_CREATOR);
  $pdf->SetAuthor($author);
  $pdf->SetTitle($title);
  $pdf->SetSubject($subject);
  $pdf->SetKeywords($keywords);

  // remove default header/footer
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);

  // set default monospaced font
  $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

  // set margins
  $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP - 25, PDF_MARGIN_RIGHT);

  // set auto page breaks
  $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

  // set image scale factor
  $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

  // set some language-dependent strings (optional)
  if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
    require_once(dirname(__FILE__) . '/lang/eng.php');
    $pdf->setLanguageArray($l);
  }

  $pdf->SetDisplayMode('fullpage', 'SinglePage', 'UseNone');
  // set font
  $pdf->SetFont('freeserif', '', 13);

  return $pdf;
}

/**
 * Vẻ chart thể hiện dữ liệu số
 */
function uit_filter_render_charts(&$form, &$form_state, $chart) {
  $form['charts'] = array(
    '#type' => 'fieldset',
    '#title' => 'Biểu đồ thống kê',
    '#collapsible' => TRUE,
    '#collapsed' => FALSE
  );
  $form['charts']['type_chart'] = array(
    '#type' => 'select',
    '#options' => uit_filter_load_types(),
    '#default_value' => (isset($form_state['values']['type_chart']) ? $form_state['values']['type_chart']: 'LineChart'),
  );
  $header = (isset($chart['header']) ? (array('all'=>'Tất cả') + $chart['header']) : array());
  $form['charts']['view_chart'] = array(
    '#type' => 'checkboxes',
    '#options' => $header,
    '#default_value' => (isset($form_state['values']['view_chart']) ? $form_state['values']['view_chart']: drupal_map_assoc(array('all'))),
  );
  $form['charts']['submit_chart'] = array('#type' => 'submit','#value' => 'Lọc');
  $form['charts']['charts_One'] = array('#markup' => uit_filter_set_graph($form_state, $chart));
}
function uit_filter_init() {
  drupal_add_js('https://www.google.com/jsapi', 'external');
}
#END RENDER

/**
 * Hiển thị chi tiết dữ liệu
 * @param $form
 * @param $items
 * @param null $id_item
 */
function uit_filter_detail_items(&$form, $items, $id_item = null){
  if (count($items) == 0) return;
  else {
    $header = $items['header'];
    $header[-1]['data'] = 'STT';
    for ($i = 0; $i < count($items); $i++) {
      $info = (isset($items[$i]) ? $items[$i] : '');
      for ($j = 0; $j < count($info); $j++) {
        $info[$j] = (isset($info[$j]) ? $info[$j] : '');
        $form['detail_item' . $i . $j] = uit_filter_markup_info($header[$j - 1]['data'], $info[$j]);
      }
    }
    $form['paper'] = array('#theme' => 'pager');
  }
  //if($id_item)//detail
}

/**
 * Hiển thị chi tiết từng dữ liệu
 * @param $title
 * @param $info
 * @return #markup
 */
function uit_filter_markup_info($title, $info){
  return array('#markup' => t('<strong>@title</strong> ' . '@info <br>', array('@title' => $title, '@info' => $info)));
}

//debug
function uit_filter_debug_values_session($dpm = false, $any_state = array()){
  if ($any_state) {
    if ($dpm) dpm($any_state, 'status');
    else {
      echo "<pre>";
      print_r($any_state);
      echo "</pre>";
      die();
    }
  }
  if ($dpm) dpm($_SESSION[$_SESSION['session']], 'session');
  else {
    echo "<pre>";
    print_r($_SESSION[$_SESSION['session']]);
    echo "</pre>";
    die();
  }
}
