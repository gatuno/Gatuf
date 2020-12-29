<?php

class Gatuf_Form_Widget_DateJSInput extends Gatuf_Form_Widget_Input {
	public $input_type = 'text';
	public $format = 'd/m/Y';
	public $date_format = 'dd/mm/yy';
	
	public function render($name, $value, $extra_attrs=array()) {
		if (isset($this->attrs['js_attrs'])) {
			$js_attrs = $this->attrs['js_attrs'];
			unset($this->attrs['js_attrs']);
		} else {
			$js_attrs = array();
		}
		
		if ($value === null) {
			$value = '';
		}
		
		$final_attrs = $this->buildAttrs(
			array('name' => $name,
				'type' => $this->input_type),
			$extra_attrs
		);
		if ($value !== '') {
			$value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
			$final_attrs['value'] = $value;
		}
		
		$output = array();
		$output[] = '<input'.Gatuf_Form_Widget_Attrs($final_attrs).' />';
		
		$javascript_vars = array();
		$javascript_vars[] = '<script type="text/javascript">';
		$javascript_vars[] = '// <![CDATA[';
		//$javascript_vars[] = '$document.ready(function (){';
		$javascript_vars[] = "$('#".$extra_attrs['id']."').datepicker({";
		$javascript_vars[] = "dateFormat: '".$this->date_format."',";
		foreach ($js_attrs as $js_attr => $js_value) {
			$javascript_vars[] = $js_attr.": '".$js_value."',";
		}
		$javascript_vars[] = "separator: ' ',";
		$javascript_vars[] = '});';
		
		$javascript_vars[] = '// ]]>';
		$javascript_vars[] = '</script>';
		
		return new Gatuf_Template_SafeString(implode("\n", $output)."\n".implode("\n", $javascript_vars), true);
	}
}
