<?php
/**
 * General variable and conversion classes
 */
class V
{
    
    function __construct()
    {
    }
    
    /**
     * returns default value if variable isn't initialized
     * arg1 = the array to traverse or variable to validate
     * arg2 = the value to return if key doesn't exist
     * arg3+ = the keys to traverse down
     */
    static function get($v, $d=null)
    {
        if (! isset($v) || empty($v))
            return $d;
        if (! is_array($v))
            return $v;
        $arg_list = func_get_args();
        $a = array_slice($arg_list, 2);
        $val = self::array_value($v, $a);
        if ($val===null)
            return $d;
        return $val;
    }
    
    static function array_value($arr, $keys)
    {
        if (array_key_exists($keys[0],$arr))
            if (count($keys) > 1)
                return self::array_value($arr[$keys[0]], array_slice($keys, 1));
            else 
                return $arr[$keys[0]];
        return null;
    }
    
    /**
     * traverses array, returning a valid key value or arg2 on error
     * arg1 = the array to traverse
     * arg2 = the value to return if key doesn't exist
     * arg3+ = the keys to traverse down
     */
    private function _get_avalue()
    {
        $arg_list = func_get_args();
        $a = array_slice($arg_list, 2);
        $val = $this->_get_avalue_step($arg_list[0], $a);
        if ($val===null)
            return $arg_list[1];
        return $val;
    }
    private function _get_avalue_step($arr, $keys)
    {
        if (array_key_exists($keys[0],$arr))
            if (count($keys) > 1)
                return $this->_get_avalue_step($arr[$keys[0]], array_slice($keys, 1));
            else 
                return $arr[$keys[0]];
        return null;
    }
    
    public function popTemplate($template, $a1=array(), $a2=array())
    {
        $newTemplate = (string)$template;
        //$newTemplate = strtr($newTemplate, $a1);
        foreach($a1 as $k => $v){
            $newTemplate = str_replace("{{$k}}", V::toString($v), $newTemplate);
        }
        foreach($a2 as $k => $v){
            $newTemplate = str_replace("{{$k}}", V::toString($v), $newTemplate);
        }
        return $newTemplate;
    }
    
    /**
     * Uses '.' to denote assoc array dimensions and '|' to denote decisions
     */
    public function popTemplateComplex($template, $a1=array(), $a2=array(), $blanks=false)
    {
        if (! preg_match_all('/{([A-Za-z.0-9\|\-]+)}/', $template, $matches))
            return $template;
        
        $newTemplate = (string)$template;
        $matches = array_unique($matches[1]);
        
        foreach($matches as $k => $v){
            $t = explode("|",$v);
            $matches[$v] = $t[0];
            $t = isset($t[1]) ? $t[1] : (($blanks)?"":$v);
            $matches[$v] = explode(".",$matches[$v]);
            $v1 = self::array_value($a1,$matches[$v]);
            $v2 = self::array_value($a2,$matches[$v]);
            if ($v1) {
                $newTemplate = str_replace("{{$v}}", V::toString($v1), $newTemplate);
                continue;
            }
            if ($v2) {
                $newTemplate = str_replace("{{$v}}", V::toString($v2), $newTemplate);
                continue;
            }
            $newTemplate = str_replace("{{$v}}", $t, $newTemplate);
        }
        return $newTemplate;
    }
    
    public function toString($v)
    {
        if (is_array($v)) {
            return json_encode($v, defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES : 0);
        }
        return (string)$v;
    }
    
    public function get_last_error_message()
    {
        $temp = error_get_last();
        if ($temp === null)
            return "";
        return $temp["message"];
    }
    
    public function sftp_file($server, $port, $u="", $p="", $file="")
    {
        //ssh_exec "dev:ppp@10.10.142.121" "ls \"/var/cron/tabs/www\""
        //ssh dev:ppp@10.10.142.121 'cd /var/cron/tabs | cat www'
        //ssh dev:ppp@10.10.142.121:/var/cron/tabs/www
        //exec("");
        //return file_get_contents("ssh2.sftp://$u:$p@$server:{$port}{$file}");
    }
    
    public function sftp_file2($server, $port, $u="", $p="", $file="")
    {
        $return = array(
            "err"  => "",
            "data" => "",
        );
        $e = null;
        $max = 10000;
        
        $connection = ssh2_connect($server, $port);
        if ($connection === false) {
            $return['err'] = "ssh2_connect connection failure (" . self::get_last_error_message() .")";
            return $return;
        }
        
        if ($u) {
            if (ssh2_auth_password($connection, 'username', 'password') === false) {
                $return['err'] = "ssh2_auth_password login failure (" . self::get_last_error_message() .")";
                return $return;
            }
        }
        
        $sftp = ssh2_sftp($connection);
        
        $stream = fopen("ssh2.sftp://$sftp/$file", 'r');
        if (! $stream) {
            $return['err'] = "fopen failure (" . self::get_last_error_message() .")";
            return $return;
        }
        
        $return["data"] = fread($stream, $max);
        
        fclose($stream);
        
        return $return;
    }
}
