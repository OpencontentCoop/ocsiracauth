<?php

$tpl = eZTemplate::factory();

$Result = array();
$Result['content'] = $tpl->fetch('design:sirac/change_password.tpl');