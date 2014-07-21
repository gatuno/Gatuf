<?php

class Gatuf_Form_Widget_ColorPicker extends Gatuf_Form_Widget {
	public function render($name, $value, $extra_attrs = array()) {
		if ($value === null) {
			$value = '#FF0000';
		}
		
		$entero = hexdec (substr ($value, 1));
		$r = 0xFF & ($entero >> 16);
		$g = 0xFF & ($entero >> 8);
		$b = 0xFF & $entero;
		
		$final_attrs = Gatuf_Form_Widget_Attrs ($this->buildAttrs (array('name' => $name), $extra_attrs));
		
		$tmpl = new Gatuf_Template('gatuf/widget/color-picker-map.html', array (dirname(__FILE__).'/../../templates'));
		
		$context = new Gatuf_Template_Context (array ('name' => $name, 'color' => $value, 'r' => $r, 'g' => $g, 'b' => $b, 'final_attrs' => $final_attrs));
		
		return new Gatuf_Template_SafeString($tmpl->render ($context), true);
	}
}	
