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
 * Render a template file.
 */
class Gatuf_Template {
	public $tpl = '';
	public $folders = array();
	public $cache = '';
	public $compiled_template = '';
	public $template_content = '';
	public $context = null;
	public $class = '';
	/**
	 * Constructor.
	 *
	 * If the folder name is not provided, it will default to
	 * Pluf::f('template_folders')
	 * If the cache folder name is not provided, it will default to
	 * Pluf::f('tmp_folder')
	 *
	 * @param string Template name.
	 * @param string Template folder paths (null)
	 * @param string Cache folder name (null)
	 */
	public function __construct($template, $folders=null, $cache=null) {
		$this->tpl = $template;
		if (null == $folders) {
			$this->folders = Gatuf::config('template_folders');
		} else {
			$this->folders = $folders;
		}
		if (null == $cache) {
			$this->cache = Gatuf::config('tmp_folder');
		} else {
			$this->cache = $cache;
		}
		
		$this->compiled_template = $this->getCompiledTemplateName();
		$b = $this->compiled_template[1];
		$this->class = 'Gatuf_Template_'.$b;
		$this->compiled_template = $this->compiled_template[0];
		if (!class_exists($this->class, false)) {
			if (!file_exists($this->compiled_template) or Gatuf::config('debug')) {
				$compiler = new Gatuf_Template_Compiler($this->tpl, $this->folders);
				$this->template_content = $compiler->getCompiledTemplate();
				$this->write($b);
			}
			include $this->compiled_template;
		}
	}

	/**
	 * Render the template with the given context and return the content.
	 *
	 * @param Object Context.
	 */
	public function render($c=null) {
		if (null == $c) {
			$c = new Gatuf_Template_Context();
		}
		$this->context = $c;
		ob_start();
		$t = $c;
		try {
			call_user_func(array($this->class, 'render'), $t);
			//include $this->compiled_template;
		} catch (Exception $e) {
			ob_clean();
			throw $e;
		}
		$a = ob_get_contents();
		ob_end_clean();
		return $a;
	}

	/**
	 * Get the full name of the compiled template.
	 *
	 * Ends with .phps to prevent execution from outside if the cache folder
	 * is not secured but to still have the syntax higlightings by the tools
	 * for debugging.
	 *
	 * @return string Full path to the compiled template
	 */
	public function getCompiledTemplateName() {
		// The compiled template not only depends on the file but also
		// on the possible folders in which it can be found.
		$_tmp = var_export($this->folders, true);
		return array($this->cache.'/Gatuf_Template-'.md5($_tmp.$this->tpl).'.phps',
			md5($_tmp.$this->tpl));
	}

	/**
	 * Write the compiled template in the cache folder.
	 * Throw an exception if it cannot write it.
	 *
	 * @return bool Success in writing
	 */
	public function write($name) {
		$this->template_content = '<?php class Gatuf_Template_'.$name.' {
public static function render($c) {$t = $c; ?>'.$this->template_content.'<?php } } ';
		// mode "a" to not truncate before getting the lock
		$fp = @fopen($this->compiled_template, 'a');
		if ($fp !== false) {
			// Exclusive lock on writing
			flock($fp, LOCK_EX);
			// We have the unique pointeur, we truncate
			ftruncate($fp, 0);
			// Go back to the start of the file like a +w
			rewind($fp);
			fwrite($fp, $this->template_content, strlen($this->template_content));
			// Lock released, read access is possible
			flock($fp, LOCK_UN);
			fclose($fp);
			@chmod($this->compiled_template, 0777);
			return true;
		} else {
			throw new Exception(sprintf('No puedo escribir la plantilla compilada: %s', $this->compiled_template));
		}
		return false;
	}

	public static function markSafe($string) {
		return new Gatuf_Template_SafeString($string, true);
	}
}

/**
 * Set a string to be safe for display.
 *
 * @param string String to be safe for display.
 * @return string Pluf_Template_SafeString 
 */
function Gatuf_Template_unsafe($string) {
	return new Gatuf_Template_SafeString($string, true);
}

/**
 * Special htmlspecialchars that can handle the objects.
 *
 * @param string String proceeded by htmlspecialchars
 * @return string String like if htmlspecialchars was not applied
 */
function Gatuf_Template_htmlspecialchars($string) {
	return htmlspecialchars((string)$string, ENT_COMPAT, 'UTF-8');
}

/**
 * Modifier plugin: Convert the date from GMT to local and format it.
 *
 * This is used as all the datetime are stored in GMT in the database.
 *
 * @param string $date input date string considered GMT
 * @param string $format strftime format for output ('%b %e, %Y')
 * @return string date in localtime
 */
function Gatuf_Template_dateFormat($date, $format='%b %e, %Y') {
	if (substr(PHP_OS, 0, 3) == 'WIN') {
		$_win_from = array('%e',  '%T',	   '%D');
		$_win_to   = array('%#d', '%H:%M:%S', '%m/%d/%y');
		$format	= str_replace($_win_from, $_win_to, $format);
	}
	$date = date('Y-m-d H:i:s', strtotime($date.' GMT'));
	return strftime($format, strtotime($date));
}

