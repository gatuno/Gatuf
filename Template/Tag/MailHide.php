<?php

class Gatuf_Template_Tag_MailHide extends Gatuf_Template_Tag {
	public function start($email) {
		$emailparts = Gatuf_Template_Tag_MailHide::getparts($email);
		$url = Gatuf_Template_Tag_MailHide::geturl($email);
	
		echo htmlentities($emailparts[0]) . "<a href='" . htmlentities($url) .
			"' onclick=\"window.open('" . htmlentities($url) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"Revelar esta dirección de correo electrónico\">...</a>@" . htmlentities($emailparts [1]);
	}
	
	public static function geturl($email) {
		$pubkey = Gatuf::config('mailhide_pubkey', '');
		$privkey = Gatuf::config('mailhide_privkey', '');
		
		if ($pubkey == '' || $privkey == '') {
			throw new Exception('MailHide no configurado');
		}
		
		$ky = pack('H*', $privkey);
		if (! function_exists("mcrypt_encrypt")) {
			throw new Exception("To use reCAPTCHA Mailhide, you need to have the mcrypt php module installed.");
		}
		
		$numpad = 16 - (strlen($email) % 16);
		$val  = str_pad($email, strlen($email) + $numpad, chr($numpad));
		$cryptmail = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $ky, $val, MCRYPT_MODE_CBC, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0");
		
		return "http://www.google.com/recaptcha/mailhide/d?k=" . $pubkey . "&c=" . strtr(base64_encode($cryptmail), '+/', '-_');
	}
	
	public static function getparts($email) {
		$arr = preg_split("/@/", $email);

		if (strlen($arr[0]) <= 4) {
			$arr[0] = substr($arr[0], 0, 1);
		} else {
			if (strlen($arr[0]) <= 6) {
				$arr[0] = substr($arr[0], 0, 3);
			} else {
				$arr[0] = substr($arr[0], 0, 4);
			}
		}
		return $arr;
	}
}
