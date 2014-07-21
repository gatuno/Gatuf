<?php

$user_model = Gatuf::config('gatuf_custom_user','Gatuf_User');
$group_model = Gatuf::config('gatuf_custom_group', 'Gatuf_Group');

return array(
	$user_model => array(
		'relate_to_many' => array($group_model, 'Gatuf_Permission'),
	),
	$group_model => array(
		'relate_to_many' => array('Gatuf_Permission'),
	),
	'Gatuf_Message' => array(
		'relate_to' => array($user_model),
	),
);
