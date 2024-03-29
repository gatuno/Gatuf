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

class Gatuf_HTTP_Response_NotFound extends Gatuf_HTTP_Response {
	public function __construct($request) {
		$content = '';
		try {
			$context = new Gatuf_Template_Context(array('query' => $request->query));
			$tmpl = new Gatuf_Template('404.html');
			$content = $tmpl->render($context);
			$mimetype = null;
		} catch (Exception $e) {
			$mimetype = 'text/plain';
			$content = 
				sprintf(__('The requested URL %s was not found on this server.'), Gatuf_esc($request->query)).
				"\n".
				__('Please check the URL and try again.')."\n\n".
				__('404 - Not Found');
		}
		parent::__construct($content, $mimetype);
		$this->status_code = 404;
	}
}
