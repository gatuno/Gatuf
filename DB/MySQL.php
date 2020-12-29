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
 * MySQL connection class
 */
class Gatuf_DB_MySQL {
	public $con_id;
	public $pfx = '';
	private $debug = false;
	/** The last query, set with debug(). Used when an error is returned. */
	public $lastquery = '';
	public $engine = 'MySQL';
	public $type_cast = array();
	public $dbname = '';
	
	public function __construct($user, $pwd, $server, $dbname, $pfx='', $debug=false) {
		Gatuf::loadFunction('Gatuf_DB_defaultTypecast');
		$this->type_cast = Gatuf_DB_defaultTypecast();
		$this->debug('* MYSQL CONNECT');
		$this->con_id = mysqli_connect($server, $user, $pwd, $dbname);
		$this->dbname = $dbname;
		$this->debug = $debug;
		$this->pfx = $pfx;
		if (!$this->con_id) {
			throw new Exception($this->getError());
		}
		$this->execute('SET NAMES \'utf8\'');
	}
	
	public function createDB($dbname) {
		// CREATE DATABASE `siiau_2008B` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
		$sql = sprintf('CREATE DATABASE %s DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci', $this->qn($dbname));
		$this->execute($sql);
		
		$this->dbname = $dbname;
	}
	
	public function database($dbname) {
		$this->dbname = $dbname;
		$db = mysqli_select_db($this->con_id, $dbname);
		$this->debug('* USE DATABASE '.$dbname);
		if (!$db) {
			throw new Exception($this->getError());
		}
		return true;
	}

	/**
	 * Get the version of the MySQL server.
	 *
	 * @return string Version string
	 */
	public function getServerInfo() {
		return mysqli_get_host_info($this->con_id);
	}

	/**
	 * Log the queries. Keep track of the last query and if in debug mode
	 * keep track of all the queries in 
	 * $GLOBALS['_PX_debug_data']['sql_queries']
	 *
	 * @param string Query to keep track
	 * @return bool true
	 */
	public function debug($query) {
		$this->lastquery = $query;
		if (!$this->debug) {
			return true;
		}
		if (!isset($GLOBALS['_GATUF_debug_data']['sql_queries'])) {
			$GLOBALS['_GATUF_debug_data']['sql_queries'] = array();
		}
		$GLOBALS['_GATUF_debug_data']['sql_queries'][] = $query;
		return true;
	}

	public function close() {
		if ($this->con_id) {
			mysqli_close($this->con_id);
			return true;
		} else {
			return false;
		}
	}

	public function select($query) {
		$this->debug($query);
		$ok = mysqli_real_query($this->con_id, $query);
		if ($ok) {
			$cur = mysqli_use_result($this->con_id);
			$res = array();
			while ($row = $cur->fetch_assoc()) {
				$res[] = $row;
			}
			mysqli_free_result($cur);
			return $res;
		} else {
			throw new Exception($this->getError());
		}
	}

	public function execute($query) {
		$this->debug($query);
		$cur = mysqli_real_query($this->con_id, $query);
		if (!$cur) {
			throw new Exception($this->getError());
		} else {
			return true;
		}
	}
	
	public function getAffectedRows() {
		return (int) mysqli_affected_rows($this->con_id);
	}

	public function getLastID() {
		$this->debug('* GET LAST ID');
		return (int) mysqli_insert_id($this->con_id);
	}

	/**
	 * Returns a string ready to be used in the exception.
	 *
	 * @return string Error string
	 */
	public function getError() {
		if ($this->con_id) {
			return $this->con_id->connect_errno.' - '
				.$this->con_id->connect_error.' - '.$this->lastquery;
		} else {
			return mysqli_connect_errno().' - '
				.mysqli_connect_error().' - '.$this->lastquery;
		}
	}

	public function esc($str) {
		return '\''.mysqli_real_escape_string($this->con_id, $str).'\'';
	}

	/**
	 * Quote the column name.
	 *
	 * @param string Name of the column
	 * @return string Escaped name
	 */
	public function qn($col) {
		return '`'.$col.'`';
	}

	/**
	 * Start a transaction.
	 */
	public function begin() {
		if (Gatuf::config('db_mysql_transaction', false)) {
			$this->execute('BEGIN');
		}
	}

	/**
	 * Commit a transaction.
	 */
	public function commit() {
		if (Gatuf::config('db_mysql_transaction', false)) {
			$this->execute('COMMIT');
		}
	}

	/**
	 * Rollback a transaction.
	 */
	public function rollback() {
		if (Gatuf::config('db_mysql_transaction', false)) {
			$this->execute('ROLLBACK');
		}
	}

	public function __toString() {
		return '<Gatuf_DB_MySQL('.$this->con_id.')>';
	}
}
