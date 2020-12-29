<?php
/*
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Plume Framework, a simple PHP Application Framework.
# Copyright (C) 2001-2007 Loic d'Anterroches and contributors.
#
# Plume Framework is free software; you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation; either version 2.1 of the License, or
# (at your option) any later version.
#
# Plume Framework is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# ***** END LICENSE BLOCK ***** */

/**
 * Simple checkbox with grouping.
 */
class Gatuf_Form_Widget_SelectInput extends Gatuf_Form_Widget {
	public $choices = array();
	public $can_other = false;
	public $other_text = '';
	public $want_choices = true;
	public function __construct($attrs=array()) {
		if (isset($attrs['choices'])) {
			$this->choices = $attrs['choices'];
			unset($attrs['choices']);
		}
		
		if (isset($attrs['choices_other'])) {
			$this->can_other = $attrs['choices_other'];
			$this->other_text = $attrs['choices_other_text'];
			unset($attrs['choices_other']);
			unset($attrs['choices_other_text']);
		}
		
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
	public function render(
		$name,
		$value,
		$extra_attrs=array(),
		$choices=array()
	) {
		$output = array();
		if ($value === null) {
			$value = '';
		}
		$final_attrs = $this->buildAttrs(array('name' => $name), $extra_attrs);
		if ($this->can_other) {
			$other_id = 'other_'.$extra_attrs['id'];
			$final_attrs['onchange'] = 'change_'.$other_id.' ()';
		}
		$output[] = '<select'.Gatuf_Form_Widget_Attrs($final_attrs).'>';
		$groups = $this->choices + $choices;
		$found = false;
		foreach ($groups as $option_group => $c) {
			if (!is_array($c)) {
				$subchoices = array($option_group => $c);
			} else {
				$output[] = '<optgroup label="'.htmlspecialchars($option_group, ENT_COMPAT, 'UTF-8').'">';
				$subchoices = $c;
			}
			foreach ($subchoices as $option_label=>$option_value) {
				if ($option_value == $value) {
					$selected = ' selected="selected"';
					$found = true;
				} else {
					$selected = '';
				}
				$output[] = sprintf(
					'<option value="%s"%s>%s</option>',
					htmlspecialchars($option_value, ENT_COMPAT, 'UTF-8'),
					$selected,
					htmlspecialchars($option_label, ENT_COMPAT, 'UTF-8')
				);
			}
			if (is_array($c)) {
				$output[] = '</optgroup>';
			}
		}
		if ($this->can_other) {
			/* Agregar una opción que diga otros */
			$selected = ($found) ? '':' selected="selected"';
			
			$output[] = sprintf('<option value="___OTHER___"%s>%s</option>', $selected, htmlspecialchars($this->other_text, ENT_COMPAT, 'UTF-8'));
		}
		$output[] = '</select>';
		
		$javascript_vars = array();
		/* Agregar el javascript necesario para aparecer la caja de "Otros" */
		if ($this->can_other) {
			$other_attrs = $this->buildAttrs(array('name' => 'other_'.$name), array_merge($extra_attrs, array('id' => $other_id)));
			if (!$found) {
				$other_attrs['value'] = htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
			}
			
			$output[] = sprintf('<span id="div_%s"><label for="%s">%s:</label>', $other_id, $other_id, $this->other_text);
			$output[] = '<input'.Gatuf_Form_Widget_Attrs($other_attrs).' /></span>';
			
			$javascript_vars[] = '<script type="text/javascript">';
			$javascript_vars[] = '// <![CDATA[';
			/* Generar el código javascript */
			$javascript_vars[] = 'function change_'.$other_id.' () {';
			$javascript_vars[] = sprintf('var select = document.getElementById ("%s");', $extra_attrs['id']);
			$javascript_vars[] = 'if (select.value == "___OTHER___") {';
			$javascript_vars[] = 'document.getElementById ("div_'.$other_id.'").style.display = "inline"';
			$javascript_vars[] = '} else {';
			$javascript_vars[] = 'document.getElementById ("div_'.$other_id.'").style.display = "none"';
			$javascript_vars[] = '} }';
			$javascript_vars[] = 'change_'.$other_id.' ();';
			$javascript_vars[] = '// ]]>';
			$javascript_vars[] = '</script>';
		}
		return new Gatuf_Template_SafeString(implode("\n", $output)."\n".implode("\n", $javascript_vars), true);
	}
	
	public function valueFromFormData($name, $data) {
		if (isset($data[$name])) {
			if ($this->can_other && $data[$name] == '___OTHER___') {
				/* Tomar la caja "Otros" */
				if (isset($data['other_'.$name])) {
					return $data['other_'.$name];
				}
				return null;
			}
			return $data[$name];
		}
		
		return null;
	}
}
