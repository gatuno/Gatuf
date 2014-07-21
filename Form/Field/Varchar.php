<?php
/* -*- tab-width: 4; indent-tabs-mode: nil; c-basic-offset: 4 -*- */
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

class Gatuf_Form_Field_Varchar extends Gatuf_Form_Field
{
    public $widget = 'Gatuf_Form_Widget_TextInput';
    public $max_length = null;
    public $min_length = null;

    public function clean($value) {
        parent::clean($value);
        if (in_array($value, $this->empty_values)) {
            if ($this->multiple) {
                return array ();
            } else {
                $value = '';
            }
        }
        if ($this->multiple) {
            return $this->multiClean($value);
        } else {
            $value_length = mb_strlen($value);
            if ($this->max_length !== null and $value_length > $this->max_length) {
                throw new Gatuf_Form_Invalid(sprintf('Ensure this value has at most %1$d characters (it has %2$d).', $this->max_length, $value_length));
            }
            if ($this->min_length !== null and $value_length < $this->min_length) {
                throw new Gatuf_Form_Invalid(sprintf('Ensure this value has at least %1$d characters (it has %2$d).', $this->min_length, $value_length));
            }
            if (isset ($this->widget->choices)) {
                $found = false;
                foreach ($this->widget->choices as $val) {
                    if (is_array ($val)) {
                        foreach ($val as $subval) {
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
        return $value;
    }

    public function widgetAttrs($widget)
    {
        if ($this->max_length !== null and 
            in_array(get_class($widget), 
                     array('Gatuf_Form_Widget_TextInput', 
                           'Gatuf_Form_Widget_PasswordInput'))) {
            return array('maxlength'=>$this->max_length);
        }
        return array();
    }
}

