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
 * Setup of the Plume Framework.
 *
 * It creates all the tables for the framework models.
 */

function Gatuf_Migrations_Install_setup($params=null)
{
    $models = array('Gatuf_DB_SchemaInfo',
                    'Gatuf_Session',
                    Gatuf::config('gatuf_custom_user','Gatuf_User'),
                    Gatuf::config('gatuf_custom_group','Gatuf_Group'),
                    'Gatuf_Message',
                    'Gatuf_Permission',
                    'Gatuf_GSetting',
                    );
    $db = Gatuf::db();
    $schema = new Gatuf_DB_Schema($db);
    foreach ($models as $model) {
        $schema->model = new $model();
        $schema->createTables();
    }
}

function Gatuf_Migrations_Install_teardown($params=null)
{
    $models = array('Gatuf_DB_SchemaInfo',
                    'Gatuf_Session',
                    Gatuf::config('gatuf_custom_user','Gatuf_User'),
                    Gatuf::config('gatuf_custom_group','Gatuf_Group'),
                    'Gatuf_Message',
                    'Gatuf_Permission',
                    'Gatuf_GSetting',
                    );
    $models = array_reverse ($models);
    $db = Gatuf::db();
    $schema = new Gatuf_DB_Schema($db);
    foreach ($models as $model) {
        $schema->model = new $model();
        $schema->dropTables();
    }
}
