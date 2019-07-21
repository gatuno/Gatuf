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

class Gatuf_Form_Field_Integer extends Gatuf_Form_Field {
    public $widget = 'Gatuf_Form_Widget_TextInput';
    public $max = null;
    public $min = null;

    public function clean($value) {
        parent::clean($value);
        $value = $this->setDefaultEmpty($value);
        if ($this->multiple) {
            return $this->multiClean($value);
        } else {
            if ($value == '') return $value;
            if (!preg_match('/^[\+\-]?[0-9]+$/', $value)) {
                throw new Gatuf_Form_Invalid('El valor debe ser un número.');
            }
            $value = (int) $value;
            $this->checkMinMax($value);
            if ($this->choices !== null && $this->choices_other == false) {
                $found = false;
                foreach ($this->choices as $val => $desc) {
                    if (is_array ($val)) {
                        foreach ($val as $subval => $desc2) {
                            if ($value == $subval) {
                                $found = true;
                                break;
                            }
                         }
                    } else {
                        if ($value == $val) {
                            $found = true;
                            break;
                        }
                    }
                }
                if (!$found) {
                    throw new Gatuf_Form_Invalid('Selección inválida');
                }
            }
        }
        return (int) $value;
    }
	public function widgetAttrs($widget) {
        $attrs = array ();
        if ($this->choices !== null and property_exists ($widget, 'want_choices') && $widget->want_choices == true) {
        	$widget->choices = $this->choices + $widget->choices;
        }
        
        if ($this->choices !== null and property_exists ($widget, 'can_other')) {
        	$widget->can_other = $this->choices_other;
        	$widget->other_text = $this->choices_other_text;
        }
        
        return $attrs;
    }
    
    protected function checkMinMax($value) {
        if ($this->max !== null and $value > $this->max) {
            throw new Gatuf_Form_Invalid(sprintf('El valor no puede ser mayor que %1$d.', $this->max));
        }
        if ($this->min !== null and $value < $this->min) {
            throw new Gatuf_Form_Invalid(sprintf('El valor no puede ser menor que %1$d.', $this->min));
        }
    }
}

