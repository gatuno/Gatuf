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

class Gatuf_Template_Tag_MediaUrl extends Gatuf_Template_Tag {
    function start ($file='') {
        echo Gatuf_Template_Tag_MediaUrl::url ($file);
    }

    public static function url($file='') {
        if ($file !== '' && Gatuf::config ('last_update_file', false) &&
            false !== ($last_update = Gatuf::fileExists(Gatuf::config('last_update_file')))
            ) {
            $file = $file.'?'.substr(md5(filemtime($last_update)), 0, 5);
        }
        return Gatuf::config('url_media', Gatuf::config('app_base').'/media').$file;
    }
}

