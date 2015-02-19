<?php

class Gatuf_DB {
	static function get($engine, $server, $database, $login, $password, 
	                    $prefix, $debug=false, $version='') {
		$engine = 'Gatuf_DB_'.$engine;
		$con = new $engine($login, $password, $server, $database, $prefix, $debug, $version);
		return $con;
	}
}

function Gatuf_DB_getConnection() {
	if (isset($GLOBALS['_GATUF_db']) && 
		(is_resource($GLOBALS['_GATUF_db']->con_id) or is_object($GLOBALS['_GATUF_db']->con_id))) {
		return $GLOBALS['_GATUF_db'];
	}
	$GLOBALS['_GATUF_db'] = Gatuf_DB::get(Gatuf::config('db_engine'),
	                                      Gatuf::config('db_server'),
	                                      Gatuf::config('db_database'),
	                                      Gatuf::config('db_login'),
	                                      Gatuf::config('db_password'),
	                                      Gatuf::config('db_table_prefix'),
	                                      Gatuf::config('debug'));
	return $GLOBALS['_GATUF_db'];
}

function Gatuf_DB_defaultTypecast() {
	return array(
	             'Gatuf_DB_Field_Boolean' =>
	                 array('Gatuf_DB_BooleanFromDb', 'Gatuf_DB_BooleanToDb'),
	             'Gatuf_DB_Field_Date' =>
	                 array('Gatuf_DB_IdentityFromDb', 'Gatuf_DB_DateToDb'),
	             'Gatuf_DB_Field_Datetime' =>
	                 array('Gatuf_DB_IdentityFromDb', 'Gatuf_DB_IdentityToDb'),
	             'Gatuf_DB_Field_Email' =>
	                 array('Gatuf_DB_IdentityFromDb', 'Gatuf_DB_IdentityToDb'),
	             'Gatuf_DB_Field_File' =>
	                 array('Gatuf_DB_IdentityFromDb', 'Gatuf_DB_IdentityToDb'),
	             'Gatuf_DB_Field_Float' =>
	                 array('Gatuf_DB_IdentityFromDb', 'Gatuf_DB_IdentityToDb'),
	             'Gatuf_DB_Field_Foreignkey' =>
	                 array('Gatuf_DB_IdentityFromDb', 'Gatuf_DB_IdentityToDb'),
	             'Gatuf_DB_Field_Integer' =>
	                 array('Gatuf_DB_IntegerFromDb', 'Gatuf_DB_IntegerToDb'),
	             'Gatuf_DB_Field_Password' =>
	                 array('Gatuf_DB_IdentityFromDb', 'Gatuf_DB_PasswordToDb'),
	             'Gatuf_DB_Field_Sequence' =>
	                 array('Gatuf_DB_IntegerFromDb', 'Gatuf_DB_IntegerToDb'),
	             'Gatuf_DB_Field_Text' =>
	                 array('Gatuf_DB_IdentityFromDb', 'Gatuf_DB_IdentityToDb'),
	             'Gatuf_DB_Field_Varchar' =>
	                 array('Gatuf_DB_IdentityFromDb', 'Gatuf_DB_IdentityToDb'),
	             'Gatuf_DB_Field_Serialized' =>
	                 array('Gatuf_DB_SerializedFromDb', 'Gatuf_DB_SerializedToDb'),
	             'Gatuf_DB_Field_Compressed' =>
	                 array('Gatuf_DB_CompressedFromDb', 'Gatuf_DB_CompressedToDb'),
	             'Gatuf_DB_Field_Char' =>
	                 array('Gatuf_DB_IdentityFromDB', 'Gatuf_DB_IdentityToDb'),
	             'Gatuf_DB_Field_Time' =>
	                 array('Gatuf_DB_IdentityFromDB', 'Gatuf_DB_IdentityToDb'),
	);
}

function Gatuf_DB_IdentityFromDb($val) {
	return $val;
}

/**
 * Identity function.
 *
 * @param mixed Value.
 * @param object Database handler.
 * @return string Ready to use for SQL.
 */
function Gatuf_DB_IdentityToDb($val, $db) {
	if (null === $val) {
		return 'NULL';
	}
	return $db->esc($val);
}

function Gatuf_DB_DateToDb ($val, $db) {
	if (false === strpos ($val, '/')) {
		return $db->esc ($val);
	}
	return $db->esc (implode('-', array_reverse(explode('/', $val))));
}

function Gatuf_DB_SerializedFromDb($val) {
	if ($val) {
		return unserialize($val);
	}
	return $val;
}

function Gatuf_DB_SerializedToDb($val, $db) {
	if (null === $val) {
		return 'NULL';
	}
	return $db->esc(serialize($val));
}

function Gatuf_DB_CompressedFromDb($val) {
	return ($val) ? gzinflate($val) : $val;
}

function Gatuf_DB_CompressedToDb($val, $db) {
	return (null === $val) ? 'NULL' : $db->esc(gzdeflate($val, 9));
}

function Gatuf_DB_BooleanFromDb($val) {
	if ($val) {
		return true;
	}
	return false;
}

function Gatuf_DB_BooleanToDb($val, $db) {
	if (null === $val) {
		return 'NULL';
	}
	if ($val) {
		return $db->esc('1');
	}
	return $db->esc('0');
}

function Gatuf_DB_IntegerFromDb($val) {
	return (null === $val) ? null : (int) $val;
}

function Gatuf_DB_IntegerToDb($val, $db) {
	return (null === $val) ? 'NULL' : (string)(int)$val;
}

function Gatuf_DB_PasswordToDb($val, $db) {
	$exp = explode(':', $val);
	if (in_array($exp[0], array('sha1', 'md5', 'crc32'))) {
		return $db->esc($val);
	}
	// We need to hash the value.
	$salt = Gatuf_Utils::getRandomString(5);
	return $db->esc('sha1:'.$salt.':'.sha1($salt.$val));
}

