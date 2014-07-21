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
            $this->checkMinMax($value);
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
        return (int) $value;
    }

    protected function checkMinMax($value) {
        if ($this->max !== null and $value > $this->max) {
            throw new Gatuf_Form_Invalid(sprintf('Asegure que el valor no es más grande que %1$d.', $this->max));
        }
        if ($this->min !== null and $value < $this->min) {
            throw new Gatuf_Form_Invalid(sprintf('Asegure que el valor no es menor que %1$d.', $this->min));
        }
    }
}

