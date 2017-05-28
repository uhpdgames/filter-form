<?php
/**
 * VER 1.00 : FILTER
 * User: uhpdgames
 * Date: 05/28/17
 * Time: 5:48 PM
 */
CLASS UHPDGames_FILTER {

    private $database;
    private $filter;
    private $list;
    private $option_filter;
    private $description = array();
    private $field_options = array('type', 'title', 'size', 'options', 'default_value', 'description', 'autocomplete_path');
    private $sodong_select = array(25 => 25, 50 => 50, 100 => 100);

    public $name_session = 'uhpdgames_filter_session';
    public $name_filter = 'uhpdgames_filter';
    public $select_all = false;
    public $full_stack = false;
    public $render_list = true;

    /**
     * @param array $arr_field
     * @param array $arr_header
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
    public function SET_option_filter($arr = array(), $description = false) {
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

        if(empty($name_database)) {
            $form['filter_wraper'] = array('#markup' => t('Bạn chưa nhập tên bảng nào để lọc<br>Định dạng: table = SET_select_table(name_table);'));
            return;
        }else if(empty($filter) || !is_array($filter)) {
            $form['filter_wraper'] = array('#markup' => t('Bạn chưa cấu hình đúng filter<br>Định dạng: filter = array(field=>array(),header=>array());'));
            return;
        }else if(empty($list) || !is_array($list)) {
            $form['filter_wraper'] = array('#markup' => t('Bạn chưa cấu hình đúng list<br>Định dạng: list = array(field=>array(),header=>array(), list);'));
            return;
        }else{
            if(@session_status())
                $_SESSION[$name_session] = $name_session;
            else {
                @session_start();
                $_SESSION[$name_session] = $name_session;
            }
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
            $form['filter_wraper']['filter'] = $this->filter_form($this->filters($form_state[$name_filter]));
            $form['filter_wraper']['submit'] = array('#type' => 'submit', '#value' => t('Lọc'));
            if ($this->render_list) {
                $form['list'] = array('#markup' => $this->GET_render_list($form_state));
            }
        }
    }

    #SUBMIT
    /**
     * Trả về kết quả tìm kiếm
     * @param $form_state
     */
    public function GET_submit_filters($form_state) {
        $state = (isset($form_state[$this->name_session]) ? $form_state[$this->name_session] : NULL);
        if (!$state) {
            drupal_set_message(t('Chưa thiết lập form filer'));
            return;
        }
        $filters = $this->filters($state);
        $values = $form_state['values'];
        foreach ($filters as $filter => $options) {
            if (isset($values[$filter]) && $values[$filter] != '') {
                if (isset($filters[$filter]['options'])) {
                    $flat_options = @form_options_flatten($filters[$filter]['options']);
                    if (isset($flat_options[$values[$filter]])) {
                        $_SESSION[$_SESSION[$this->name_session]][$filter] = $values[$filter];
                    }
                } else {
                    $_SESSION[$_SESSION[$this->name_session]][$filter] = $values[$filter];
                }
            } else {
                unset($_SESSION[$_SESSION[$this->name_session]][$filter]);
            }
        }
        $form_state['rebuild'] = TRUE;
    }
    #RENDER
    /**
     * Trả vể mảng dữ liệu đã xử lý lọc
     * @param $form_state : require
     * @param false $select_all : Lấy tất cả dữ liệu bởi filter bằng phương thức select * form table.. LÀ TRUE
     * @param false $full_stack : Lấy tất cả dữ liệu bởi filter là TRUE
     * @return array
     */
    public function GET_render_items($form_state) {
        $state = (isset($form_state[$this->name_session]) ? $form_state[$this->name_session] : array());
        if (!isset($state['db_select']) || !is_string($state['db_select'])) return array(); //not found for query. Exit!

        $stt = 1;
        $items = $header = array();
        $header['stt']['data'] = 'STT';
        $values = $state['list'];
        for ($i = 0; $i < count($values['header']); $i++) {
            $header[$i]['data'] = $values['header'][$i];
            $header[$i]['field'] = $values['field'][$i];
        }
        $query = db_select($state['db_select'], 'uhpdgames')
            ->extend('PagerDefault')
            ->extend('TableSort');
        $this->data_search_value($query, $state['filter']['field']);
        $limit = $this->uhpdgames_is_value_session('sodong');

        //$field = (isset($is_table_of_select) ? array_merge(array($is_table_of_select), $values['field']) : $values['field']);
        //$header = (isset($is_table_of_select) ? array_merge(array(''), $header) : $header);

        if ($this->select_all) $field = $header = array(); //error$field$header
        $query->fields('uhpdgames', $field);

        if ($this->full_stack) $limit = $query->countQuery()->execute()->fetchField();
        $query->limit($limit)->orderByHeader($header);
        $result = $query->execute();

        foreach ($result as $i => $row) {
            $items[$i]['stt'] = $stt++;
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
     * @param bool $select_all
     * @param bool $full_stack
     * @return list
     */
    public function GET_render_list($form_state) {
        $items = $this->GET_render_items($form_state);
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
    private function filters($state) {
        $filters = array();
        $form = $state[$this->filter];
        $option_change = $state[$this->option_filter];
        $description = isset($state[$this->description]) ? $this->description : array();

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
        $filters['sodong'] = array(
            'type' => 'select',
            'title' => 'Số dòng',
            'options' => $this->sodong_select,
            'default_value' => ($this->uhpdgames_is_value_session('sodong') ? $this->uhpdgames_is_value_session('sodong') : 25),
        );
        return $filters;
    }
    /**
     * Ghi giá trị session cho $name
     * @param $name
     * @return is_value_session by $name
     */
    private function uhpdgames_is_value_session($name){
        if (!isset($_SESSION[$this->name_session])) return;
        return !empty($_SESSION[$_SESSION[$this->name_session]][$name]) ? $_SESSION[$_SESSION[$this->name_session]][$name] : NULL;
    }
    /**
     * Tìm kiếm theo điều kiện | mục tiêu $target
     * @param SelectQueryInterface $q
     * @param $target
     */
    private function data_search_value(SelectQueryInterface &$q, $target){
        if (!isset($target) || !is_array($target)) return;
        foreach ($target as $v) {
            $value = $this->uhpdgames_is_value_session($v);
            if (empty($value)) continue;
            switch ($v) {
                case 'sodong':
                    break;
                default:
                    $q->condition($v, '%' . db_like($value) . '%', 'LIKE');
            }
        }
    }
    #debug
    public function GET_uhpdgames_debug($dpm = false, $any_state = array()){
        if ($dpm) dpm($_SESSION[$_SESSION[$this->name_session]], 'session');

        if ($any_state) {
            if ($dpm) dpm($any_state, 'status');
            else {
                echo "<pre>";
                print_r($any_state);
                echo "</pre>";
                die();
            }
        } else {
            echo "<pre>";
            print_r($_SESSION[$_SESSION[$this->name_session]]);
            echo "</pre>";
            die();
        }
    }
}
?>