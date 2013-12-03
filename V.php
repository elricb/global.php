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
    
    public function popTemplateComplex($template, $a1=array(), $a2=array())
    {
        if (! preg_match_all('/{(\w+)}/g', $template, $matches))
            return $template;
        
        $newTemplate = (string)$template;
        $matches[1] = array_unique($matches[1]);
        
        foreach($matches[1] as $k => $v){
            $matches[1][$v] = explode(".",$v);
            $v1 = self::array_value($a1,$matches[1][$v]);
            $v2 = self::array_value($a2,$matches[1][$v]);
            if ($v1)
                $newTemplate = str_replace("{{$k}}", V::toString($v1), $newTemplate);
            if ($v2)
                $newTemplate = str_replace("{{$k}}", V::toString($v2), $newTemplate);
        }
        return $newTemplate;
    }
    
    public function toString($v)
    {
        if (is_array($v)) {
            return json_encode($v);
        }
        return (string)$v;
    }
}
