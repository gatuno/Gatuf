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

class Gatuf_Group extends Gatuf_Model {
	public $_model = 'Gatuf_Group';
	
	public function init() {
		$this->_a['table'] = 'groups';
		$this->_a['model'] = 'Gatuf_Group';
		$this->primary_key = 'id';
		
		$this->_a['cols'] = array(
			'id' =>
			array(
				'type' => 'Gatuf_DB_Field_Sequence',
				'blank' => true,
			),
			'name' =>
			array(
				'type' => 'Gatuf_DB_Field_Varchar',
				'blank' => false,
				'size' => 50,
			),
			'description' =>
			array(
				'type' => 'Gatuf_DB_Field_Varchar',
				'blank' => false,
				'size' => 250,
			),
			'permissions' =>
			array(
				'type' => 'Gatuf_DB_Field_Manytomany',
				'blank' => true,
				'model' => 'Gatuf_Permission',
			),
		);
	}
	
	public function __toString() {
		return $this->name;
	}
}
