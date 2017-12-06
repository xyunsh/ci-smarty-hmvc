<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     modifier.fileSize.php
 * Type:     modifier
 * Name:     capitalize
 * Purpose:  format file size
 * -------------------------------------------------------------
 */

function smarty_modifier_fileSize($size) {
 	$size = max(0, (int)$size);
	$units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	$power = $size > 0 ? floor(log($size, 1024)) : 0;
	return number_format($size / pow(1024, $power), 2, '.', ',') . $units[$power];
}

?>
