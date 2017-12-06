<?php
 function smarty_modifier_debug($data) {
 	if(ENVIRONMENT == 'development'){
 		$json_string = json_encode($data, JSON_PRETTY_PRINT);
 		return '<!--<![CDATA[
	'.$json_string.'
]]>-->';
 	}

 	return '';
 }
 ?>
