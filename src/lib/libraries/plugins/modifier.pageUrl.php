<?php

function smarty_modifier_pageUrl($page_result, $page = 1) {
    $format = is_array($page_result) ? $page_result['link_format'] : $page_result->link_format;
    return string_format($format, $page);
}

?>
