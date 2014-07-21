<?php
/* -*- tab-width: 4; indent-tabs-mode: nil; c-basic-offset: 4 -*- */
/*
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Plume Framework, a simple PHP Application Framework.
# Copyright (C) 2001-2009 Loic d'Anterroches and contributors.
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
 * Backup of the Plume Framework.
 *
 * Backup all the data of the framework models. By default the backup
 * name will be the date YYYY-MM-DD.
 *
 * @param string Path to the folder where to store the backup
 * @param string Name of the backup (null)
 * @return int The backup was correctly written
 */
function Gatuf_Migrations_Backup_run($folder, $name=null)
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
    // Now, for each table, we dump the content in json, this is a
    // memory intensive operation
    $to_json = array();
    foreach ($models as $model) {
        $to_json[$model] = Gatuf_Test_Fixture::dump($model, false);
    }
    if (null == $name) {
        $name = date('Y-m-d');
    }
    return file_put_contents(sprintf('%s/%s-Gatuf.json', $folder, $name),
                             json_encode($to_json), LOCK_EX);
}

/**
 * Restore Pluf from a backup.
 *
 * @param string Path to the backup folder
 * @param string Backup name
 * @return bool Success
 */
function Gatuf_Migrations_Backup_restore($folder, $name)
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
    $full_data = json_decode(file_get_contents(sprintf('%s/%s-Gatuf.json', $folder, $name)), true);
    foreach ($full_data as $model => $data) {
        Gatuf_Test_Fixture::load($data, false);
    }
    return true;
}
