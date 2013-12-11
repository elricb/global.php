<?php
/**
 * Include in ajax file headers
 */

//Options
$lpass = ""; //not secure
$only_local = false; //better

$KVP = array_merge($_POST, $_GET);
$rhttp = (isset($_SERVER['REMOTE_HOST']) && $_SERVER['REMOTE_HOST'])?$_SERVER['REMOTE_HOST']:"";
$lhttp = (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:"";
$rpass = (isset($_KVP["pass"]) && $_KVP["pass"])?$_KVP["pass"]:"";

if ($only_local && $rhttp !== $lhttp)
    exit;
if ($lpass && $rpass !== $lpass)
    exit;


class Ajax 
{
    public $var = '';
    
    public function arrayItem($a, $k, $d="")
    {
        if (array_key_exists($k,$a)){
            return $a[$k];
        }
        if ($d)
            return $d;
        return null;
    }
}
