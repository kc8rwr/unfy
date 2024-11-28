<?php

function rs($message){
    ob_start();
    print_r($message);
    $output = ob_get_contents();
    ob_end_clean();
    return $output;
}

function ht($message){
    $output = rs($message);
    $output = htmlentities($output);
    $output = UStr::replace(" ", '&nbsp;', $output);
    $output = UStr::replace("\t", '&nbsp;&nbsp;&nbsp;', $output);
    $output = nl2br($output);
    return $output;
}

function hd($message){
    echo(ht($message));
}

function hdd($message){
    hd($message);
    die;
}

?>
