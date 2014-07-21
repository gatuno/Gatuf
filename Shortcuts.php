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
 * Shortcut methods to perform some operations.
 *
 * Modelled on the django.shortcuts. And modelled for Gatuf from Pluf
 */

/**
 * Render a template file and an array as a reponse.
 *
 * If a none null request object is given, the context used will
 * automatically be a Pluf_Template_Context_Request context and thus
 * the context will be populated using the
 * 'template_context_processors' functions.
 *
 * @param string Template file name
 * @param array Associative array for the context
 * @param Pluf_HTTP_Request Request object (null)
 * @return Pluf_HTTP_Response The response with the rendered template
 */
function Gatuf_Shortcuts_RenderToResponse($tplfile, $params, $request=null)
{
    $tmpl = new Gatuf_Template($tplfile);
    if (is_null($request)) {
        $context = new Gatuf_Template_Context($params);
    } else {
        $context = new Gatuf_Template_Context_Request($request, $params);
    }
    return new Gatuf_HTTP_Response($tmpl->render($context));
}

