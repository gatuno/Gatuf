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

class Gatuf_Form_Field_Date extends Gatuf_Form_Field {
	public $widget = 'Gatuf_Form_Widget_TextInput';
	public $input_formats = array(
		'y-m-d', 'Y-m-d', 'd/m/y', 'd/m/Y', // 06-10-25, 2006-10-25, 25/10/06, 25/10/2006
		'M d Y', 'M d, Y',      // 'Oct 25 2006', 'Oct 25, 2006'
		'd M Y', 'd M, Y',      // '25 Oct 2006', '25 Oct, 2006'
		'F d Y', 'F d, Y',      // 'October 25 2006', 'October 25, 2006'
		'd F Y', 'd F, Y',      // '25 October 2006', '25 October, 2006'
	);

	public function clean($value) {
		parent::clean($value);
		if (in_array($value, $this->empty_values)) {
			return '';
		}
		
		foreach ($this->input_formats as $format) {
			$date = date_create_from_format($format, $value);
			if (false !== $date && $date->format($format) == $value) {
				return $date->format('Y-m-d');
			}
		}
		throw new Gatuf_Form_Invalid(__('Enter a valid date.'));
	}
}
