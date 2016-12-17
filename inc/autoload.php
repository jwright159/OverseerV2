<?php

spl_autoload_register(
    function ($class) {
        $classtree = explode('\\', $class);
        if ($classtree[0] == 'Overseer') {
            include_once __DIR__.'/classes/'.strtolower($classtree[1]).'.php';
        } else {
            include_once __DIR__.'/lib/'.$classtree[0].'/'.$classtree[1].'/'.$classtree[2].'.php';
        }
    }
);
