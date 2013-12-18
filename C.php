<?php


class C 
{
    function encode($s, $t="64")
    {
        switch($t) {
            case "64":
            case "mime64":
                return base64_encode($s);
            case "uu": //network safe printable characters
                return convert_uuencode($s);
            case "md5": 
                //return md5($s);
                return hash($t, $s);
            default:
                foreach (hash_algos() as $v) {
                    if($s === $v)
                        return hash($t, $s);
                }
        }
        return "";
    }
    
    function decode($s, $t="64")
    {
        switch($t) {
            case "64":
            case "mime64":
                return base64_decode($s);
            case "imap64":
                return imap_base64($s);
            case "uu": //network safe printable characters
                return convert_uudecode($s);
                
        }
        return "";
    }
}
