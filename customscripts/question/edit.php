<?php

// We need to hide question/edit.php page from non admin.
if(!is_siteadmin()){
    $url = '/question/bank/managecategories/category.php';
    include($CFG->customscripts.$url);
}
return;