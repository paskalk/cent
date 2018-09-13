<?php

/*
 * TO GOD BE ALL THE GLORY || 1:02 PM 16-Feb-16
 */

//Require all the classes that you will need || SPL means Standard PHP Library
spl_autoload_register(function($classes) {
    try {
        require_once 'class/' . $classes . '.php';
    } catch (Exception $e) {
        $this->functions->flog('ERROR', __METHOD__ . $e->getMessage() . "() | Error ");
    }
});
