<?php

class Gatuf_Form_Widget_DateSimple extends Gatuf_Form_Widget {
	public $want_choices = false;
	public $format = '/';
	public $allowed_formats = array('/', '-', 'long', 'dmy', 'ymd');
	
	public function __construct($attrs=array()) {
		if (isset($attrs['format'])) {
			$this->format = $attrs['format'];
			unset($attrs['format']);
		}
		parent::__construct($attrs);
		
		if (!in_array($this->format, $this->allowed_formats)) {
			throw new Exception('Invalid format '.$this->format.' for widget DateSimple');
		}
	}
	
	public function render($name, $value, $extra_attrs=array(), $choices=array()) {
		$format = 'Y-m-d';
		if ($value === null) {
			$value = '';
		}
		
		$dia = 0;
		$mes = 0;
		$anio = 0;
		
		if ($value != '') {
			$date = date_create_from_format($format, $value);
			if (false === $date || $date->format($format) != $value) {
				/* Una fecha mal formada o inválida */
				$splits = explode('-', $value, 3);
				$anio = (int) $splits[0];
				$mes = (int) $splits[1];
				$dia = (int) $splits[2];
			} else {
				$dia = (int) $date->format('d');
				$mes = (int) $date->format('m');
				$anio = (int) $date->format('Y');
			}
		}
		
		$dia_attrs = $this->buildAttrs(array('name' => 'dia_'.$name), array_merge($extra_attrs, array('id' => 'dia_'.$extra_attrs['id'])));
		
		$output = array();
		
		if ($this->format == 'dmy') {
			$output[] = __('D').': ';
		} else {
			if ($this->format == 'ymd') {
				$output[] = __('Y').': ';
			} else {
				if ($this->format == 'long') {
					$output[] = __('Day').': ';
				}
			}
		}
		
		if ($this->format == '-' || $this->format == 'ymd') {
			if ($anio != 0) {
				$local_attrs = array('id' => 'anio_'.$extra_attrs['id'], 'size' => 8, 'value' => $anio);
			} else {
				$local_attrs = array('id' => 'anio_'.$extra_attrs['id'], 'size' => 8);
			}
			$anio_attrs = $this->buildAttrs(array('name' => 'anio_'.$name), $local_attrs);
			$output[] = '<input'.Gatuf_Form_Widget_Attrs($anio_attrs).' />';
		} else {
			$output[] = '<select'.Gatuf_Form_Widget_Attrs($dia_attrs).'>';
			for ($g = 1; $g <= 31; $g++) {
				if ($dia == $g) {
					$output[] = sprintf('<option value="%s" selected="selected">%s</option>', $g, $g);
				} else {
					$output[] = sprintf('<option value="%s">%s</option>', $g, $g);
				}
			}
			$output[] = '</select>';
		}
		
		if ($this->format == 'dmy' || $this->format == 'ymd') {
			$output[] = __('M').': ';
		} else {
			if ($this->format == 'long') {
				$output[] = __('Month').': ';
			} else {
				if ($this->format == '-' || $this->format == '/') {
					$output[] = ' '.$this->format.' ';
				}
			}
		}
		
		$mes_attrs = $this->buildAttrs(array('name' => 'mes_'.$name), array_merge($extra_attrs, array('id' => 'mes_'.$extra_attrs['id'])));
		$output[] = '<select'.Gatuf_Form_Widget_Attrs($mes_attrs).'>';
		
		for ($g = 1; $g <= 12; $g++) {
			if ($this->format == 'long') {
				$visual = date('F', mktime(0, 0, 0, $g));
			} else {
				$visual = str_pad($g, 2, '0', STR_PAD_LEFT);
			}
			if ($mes == $g) {
				$output[] = sprintf('<option value="%s" selected="selected">%s</option>', $g, $visual);
			} else {
				$output[] = sprintf('<option value="%s">%s</option>', $g, $visual);
			}
		}
		$output[] = '</select>';
		
		if ($this->format == 'dmy') {
			$output[] = __('Y').': ';
		} else {
			if ($this->format == 'ymd') {
				$output[] = __('D').': ';
			} else {
				if ($this->format == 'long') {
					$output[] = __('Year').': ';
				} else {
					if ($this->format == '-' || $this->format == '/') {
						$output[] = ' '.$this->format.' ';
					}
				}
			}
		}
		
		if ($this->format == '-' || $this->format == 'ymd') {
			$output[] = '<select'.Gatuf_Form_Widget_Attrs($dia_attrs).'>';
			for ($g = 1; $g <= 31; $g++) {
				if ($dia == $g) {
					$output[] = sprintf('<option value="%s" selected="selected">%s</option>', $g, $g);
				} else {
					$output[] = sprintf('<option value="%s">%s</option>', $g, $g);
				}
			}
			$output[] = '</select>';
		} else {
			if ($anio != 0) {
				$local_attrs = array('id' => 'anio_'.$extra_attrs['id'], 'size' => 8, 'value' => $anio);
			} else {
				$local_attrs = array('id' => 'anio_'.$extra_attrs['id'], 'size' => 8);
			}
			$anio_attrs = $this->buildAttrs(array('name' => 'anio_'.$name), $local_attrs);
			$output[] = '<input'.Gatuf_Form_Widget_Attrs($anio_attrs).' />';
		}
		
		return new Gatuf_Template_SafeString(implode("\n", $output), true);
	}
	
	public function idForLabel($id) {
		if ($this->format == 'ymd' || $this->format == '-') {
			return 'anio_'.$id;
		}
		return 'dia_'.$id;
	}
	
	public function valueFromFormData($name, $data) {
		if (isset($data['dia_'.$name]) && isset($data['mes_'.$name]) && isset($data['anio_'.$name])) {
			return $data['anio_'.$name].'-'.str_pad($data['mes_'.$name], 2, '0', STR_PAD_LEFT).'-'.str_pad($data['dia_'.$name], 2, '0', STR_PAD_LEFT);
		}
		return null;
	}
}
