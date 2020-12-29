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
 * Class storing the data that are then used in the template.
 *
 * This class automatically set the 'request' key with the current
 * request and use and add more keys based on the processors.
 */
class Gatuf_Template_Context_Request extends Gatuf_Template_Context {
	public function __construct($request, $vars=array()) {
		$vars = array_merge(array('request' => $request), $vars);
		foreach (Gatuf::config('template_context_processors', array()) as $proc) {
			Gatuf::loadFunction($proc);
			$vars = array_merge($proc($request), $vars);
		}
		$params = array('request' => $request,
			'context' => $vars);
		/**
		 * [signal]
		 *
		 * Pluf_Template_Context_Request::construct
		 *
		 * [sender]
		 *
		 * Pluf_Template_Context_Request
		 *
		 * [description]
		 *
		 * This signal allows an application to dynamically modify the
		 * context array.
		 *
		 * [parameters]
		 *
		 * array('request' => $request,
		 *       'context' => array());
		 *
		 */
		Gatuf_Signal::send(
			'Gatuf_Template_Context_Request::construct',
			'Gatuf_Template_Context_Request',
			$params
		);
		$this->_vars = new Gatuf_Template_ContextVars($params['context']);
	}
}
