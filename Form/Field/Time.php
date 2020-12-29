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

class Gatuf_Form_Field_Time extends Gatuf_Form_Field {
	public $widget = 'Gatuf_Form_Widget_TextInput';

	public function clean($value) {
		parent::clean($value);
		if (in_array($value, $this->empty_values)) {
			return '';
		}
		$match = array();
		if (preg_match("/^(2[0-3]|[01][0-9]|[0-9]):([0-5][0-9])(:[0-5][0-9])?\s*([ap]m)?$/i", $value, $match)) {
			/* Valido */
			$hora = $match[1];
			$minuto = $match[2];
			
			if (isset($match[4])) {
				if ($hora > 12) {
					throw new Gatuf_Form_Invalid(__('Enter a valid time.'));
				}
				if ($hora != 12 && $match[4] == 'pm') {
					$hora += 12;
				}
			}
			$seg = 0;
			if (isset($match[3])) {
				$seg = substr($match[3], 1);
			}
		} else {
			throw new Gatuf_Form_Invalid(__('Enter a valid time.'));
		}
		$full = sprintf('%02s:%02s:%02s', $hora, $minuto, $seg);
		return date_create_from_format('H:i:s', $full);
	}
}
