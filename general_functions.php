<?php
function strptime($date, $format) { 
    $masks = array( 
    '%d' => '(?P<d>[0-9]{2})', 
    '%m' => '(?P<m>[0-9]{2})', 
    '%Y' => '(?P<Y>[0-9]{4})', 
    '%H' => '(?P<H>[0-9]{2})', 
    '%M' => '(?P<M>[0-9]{2})', 
    '%S' => '(?P<S>[0-9]{2})', 
    // usw.. 
    ); 

    $rexep = "#".strtr(preg_quote($format), $masks)."#"; 
    if(!preg_match($rexep, $date, $out)) 
    return false; 

    $ret = array( 
    "tm_sec"  => (int) $out['S'], 
    "tm_min"  => (int) $out['M'], 
    "tm_hour" => (int) $out['H'], 
    "tm_mday" => (int) $out['d'], 
    "tm_mon"  => $out['m']?$out['m']-1:0, 
    "tm_year" => $out['Y'] > 1900 ? $out['Y'] - 1900 : 0, 
    ); 
    return $ret; 
} 
?>