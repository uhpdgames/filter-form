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
function uit_filters($form_state) {
    $filters = array();
    $form = $form_state['filter'];
    $option_change = $form_state['option_filter'];
    $description = isset($form_state['description']) ? $form_state['description'] : array();

    for ($i = 0; $i < count($form['field']); $i++) {
        if(is_array($option_change[$i])) {
            $filters[$form['field'][$i]] = array(
                'type' => 'select',
                'title' => $form['header'][$i],
                'options' => $option_change[$i],
                'description' => (isset($description[$i]) ? $description[$i] : ''),
                'default_value' => uit_filter_is_value_session($form['field'][$i])
            );
        }
        if($option_change[$i] == 'textfied' || is_string($option_change[$i])) {
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
        'options' => (array('25' => t('25'),'50' => t('50'),'100'=>t('100'),'200'=>t('200'),'500'=>t('500'))),
        'default_value' => (uit_filter_is_value_session('sodong') ? uit_filter_is_value_session('sodong') : 25),
    );
    return $filters;
}