/**
 * Modifier plugin: Convert format a date.
 *
 * This is used for dates not IN GMT.
 *
 * @param string $date input date string
 * @param string $format strftime format for output ('%b %e, %Y')
 * @return string date
 */
function Gatuf_Template_dateSimpleFormat($date, $format='%b %e, %Y') {
	if (substr(PHP_OS, 0, 3) == 'WIN') {
		$_win_from = array('%e',  '%T',	   '%D');
		$_win_to   = array('%#d', '%H:%M:%S', '%m/%d/%y');
		$format	= str_replace($_win_from, $_win_to, $format);
	}
	$date = date('Y-m-d H:i:s', strtotime($date));
	return strftime($format, strtotime($date));
}


/**
 * Modifier plugin: Format a unix time.
 *
 * Warning: date format is directly to be used, not consideration of
 * GMT or local time.
 *
 * @param int $time  input date string considered GMT
 * @param string $format strftime format for output ('Y-m-d H:i:s')
 * @return string formated time
 */
function Gatuf_Template_timeFormat($time, $format='H:i:s') {
	if ($time instanceof DateTime) {
		return $time->format($format);
	}
	return date($format, $time);
}


/**
 * Special echo function that checks if the string to output is safe
 * or not, if not it is escaped.
 *
 * @param mixed Input
 * @return string Safe to display in HTML.
 */
function Gatuf_Template_safeEcho($mixed, $echo=true) {
	if ($echo) {
		echo (!is_object($mixed) or 'Gatuf_Template_SafeString' != get_class($mixed)) ?
			htmlspecialchars($mixed, ENT_COMPAT, 'UTF-8') :
			$mixed->value;
	} else {
		return (!is_object($mixed) or 'Gatuf_Template_SafeString' != get_class($mixed)) ?
			htmlspecialchars($mixed, ENT_COMPAT, 'UTF-8') :
			$mixed->value;
	}
}

/**
 * New line to <br /> returning a safe string.
 *
 * @param mixed Input
 * @return string Safe to display in HTML.
 */
function Gatuf_Template_nl2br($mixed) {
	if (!is_object($mixed) or 'Gatuf_Template_SafeString' !== get_class($mixed)) {
		return Gatuf_Template::markSafe(nl2br(htmlspecialchars((string) $mixed, ENT_COMPAT, 'UTF-8')));
	} else {
		return Gatuf_Template::markSafe(nl2br($mixed->value));
	}
}

/**
 * Var export returning a safe string.
 *
 * @param mixed Input
 * @return string Safe to display in HTML.
 */
function Gatuf_Template_varExport($mixed) {
	return Gatuf_Template_unsafe('<pre>'.Gatuf_esc(var_export($mixed, true)).'</pre>');
}


/**
 * Display the date in a "6 days, 23 hours ago" style.
 */
function Gatuf_Template_dateAgo($date, $f='withal') {
	Gatuf::loadFunction('Gatuf_Date_Easy');
	if (is_null($date)) {
		return 'nunca';
	}
	$date = Gatuf_Template_dateFormat($date, '%Y-%m-%d %H:%M:%S');
	if ($f == 'withal') {
		return Gatuf_Date_Easy($date, null, 2, 'ahora');
	} else {
		return Gatuf_Date_Easy($date, null, 2, 'ahora', false);
	}
}

/**
 * Display the time in a "6 days, 23 hours ago" style.
 */
function Gatuf_Template_timeAgo($date, $f="withal") {
	Gatuf::loadFunction('Gatuf_Date_Easy');
	$date = Gatuf_Template_timeFormat($date);
	if ($f == 'withal') {
		return Gatuf_Date_Easy($date, null, 2, 'ahora');
	} else {
		return Gatuf_Date_Easy($date, null, 2, 'ahora', false);
	}
}

/**
 * Hex encode an email excluding the "mailto:".
 */
function Gatuf_Template_safeEmail($email) {
	$email = chunk_split(bin2hex($email), 2, '%');
	$email = '%'.substr($email, 0, strlen($email) - 1);
	return Gatuf_Template::markSafe($email);
}

/**
 * Returns the first item in the given array.
 *
 * @param array $array
 * @return mixed An empty string if $array is not an array.
 */
function Gatuf_Template_first($array) {
	$array = (array) $array;
	$result = array_shift($array);
	if (null === $result) {
		return '';
	}

	return $result;
}

/**
 * Returns the last item in the given array.
 *
 * @param array $array
 * @return mixed An empty string if $array is not an array.
 */
function Gatuf_Template_last($array) {
	$array = (array) $array;
	$result = array_pop($array);
	if (null === $result) {
		return '';
	}

	return $result;
}

function Gatuf_Template_json($data, $options = 0) {
	return Gatuf_Template::markSafe(json_encode($data, $options));
}
