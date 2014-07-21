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

class Gatuf_Form_Field_Datetime extends Gatuf_Form_Field {
    public $widget = 'Gatuf_Form_Widget_DatetimeInput';
    public $input_formats = array(
             '%d-%m-%Y %H:%M:%S',     // '25-10-2006 14:30:59'
             '%d-%m-%Y %H:%M',        // '25-10-2006 14:30'
             '%d-%m-%Y',              // '25-10-2006'
             '%d/%m/%Y %H:%M:%S',     // '25/10/2006 14:30:59'
             '%d/%m/%Y %H:%M',        // '25/10/2006 14:30'
             '%d/%m/%Y',              // '25/10/2006'
             '%d/%m/%y %H:%M:%S',     // '25/10/06 14:30:59'
             '%d/%m/%y %H:%M',        // '25/10/06 14:30'
             '%d/%m/%y',              // '25/10/06'
                                  );

    public function clean($value) {
        parent::clean($value);
        if (in_array($value, $this->empty_values)) {
            return '';
        }
        foreach ($this->input_formats as $format) {
            if (false !== ($date = strptime($value, $format))) {
                $day   = $date['tm_mday'];
                $month = $date['tm_mon'] + 1;
                $year  = $date['tm_year'] + 1900;
                // PHP's strptime has various quirks, e.g. it doesn't check
                // gregorian dates for validity and it also allows '60' in
                // the seconds part
                if (checkdate($month, $day, $year) && $date['tm_sec'] < 60) {
                    $date = str_pad($year,  4, '0', STR_PAD_LEFT).'-'.
                            str_pad($month, 2, '0', STR_PAD_LEFT).'-'.
                            str_pad($day,   2, '0', STR_PAD_LEFT).' '.
                            str_pad($date['tm_hour'], 2, '0', STR_PAD_LEFT).':'.
                            str_pad($date['tm_min'],  2, '0', STR_PAD_LEFT).':'.
                            str_pad($date['tm_sec'],  2, '0', STR_PAD_LEFT);
                    
                    // we internally use GMT, so we convert it to a GMT date.
                    return gmdate('Y-m-d H:i:s', strtotime($date));
                }
            }
        }
        throw new Gatuf_Form_Invalid('Enter a valid date/time.');
    }
}
