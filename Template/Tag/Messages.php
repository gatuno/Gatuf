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
 * Display the messages for the current user.
 */
class Gatuf_Template_Tag_Messages extends Gatuf_Template_Tag {
	public function start($user) {
		if (is_object($user) && !$user->isAnonymous() && get_class($user) == Gatuf::config('gatuf_custom_user', 'Gatuf_User')) {
			$messages = $user->getAndDeleteMessages();
			if (count($messages) > 0) {
				echo '<div class="user-messages">'."\n";
				foreach ($messages as $m) {
					switch ($m['type']) {
						case 1:
							$clase = "info";
							break;
						case 2:
							$clase = "advertencia";
							break;
						case 3:
							$clase = "error";
							break;
					}
					echo '<div class="'.$clase.'"><p>'.$m['message'].'</p></div>';
				}
				echo '</div>';
			}
		}
	}
}
