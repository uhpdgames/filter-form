<?php
/**
 * Created by UHPD Games
 * Date: 12/29/2016
 * Time: 10:26 AM
 * Name: filter-form
 */
/**
 * == SETUP MODULE FILTER==
 *  #TYPE 1
    $form_state['uit_filter'] = array(
        #require
        'session' => 'string name',
        'db_select'=> 'name database',
        'filter' => array(
                'field' => array(),
                'header' => array()),
        'option_filter' => array(),
        'list' => array(
                'field' =>array(),
                'header' =>array()),
        #options
        'select_all' => 'string name',
        'is_table_of_select' => 'string name',
        'full_stack' => 'string name',
        'description' => array(),
    );
 *
 * #TYPE 2 : not render items. Only by create a filter form
        $form_state['uit_filter'] = array(
        #require
        'session' => 'string name',
        'filter' => array(
                'field' => array(),
                'header' => array()),
        'option_filter' => array(),
        #options
        'description' => array(),
    );
 * NOTE! option_filter in array: PATH~ IS autocomplete_path || textfied~ IS TEXTFIED || ARRAY()~ IS SELECT
 *
 * uit_filter_form_default($form, $form_state['uit_filter']);
 *
 * uit_filter_set_filters($form_state);
 */

module_load_include('inc', drupal_get_path('module', 'uit_filter') . '/filter');
function uit_filter_help($path, $args) {
    switch ($path) {
        case 'admin/help/uit-filters':
            return t('Đang cập nhật....');
            break;
    }
}
function uit_filter_form_default(&$form, $form_state, $list = false){
    if(isset($form_state['session']))
        $_SESSION['session'] = $form_state['session'];
    else return; //exit module
    if(isset($form_state['filter']) && is_array($form_state['filter'])){
        $form['filter_wraper'] = array(
            '#type' => 'fieldset',
            '#title' => t('Thông tin lọc'),
            '#collapsible' => TRUE,
            '#prefix' => '<div id="filter-wrapper" class="container-inline">',
            '#suffix' => '</div>',
        );
        $form['filter_wraper']['filter'] = uit_filter_form(uit_filters($form_state));
        $form['filter_wraper']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Lọc')
        );
        if($list) {
            $is_table_of_select = (isset($form_state['is_table_of_select']) ? $form_state['is_table_of_select'] : null);
            $select_all = (isset($form_state['select_all']) ? form_state['$select_all'] : false);
            $full_stack = (isset($form_state['full_stack']) ? form_state['$full_stack'] : false);
            $form['list'] = array('#markup' => uit_filter_render_list($form_state, $is_table_of_select, $select_all, $full_stack));
            /**
             * uit_filter_render_list($form_state);
             * uit_filter_render_list($form_state,'id');
             * uit_filter_render_list($form_state,'id', false, true);
             * uit_filter_render_list($form_state,'id', true);
             * uit_filter_render_list($form_state,'id', true, true);
             * uit_filter_render_list($form_state, null, true));
             * uit_filter_render_list($form_state, null, false, true));
             * uit_filter_render_list($form_state, null, true, true));
             */
        }
    }
}
function uit_filter_set_filters($form_state) {
    $filters = uit_filters($form_state['uit_filter']);
    $values = $form_state['values'];
    foreach ($filters as $filter => $options) {
        if (isset($values[$filter]) && $values[$filter] != '') {
            if (isset($filters[$filter]['options'])) {
                $flat_options = form_options_flatten($filters[$filter]['options']);
                if (isset($flat_options[$values[$filter]])) {
                    $_SESSION[$_SESSION['session']][$filter] = $values[$filter];
                }
            }
            else {
                $_SESSION[$_SESSION['session']][$filter] = $values[$filter];
            }
        }
        else {
            unset($_SESSION[$_SESSION['session']][$filter]);
        }
    }
}
function uit_filter_is_value_session($name) {
    if(!isset($_SESSION['session'])) return;
    return !empty($_SESSION[$_SESSION['session']][$name]) ? $_SESSION[$_SESSION['session']][$name] : NULL;
}
function uit_filter_search_value(SelectQueryInterface &$q, $target) {
    foreach ($target as $v) {
        $value = uit_filter_is_value_session($v);
        if(empty($value)) continue;
        switch ($v) {
            case 'sodong':
                break;
            default:
                $q->condition($v, '%' . db_like($value) . '%', 'LIKE');
        }
    }
    return;
}
//debug
function uit_filter_debug_values_session($dpm = false, $any_state = array()){
    if($any_state){
        if($dpm) dpm($any_state, 'status');
        else{
            echo "<pre>";
            print_r ($any_state);
            echo "</pre>";
            die();
        }
    }
    if($dpm) dpm($_SESSION[$_SESSION['session']], 'session');
    else {
        echo "<pre>";
        print_r ($_SESSION[$_SESSION['session']]);
        echo "</pre>";
        die();
    }
}
//render
/**
 * @param $form_state: require
 * @param null $is_table_of_select: Dạng chuỗi, là tên 1 field cần xữ lý
 * @param false $select_all: Lấy tất cả dữ liệu bởi filter bằng phương thức select * form table.. LÀ TRUE
 * @param false $full_stack: Lấy tất cả dữ liệu bởi filter là TRUE
 * @return array
 */
