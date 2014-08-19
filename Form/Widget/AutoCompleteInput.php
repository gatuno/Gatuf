<?php
/* -*- tab-width: 4; indent-tabs-mode: nil; c-basic-offset: 4 -*- */
/*
/**
 * Simple checkbox with grouping.
 */
class Gatuf_Form_Widget_AutoCompleteInput extends Gatuf_Form_Widget {
    public $url_json = '';
    public $input_type = 'text';
    
    public function __construct($attrs=array()) {
        $this->url_json = $attrs['json'];
        $this->min_length = $attrs['min_length'];
        unset($attrs['json']);
        unset($attrs['min_length']);
        parent::__construct($attrs);
    }

    /**
     * Renders the HTML of the input.
     *
     * @param string Name of the field.
     * @param mixed Value for the field, can be a non valid value.
     * @param array Extra attributes to add to the input form (array())
     * @param array Extra choices (array())
     * @return string The HTML string of the input.
     */
    public function render($name, $value, $extra_attrs=array(), 
                           $choices=array()) {
        if ($value === null) $value = '';
        $final_attrs = $this->buildAttrs(array('name' => $name, 
                                               'type' => $this->input_type,
                                               'autocomplete' => 'off'),
                                         $extra_attrs);
        if ($value !== '') {
            $value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
            $final_attrs['value'] = $value;
        }
        
        $javascript_vars[] = '<script type="text/javascript">';
		$javascript_vars[] = '// <![CDATA[';
		
		$javascript_vars[] = sprintf ('$("#id_%s").autocomplete({source: "%s", minLength: %s});', $name, $this->url_json, $this->min_length);
		$javascript_vars[] = '// ]]>';
		$javascript_vars[] = '</script>';
        return new Gatuf_Template_SafeString('<input'.Gatuf_Form_Widget_Attrs($final_attrs).' />'."\n".implode ("\n", $javascript_vars), true);
    }
}
