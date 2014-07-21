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

class Gatuf_Form_Field_Time extends Gatuf_Form_Field {
    public $widget = 'Gatuf_Form_Widget_TextInput';

    public function clean($value) {
		parent::clean($value);
		if (in_array($value, $this->empty_values)) {
			return '';
		}
		/* Validaciones extras para evitar errores */
		if (false === ($split = strpos ($value, ':'))) {
			if (strlen ($value) != 4 and strlen ($value) != 3) {
				throw new Gatuf_Form_Invalid ('Las horas en formato Siiau deben ser de 3 o 4 dígitos');
			}
			$hora = (int) substr ($value, 0, -2);
			$minuto = (int) substr ($value, -2);
		} else {
			if (false === ($date = strptime($value, '%H:%M'))) {
			    throw new Gatuf_Form_Invalid ('La hora ingresada no es válida');
			}
		    $hora = (int) substr ($value, 0, $split);
		    $minuto = (int) substr ($value, $split + 1);
		}
		if ($hora < 0 || $hora > 23 || $minuto < 0 || $minuto > 59) {
			throw new Gatuf_Form_Invalid ('La hora ingresada no es válida');
		}
		
        return str_pad ($hora, 2, '0', STR_PAD_LEFT).':'.
               str_pad ($minuto, 2, '0', STR_PAD_LEFT);
    }
}
