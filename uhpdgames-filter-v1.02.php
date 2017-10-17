<?php
/**
 * VER 1.02
 * Author: uhpdgames

https://github.com/uhpdgames
Home-Page: http://uhpdgames.me | https://uhpdgames.github.io
https://github.com/uhpdgames/filter-form
 */
/**
 *
.----------------.  .----------------.  .----------------.  .----------------.   .----------------.  .----------------.  .----------------.  .----------------.  .----------------.
| .--------------. || .--------------. || .--------------. || .--------------. | | .--------------. || .--------------. || .--------------. || .--------------. || .--------------. |
| | _____  _____ | || |  ____  ____  | || |   ______     | || |  ________    | | | |    ______    | || |      __      | || | ____    ____ | || |  _________   | || |    _______   | |
| ||_   _||_   _|| || | |_   ||   _| | || |  |_   __ \   | || | |_   ___ `.  | | | |  .' ___  |   | || |     /  \     | || ||_   \  /   _|| || | |_   ___  |  | || |   /  ___  |  | |
| |  | |    | |  | || |   | |__| |   | || |    | |__) |  | || |   | |   `. \ | | | | / .'   \_|   | || |    / /\ \    | || |  |   \/   |  | || |   | |_  \_|  | || |  |  (__ \_|  | |
| |  | '    ' |  | || |   |  __  |   | || |    |  ___/   | || |   | |    | | | | | | | |    ____  | || |   / ____ \   | || |  | |\  /| |  | || |   |  _|  _   | || |   '.___`-.   | |
| |   \ `--' /   | || |  _| |  | |_  | || |   _| |_      | || |  _| |___.' / | | | | \ `.___]  _| | || | _/ /    \ \_ | || | _| |_\/_| |_ | || |  _| |___/ |  | || |  |`\____) |  | |
| |    `.__.'    | || | |____||____| | || |  |_____|     | || | |________.'  | | | |  `._____.'   | || ||____|  |____|| || ||_____||_____|| || | |_________|  | || |  |_______.'  | |
| |              | || |              | || |              | || |              | | | |              | || |              | || |              | || |              | || |              | |
| '--------------' || '--------------' || '--------------' || '--------------' | | '--------------' || '--------------' || '--------------' || '--------------' || '--------------' |
'----------------'  '----------------'  '----------------'  '----------------'   '----------------'  '----------------'  '----------------'  '----------------'  '----------------'

 */
/**
 * Class UHPDGAMES_FILTER
 * HOW TO USE
 *
 * CALL
include_once libraries_get_path('UHPGames') . 'filter_form_$ver.php';
module_load_include('php','test_filter', 'filter_form_$ver.php');
include '/filter_form_$ver.php';
 *
 *FUNCTION FORM
$filter = new UHPDGAMES_FILTER();

$field  = array('');
$header =  array('');
$filter->SET_select_table('');
$filter->SET_filter($field, $header, 'filter');
$filter->SET_filter_options(array(''));

$filter->SET_filter($field, $header, 'list');
$filter->create_form_filter($form, $form_state);
 *
 *FUNCTION FORM SUBMIT
$values = $form_state['values'];
$GLOBALS['filter']->GET_submit_filters($values);
 */
