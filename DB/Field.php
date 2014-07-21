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

/**
 * Default database field.
 */
class Gatuf_DB_Field {
	/**
	 * The types are defined in the $mappings member variable of the
	 * schema class of your database engine, for example
	 * Pluf_DB_Schema_MySQL.
	 */
	public $type = '';

	/**
	 * The column name of the field.
	 */
	public $column = '';

	/**
	 * Current value of the field.
	 */
	public $value;

	/**
	 * All the extra parameters of the field.
	 */
	public $extra = array();

	/**
	 * The extra methods added to the model by the field.
	 */
	public $methods = array();

	/**
	 * Constructor.
	 *
	 * @param mixed Value ('')
	 * @param string Column name ('')
	 */
	function __construct($value='', $column='', $extra=array()) {
		$this->value = $value;
		$this->column = $column;
		if ($extra) {
			$this->extra = array_merge($this->extra, $extra);
		}
	}
}