function uit_filter_render_items($form_state, $is_table_of_select = null, $select_all = false, $full_stack = false){
    if(!isset($form_state['db_select']) || !is_string($form_state['db_select'])) return; //not available for query. Exit!

    $stt = 1;
    $items = $header = array();
    $header['stt']['data'] = 'STT';
    $values = $form_state['list'];
    for($i = 0; $i < count ($values['header']); $i++){
        $header[$i]['data'] = $values['header'][$i];
        $header[$i]['field'] = $values['field'][$i];
    }
    $query = db_select($form_state['db_select'], 'uit')
        ->extend('PagerDefault')
        ->extend('TableSort');
    uit_filter_search_value($query, $form_state['filter']['field']);
    $limit = uit_filter_is_value_session('sodong') ? uit_filter_is_value_session('sodong') : 25;

    $field = (isset($is_table_of_select) ? array_merge(array($is_table_of_select), $values['field']) : $values['field']);
    $header = (isset($is_table_of_select) ? array_merge(array(''), $header) : $header);

    if($select_all) $field = $header = array();
    $query->fields('uit', $field);

    if($full_stack) $limit = $query->countQuery()->execute()->fetchField();
    $query->limit($limit)->orderByHeader($header);
    $result = $query->execute();
    if(count($result) == 0) return $items;

    foreach ($result as $i => $row) {
        if($is_table_of_select){
            $id = $row->$is_table_of_select;
            //unset($row->$is_table_of_select);
            //$items[$i][] ="<input type='checkbox' name='' value=$id>";
            $items[$i]['id']['data'] = array(
                '#type' => 'checkbox',
                '#values' => $id,
            );
            $items[$i]['stt'] = $stt++;
        }
        else $items[$i]['stt'] = $stt++;
        foreach ($field as $v){
            $items[$i][] = $row->$v;
        }
    }
    $items['header'] = $header;
    return $items;
}
function uit_filter_render_list($form_state, $is_table_of_select = null, $select_all = false, $full_stack = false){
    $items =  uit_filter_render_items($form_state, $is_table_of_select, $select_all, $full_stack);
    $output = 'Không có dữ liệu';
    global $pager_total_items;

    if(count($items) > 0 || $pager_total_items[0] == ''){
        $output = "Tổng danh sách: " . $pager_total_items[0];//unset($items['header']);
        $output .= theme('table', array('header' => $items['header'], 'rows' => $items, 'empty' =>'Không có dữ liệu'));
        $output .= theme('pager');
    }
    return $output;
}
function uit_filter_render_export_excel(){ return;}
function uit_filter_render_export_pdf(){ return;}
function uit_filter_render_charts(){ return;}
//end render
function uit_filter_detail_items(&$form, $items, $id_item = null){
    if(count($items) == 0) return;
    else {
        $header = $items['header'];
        $header[-1]['data'] = 'STT';
        for($i = 0; $i < count($items); $i++){
            $info = (isset($items[$i]) ? $items[$i] : '');
            for ($j = 0; $j < count($info); $j++){
                $info[$j] = (isset($info[$j]) ? $info[$j] : '');
                $form['detail_item'. $i . $j] = uit_filter_markup_info($header[$j-1]['data'], $info[$j]);
            }
        }
        $form['paper'] = array('#theme' => 'pager');
    }
    //if($id_item)//detail
}
function uit_filter_markup_info($title, $info){return array('#markup' => t('<strong>@title</strong> ' . '@info <br>', array('@title' => $title, '@info' => $info)));}
