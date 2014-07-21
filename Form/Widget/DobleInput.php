<?php

class Gatuf_Form_Widget_DobleInput extends Gatuf_Form_Widget {
	public $choices = array();

	public function __construct($attrs=array()) {
		$this->choices = $attrs['choices'];
		unset($attrs['choices']);
		parent::__construct($attrs);
	}
	
	public function render($name, $value, $extra_attrs=array(), $choices=array()) {
		$select_grupo = array();
		
		if ($value === null) {
			$value = '';
		}
		
		$grupo_id = 'grupo_'.$extra_attrs['id'];
		$group_attrs = $this->buildAttrs (array('name' => 'group_'.$name, 'onchange' => 'change_'.$grupo_id.' ()'), array_merge ($extra_attrs, array ('id' => $grupo_id)));
		$final_attrs = $this->buildAttrs (array('name' => $name), $extra_attrs);
		
		$javascript_vars = array ();
		$javascript_vars[] = '<script type="text/javascript">';
		$javascript_vars[] = '// <![CDATA[';
		$javascript_vars[] = 'var '.$grupo_id.'_opciones = new Array ();';
		$select_grupo[] = '<select'.Gatuf_Form_Widget_Attrs($group_attrs).'>';
		$groups = $this->choices + $choices;
		
		/* Generar el segundo select */
		$otro_select = array ();
		$otro_select[] = '<select'.Gatuf_Form_Widget_Attrs($final_attrs).'>';
		
		$num_grupo = 0;
		foreach ($groups as $option_group => $c) {
			if (!is_array ($c) || count($c) == 0) {
				continue;
			}
			$num_opcion = 0;
			$javascript_vars[] = sprintf ('%s_opciones[%s] = new Array ();', $grupo_id, $num_grupo);
			
			$grupo_preseleccionado = false;
			foreach ($c as $option_label => $option_value) {
				if ($option_value == $value) {
					$grupo_preseleccionado = true;
				}
				$selected = ($option_value == $value) ? ' selected="selected"':'';
				$javascript_vars[] = sprintf ('%s_opciones[%s][%s] = new Array ("%s", "%s");', $grupo_id, $num_grupo, $num_opcion, htmlspecialchars ($option_label, ENT_COMPAT, 'UTF-8'), htmlspecialchars ($option_value, ENT_COMPAT, 'UTF-8'));
				$num_opcion++;
			}
			if ($grupo_preseleccionado || ($num_grupo == 0 && $value == '')) {
				foreach ($c as $option_label => $option_value) {
					$selected = ($option_value == $value) ? ' selected="selected"':'';
	                $otro_select[] = sprintf('<option value="%s"%s>%s</option>',
                                    htmlspecialchars($option_value, ENT_COMPAT, 'UTF-8'),
                                    $selected, 
                                    htmlspecialchars($option_label, ENT_COMPAT, 'UTF-8'));
				}
			}
			$selected = ($grupo_preseleccionado) ? ' selected="selected"':'';
			$select_grupo[] = sprintf ('<option value="%s"%s>%s</option>', $num_grupo, $selected, htmlspecialchars($option_group, ENT_COMPAT, 'UTF-8'));
			$num_grupo++;
		}
		$select_grupo[] = '</select>';
		$otro_select[] = '</select>';
		
		/* Generar el código javascript */
		$javascript_vars[] = 'function change_'.$grupo_id.' () {';
		$javascript_vars[] = sprintf ('var grupo = document.getElementById ("%s").selectedIndex;', $grupo_id);
		$javascript_vars[] = sprintf ('var select = document.getElementById ("%s");', $extra_attrs['id']);
		
		$javascript_vars[] = 'select.options.length=0;';
		$javascript_vars[] = sprintf ('for (var g=0; g < %s_opciones[grupo].length; g++) {', $grupo_id);
		$javascript_vars[] = sprintf ('select.options[g] = new Option(%s_opciones[grupo][g][0], %s_opciones[grupo][g][1], false, false);}}', $grupo_id, $grupo_id);
		$javascript_vars[] = '// ]]>';
		$javascript_vars[] = '</script>';
		
		return new Gatuf_Template_SafeString(implode("\n", $select_grupo)."\n".implode ("\n", $otro_select)."\n".implode ("\n", $javascript_vars), true);
	}
}	
