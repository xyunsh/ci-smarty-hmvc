<?php

function smarty_modifier_requestIs($match, $cls) {
    return request_is($match) ? $cls : '';
}

?>