CLASS UHPDGAMES_FILTER {

  private $database;
  //private $join_databases;
  private $filter;
  private $list;
  //private $filter_options;
  private $description = array();
  private $field_options = array('type', 'title', 'size', 'options', 'default_value', 'description', 'autocomplete_path');
  private $pages = array(10 => 10, 20 => 20, 50 => 50,100 => 100);

  public $name_session = 'uhpdgames_filter_session';
  public $name_filter = 'uhpdgames_filter';
  public $select_all = false;
  public $full_stack = false;
  public $render_list = true;
  public $display_more_pages = true;

  //public $set_filter = true;

  /*    public function __construct(
        $table_name,
        $filter,
        $filter_options = array(),
        $description = array(),
        $list
      )
      {
        $this->database = $table_name;
        $this->filter = $filter;
        $this->option_filter = $filter_options;
        $this->description = $description;
        $this->list = $list;
      }*/

  /**
   * @param array $arr_field
   * @param array $arr_header
   * @param $type null | list
   * @return array[field, header];
   */
  public function SET_filter($arr_field = array(), $arr_header = array(), $type = 'filter') {
    $values = array(
      'field' => $arr_field,
      'header' => $arr_header
    );
    if($type == 'list') return $this->list = $values;
    else return $this->filter = $values;
  }

  /**
   * @param array $arr =>value : textfield | SELECT
   * @param bool $description : Viết mô tả
   * @return array option_filter
   *
   */
  public function SET_filter_options($arr = array(), $description = false) {
    $filter = isset($this->filter) ? $this->filter : array();
    $count_fields = count($filter['field']) ? : 0;
    $arr_op = array();
    for($i = 0 ; $i < $count_fields ; $i++){
      $arr_op[$i] = $arr[$i];
    }
    if($description)
      return $this->description = $arr_op;
    else return $this->option_filter = $arr_op;
  }

  /**
   * Require table in database
   * @param $name
   * @return string name table filter
   */
  public function SET_select_table($name) {
    return $this->database = $name;
  }
  public function SET_select_more_tables($name) {
    if(!is_array($name)) return $this->join_databases = array();
    return $this->join_databases = $name;
  }
  /**
   * Form filter mặc định được khởi tạo.
   * @param $form
   * @param $form_state
   * //@param bool $list
   */
  public function create_form_filter(&$form, &$form_state) {
    $name_filter = $this->name_filter;
    $name_session = $this->name_session;
    $name_database = isset($this->database) ? $this->database : NULL;
    $filter = isset($this->filter) ? $this->filter : NULL;
    $list = isset($this->list) ? $this->list : NULL;

    /*if(empty($name_database) || !is_string($name_database)) {
        die('Bạn chưa nhập tên bảng nào để lọc<br>Định dạng: SET_select_table(name_table);');
    }else if(empty($filter) || !is_array($filter)) {
        die('Bạn chưa cấu hình đúng filter<br>Định dạng: SET_filter array(field=>array(),header=>array());');
    }else if(empty($list) || !is_array($list)) {
        die('Bạn chưa cấu hình đúng list<br>Định dạng: SET_filter array(field=>array(),header=>array(), list);');
    }*/


    /* if(@session_status())
         $_SESSION[$name_session] = $name_session;*/

    // @session_start();
    //$_SESSION[$name_session] = $name_session;

    $form_state[$name_filter] = array(
      #require
      'session' => $name_session,
      'db_select' => $name_database,
      'filter' => $filter,
      'list' => $list,
      #option
    );
    $form['filter_wraper'] = array(
      '#type' => 'fieldset',
      '#title' => t('Thông tin lọc'),
      '#collapsible' => TRUE,
      '#prefix' => '<div id="filter-wrapper" class="container-inline">',
      '#suffix' => '</div>',
    );
    $form['filter_wraper']['filter'] = $this->filter_form($this->filters());
    $form['filter_wraper']['submit'] = array('#type' => 'submit', '#value' => t('Lọc'),  '#attributes' => array('name' =>'submit_filter'));
    /*if ($this->render_list) {
      $form['list'] = array('#markup' => $this->GET_render_list($form_state, $q));
    }*/
  }

  #SUBMIT
  /**
   * Trả về kết quả tìm kiếm
   * @param $form_state
   */
  public function GET_submit_filters($values) {
    if(isset($_POST['submit_filter'])) {
      /*$state = (isset($form_state[$this->name_filter]) ? $form_state[$this->name_filter] : NULL);
      if (empty($state)) {
        die(t('Chưa thiết lập form filer'));
      }*/
      $filters = $this->filters();
      $name = $this->filter;
      foreach ($filters as $filter => $options) {
        $values_filter = isset($name['filter']['field']) ? $name['filter']['field'] : '';
        if (isset($values[$filter])) {
          $_SESSION[$this->name_session][$filter] = $this->uhpdgames_set_session($values_filter, $values[$filter]);
        } else {
          unset($_SESSION[$this->name_session][$filter]);
        }
      }
    }
    return false;
  }
  public function header ($form_state){
    $state = (isset($form_state[$this->name_filter]) ? $form_state[$this->name_filter] : array());

    $header = array();
    $header['stt']['data'] = 'STT';
    $values = isset($state['list']) ? $state['list'] : array();
    //SET header
    for ($i = 0; $i < count($values['header']); $i++) {
      $header[$i]['data'] = $values['header'][$i];
      $header[$i]['field'] = $values['field'][$i];
    }
    return $header;
  }
  #RENDER
  /**
   * Trả vể mảng dữ liệu đã xử lý lọc
   * @param $form_state : require
   * @return array
   */
  public function GET_render_items($form_state, $result) {
    $state = (isset($form_state[$this->name_filter]) ? $form_state[$this->name_filter] : array());
//        if (!isset($state['db_select']) || !is_string($state['db_select'])) return array(); //not found for query. Exit!

    $stt = 1;
    $items = array();
    $values = $state['list'];

    /*        $query = db_select($state['db_select'], 'uhpdgames');
              if(count($this->join_databases) > 0){
                foreach ($this->join_databases as $condition => $tables){
                  $tag = substr($condition, 0 , 2);
                  $query->join($tables , $tag, "$tag.$condition = uhpdgames.$condition");
                }
              }
            $query->extend('PagerDefault')->extend('TableSort');*/
    // $q->extend('PagerDefault')->extend('TableSort');
    //$this->data_search_value($query, $state['filter']['field']);
    //$this->data_search_value($q, $state['filter']['field']);
    /*$limit = 25;
    if($this->uhpdgames_is_value_session('pages')) {
      $limit = $this->uhpdgames_is_value_session('pages');
    }
    $field = $values['field'];*/
    //$header = (isset($is_table_of_select) ? array_merge(array(''), $header) : $header);

    //if ($this->select_all) $field = $header = array(); //error$field$header
    //$query->fields('uhpdgames', $field);

    //if ($this->full_stack) $limit = $query->countQuery()->execute()->fetchField();
    //if ($this->full_stack) $limit = $q->countQuery()->execute()->fetchField();
    //$q->limit($limit)->orderByHeader($header);
    //$query->limit($limit)->orderByHeader($header);
    //$result = $query->execute()->fetchAll();
    //$result = $q->execute()->fetchAll();
    $field = $values['field'];
    if(count($result) > 0) {
      foreach ($result as $i => $row) {
        $items[$i]['stt'] = $stt++;
        foreach ($field as $v) {
          $name  = strval($v);
          $items[$i][] = $row->$name;
        }
      }
    }
    $items['header'] = $this->header($form_state);
    return $items;
  }
  /**
   * Trả về danh sách dữ liệu đã xử lý lọc
   * @param $form_state
   * @return list
   */
  public function GET_render_list($form_state, $rs) {
    $items = $this->GET_render_items($form_state, $rs);
    $output = 'Không có dữ liệu';
    global $pager_total_items;

    if (count($items) > 1) {
      $header = $items['header'];
      unset($items['header']);
      $output = t('Tổng danh sách: @total', array('@total' => $pager_total_items[0]));
      $output .= theme('table', array('header' => $header, 'rows' => $items, 'empty' => 'Không có dữ liệu'));
      $output .= theme('pager');
    }
    return $output;
  }

  /**
   * Thiết lập filter_form
   * @param $filters
   * @return array
   */
  private function filter_form($filters) {
    $form = array();
    $options = $this->field_options;
    foreach ($filters as $key => $filter) {
      foreach ($options as $k => $v) {
        isset($filter[$v]) ? $form[$key]["#$v"] = $filter[$v] : NULL;
      }
    }
    return $form;
  }
  /**
   * Tạo fields cho filter
   * @param $state is $form_state
   * @return array $filters
   */
  private function filters() {
    $filters = array();
    $form = $this->filter;
    $option_change = $this->option_filter;
    $description = isset($this->description) ? $this->description : array();

    for ($i = 0; $i < count($form['field']); $i++) {
      if (is_array($option_change[$i])) {
        $filters[$form['field'][$i]] = array(
          'type' => 'select',
          'title' => $form['header'][$i],
          'options' => $option_change[$i],
          'description' => (isset($description[$i]) ? $description[$i] : ''),
          'default_value' => $this->uhpdgames_is_value_session($form['field'][$i])
        );
      }else if ($option_change[$i] == 'textfied' || is_string($option_change[$i]) ) {
        $filters[$form['field'][$i]] = array(
          'type' => 'textfield',
          'title' => $form['header'][$i],
          'autocomplete_path' => (($option_change[$i] != 'textfied') ? $option_change[$i] : ''),
          'description' => (isset($description[$i]) ? $description[$i] : ''),
          'default_value' => $this->uhpdgames_is_value_session($form['field'][$i])
        );
      }
    }
    if($this->display_more_pages) {
      $filters['pages'] = array(
        'type' => 'select',
        'title' => 'Số dòng',
        'options' => $this->pages,
        'default_value' => ($this->uhpdgames_is_value_session('pages') ? $this->uhpdgames_is_value_session('pages') : 25),
      );
    }
    return $filters;
  }

  /**
   * Ghi gia tri session tam thoi
   * @param $key
   * @param null $value
   * @return mixed
   */
  public function uhpdgames_set_session($key, $value = null){
    if (isset($value)) {
      if(isset($_SESSION[$key])) unset($_SESSION[$key]);
      $_SESSION[$key] = $value;
    }
    if (isset($_SESSION[$key])) return $_SESSION[$key];
  }
  /**
   * Ghi giá trị session cho $name
   * @param $name
   * @return is_value_session by $name
   */
  public function uhpdgames_is_value_session($name){
    if (!isset($_SESSION[$this->name_session])) return;
    return !empty($_SESSION[$this->name_session][$name]) ? $_SESSION[$this->name_session][$name] : NULL;
  }
  /**
   * Tìm kiếm theo điều kiện | mục tiêu $target
   * @param SelectQueryInterface $q
   * @param $target
   */
  public function data_search_value(SelectQueryInterface &$q, $target){
    if (!isset($target) || !is_array($target)) return;
    foreach ($target as $v) {
      $value = $this->uhpdgames_is_value_session($v);
      if (empty($value)) continue;
      switch ($v) {
        case 'pages':
          break;
        default:
          $q->condition($v, '%' . db_like($value) . '%', 'LIKE');
      }
    }
  }
}
?>