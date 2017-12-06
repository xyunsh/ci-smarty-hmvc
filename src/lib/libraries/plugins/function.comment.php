<?php
function smarty_function_comment($params,$smarty){
    return '<!-- Generated at '.date('Y-m-d H:i:s').'-->';
}

?>