<?php
/*
 *
 * Stitcher (thanks for the name tyler)
 * Last Update:  Aug 14 2012 (elricb)
 * 
 * Runs in three versions:
 * Live:  includes the js files and spits out the raw text (live has no performance benefit).
 * FirstRun:  includes and minifies on first run
 *     compares $target date to latest last update in js/css files
 * Archived:  create minified file offline 
 *
 * To Use Live:
 *     $ri = new StitcherLive();
 *     $ri->includeJs("file1.js");
 *     $ri->includeJs("/folder/file2.js"); //works based on folder root, not url root
 *     $ri->includeCss("http://site.com/folder/file2.css");
 *     $ri->displayJs();
 *     $ri->displayCss();
 *
 * To Use First Run
 *     $ri = new Stitcher(<"filename">); //filename without extension (if not included, builds css and js based on php page name
 *     $ri->includeJs("file1.js");
 *     $ri->includeJs("/folder/file2.js"); //works based on folder root, not url root
 *     $ri->includeCss("http://site.com/folder/file2.css");
 *     $ri->displayJs(); //creates js file and includes
 *     $ri->displayCss(); //creates css file and includes
 *     
 * To Use Archived
 *     //Create PHP wrapper and suppress output:
 *     $ri = new Stitcher("filename.js","filename.css"); //filename without extension (if not included, builds css and js based on php page name
 *     $ri->includeJs("file1.js");
 *     $ri->includeJs("/folder/file2.js"); //works based on folder root, not url root
 *     $ri->includeCss("http://site.com/folder/file2.css");
 *     $ri->displayJs(); //return true == success
 *     $ri->displayCss(); //return true == success
 *
 * To Do:
 *     if size of js files is extreme, may need to implement caching
 *     RISKY to update a 'live' js file
 *         need to create swapable instances (del/rename).
 *     Give option to place comment between each js file for debugging purposes
 *         e.g. original file:  something.js build: aug 2, 2011 
 *     Give option to merge, but not compress, for debugging....
 *         task -v (verbose) -c # (compress 1=full 2=comment files [default] 3=uncompressed)
 *
 */
class StitcherLive
{
//The weight of the process is on the server vs client, but can serve the js pages faster.
//this is generally not a good speed trade-off
    public $contentsJs = '';
    public $contentsCss = '';
    public $includerPath = '';
    
    function StitcherLive()
    {
        //possible path corrections here
    }
    function __construct($target=''){$this->StitcherLive();}
    
    public function includeJs($file='')
    {
        $str = "";
        if($file && gettype($file) === "string" && ($str = file_get_contents($file)) !== FALSE)
        {
            $this->contentsJs .= $str;
        }
    }
    public function includeCss($file='')
    {
        $str = "";
        if($file && gettype($file) === "string" && ($str = file_get_contents($file)) !== FALSE)
        {
            $this->contentsCss .= $str;
        }
    }
    public function displayJs()
    {
        echo("<script type=\"text/javascript\">//<![CDATA[\n");
        echo($this->contentsJs);
        echo("\n//]]></script>\n");
    }
    public function displayCss()
    {
        echo("<style type=\"text/css\">\n");
        echo($this->contentsCss);
        echo("\n</style>\n");
    }
}


/*
 * Stitcher(target,targetCss);
 */
class Stitcher
{//Compares compiled vs included file dates each time class is run.
    private $filesJs;
    private $filesCss;
    private $fileJs='';
    private $fileCss='';
    private $contentsJs='';
    private $contentsCss='';
    private $targetDateJs=FALSE;
    private $sourceDateJs=FALSE;
    private $targetDateCss=FALSE;
    private $sourceDateCss=FALSE;
    private $maxLength = 50000;

    function fileLastMod($file)
    {
        if(file_exists($file)) //will fail on URL locations - expected behaviour
            return filemtime($file);
        return FALSE;
    }
    
    function pathinfoFilename($path)
    {
        $file = pathinfo($path, 8); //only since PHP5.2
        if($file === NULL) //not using PHP5.2+
        {
            $file = pathinfo($path,PATHINFO_BASENAME);
            $pos = strrpos($file,".");
            if($pos!==-1)
                return(substr($file,0,$pos));
        }
        return $file;
    }
    
    function setTarget($file,$ext)
    {//blank will mimic this filename, no ext will assign extentions, otherwise use literals (no validation, assume passed values are correct)
        //$file = (gettype($file)==='string')?$file:'';
        $file = (!$file)?$this->pathinfoFilename(basename($_SERVER["SCRIPT_NAME"])):$file;
        $ext1 = pathinfo($file, PATHINFO_EXTENSION);
        if($ext1===NULL || !$ext1)
        {
            return $file . $ext;
        }
        else 
        {//to trust or not to trust the ext?
            return $file;
            if(strtolower($ext1) === $ext)
                return $file;
            else
                return substr($file,0,strrpos($file,".")).$ext;
        }
    }
    
    public function date_to_string($date1)
    {//this function is for testing purposes only
        if(function_exists("date_default_timezone_set")) //comply with php 5.2
            date_default_timezone_set("America/New_York");
        if($date1 === FALSE)
        {
            return "no date";
        }
        else
        {
            return date("F d Y H:i:s", $date1);
        }
    }
    
    function testOutput()
    {//this function is for testing purposes only
        echo "//targetJs: ".$this->fileJs . " ({$this->date_to_string($this->fileLastMod( $this->fileJs ))})\n<br />";
        echo "//targetCss: ".$this->fileCss . " ({$this->date_to_string($this->fileLastMod( $this->fileCss ))})\n<br />";
        foreach($this->filesJs as $value)
        {
            echo "    //Js include: ".$value . " ({$this->date_to_string($this->fileLastMod( $value ))})\n<br />";
        }
    }
    
    function Stitcher($target='',$targetCss='')
    {//assumes if $target has ext, it's JS.  Assumes if $targetCss exists, it's valid.
        $this->maxLength = (function_exists("memory_get_peak_usage"))?memory_get_peak_usage()/5:$this->maxLength; //Only since PHP5
        $this->filesJs = array();
        $this->filesCss = array();
        $this->fileJs = $this->setTarget($target,".js");
        $this->fileCss = ($targetCss)?$targetCss:$this->setTarget($target,".css");
        $this->targetDateJs = $this->fileLastMod( $this->fileJs ); //if no date, assumes doesn't exist and tries to create, if increatible, spits raw js to page
        $this->targetDateCss = $this->fileLastMod( $this->fileCss ); //ditto
    }
    function __construct($target='',$targetCss=''){$this->Stitcher($target,$targetCss);} //PHP5 constructor

    public function includeJs($file='')
    {
        if($file && gettype($file)=='string') $this->filesJs[] = $file;
        if(($temp = $this->fileLastMod($file)) > $this->targetDateJs) //integer date values: NULL > date (don't save), NULL > NULL (don't save), date > NULL|date (save)
            $this->sourceDateJs = $temp;
    }
    public function includeCss($file='')
    {
        if($file && gettype($file)=='string') $this->filesCss[] = $file;
        if(($temp = $this->fileLastMod($file)) > $this->sourceDateCss) //integer date values: NULL > date (don't save), NULL > NULL (don't save), date > NULL|date (save)
            $this->sourceDateCss = $temp;
    }
    
    public function displayJs()
    {
        echo $this->testOutput();
        
        $err = "";
        $temp = TRUE;
        if($this->targetDateJs === FALSE || $this->targetDateJs < $this->sourceDateJs)
        {//target file doesn't exist or it's not up to date
            foreach($this->filesJs as $value)
            {
                $this->contentsJs .= file_get_contents($value,false,NULL,-1,$this->maxLength); //be hard to trace err if file is over max, but if you have a 50meg+ js file you deserve to have it fail
            }
            $temp = FALSE;
            if(is_writable($this->fileJs)) // || chmod($this->fileJs, 0644))
            {
                if($this->contentsJs) 
                    $temp = file_put_contents ($this->fileJs, $this->minifyJS($this->contentsJs));
                if($temp===FALSE)
                    $err = "invalid content";
            }
            else
            {   
                $err = "no write access";
                if($this->contentsJs)
                {
                    echo("<script type=\"text/javascript\">//<![CDATA[\n");
                    echo($this->minifyJS($this->contentsJs));
                    echo("\n//]]></script>\n");
                }
            }
        }
        
        if($temp!==FALSE)
        {
            echo("<script type=\"text/JavaScript\" src=\"".$this->fileJs."\"></script>\n");
            return true;
        }
        else
        {
            echo("<!-- failed to include \"".$this->fileJs."\" ($err) --!>\n");
            return false;
        }
    }
    
    
    public function displayCss()
    {
        $err = "";
        $temp = TRUE;
        if($this->targetDateCss === FALSE || $this->targetDateCss < $this->sourceDateCss)
        {//target file doesn't exist or it's not up to date
            foreach($this->filesCss as $value)
            {
                $this->contentsCss .= file_get_contents($value,false,NULL,-1,$this->maxLength); //be hard to trace err if file is over max, but if you have a 50meg js file you deserve to have it fail
            }
            $temp = FALSE;
            if(is_writable($this->fileCss))
            {
                if($this->contentsCss)
                    $temp = file_put_contents ($this->fileCss, $this->minifyCSS($this->contentsCss));
                if($temp===FALSE)
                    $err = "invalid content";
            }
            else
            {
                $err = "no write access";
                if($this->contentsCss)
                {
                    echo("<style type=\"text/css\">\n");
                    echo($this->contentsCss);
                    echo("\n</style>\n");
                }
            }
        }
                
        if($temp!==FALSE)
        {
            echo("<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->fileCss."\" />\n");
            return true;
        }
        else
        {
            echo("<!-- failed to include \"".$this->fileCss."\" ($err) --!>\n");
            return false;
        }
    }

    /**
     * Minifies the specified CSS string and returns it.
     *
     * @param string $css CSS string
     * @return string minified string
     */
    function minifyCSS($css) 
    {
        // Compress whitespace.
        $css = preg_replace('/\s+/', ' ', $css);
    
        // Remove comments.
        $css = preg_replace('/\/\*.*?\*\//', '', $css);
    
        return trim($css);
    }
    
    /**
     * Minifies the specified JavaScript string and returns it.
     *
     * @param string $js JavaScript string
     * @return string minified string
     */
    function minifyJS($js,$simple=true) {
        if($simple)
        {
            return(minifyWhitespace($js));
        }
        else
        {
            return JSMin::minify($js); //requires php5 +
        }
    }
    
    
}




/*
 * http://razorsharpcode.blogspot.ca/2010/02/lightweight-javascript-and-css.html
 * Lightweight Whitespace Parser for JS and CSS
 * 
 */
function minifyWhitespace($_src) {
    // Buffer output
    ob_start();
    $_time=microtime(TRUE);
    $_ptr=0;
    $_ofs=0;
    while ($_ptr<=strlen($_src)) {
        if ($_src{$_ptr}=='/') {
            // Let's presume it's a regex pattern
            $_regex=TRUE;
            if ($_ptr>0) {
                // Backtrack and validate
                $_ofs=$_ptr;
                while ($_ofs>0) {
                    $_ofs--;
                    // Regex pattern should be preceded by parenthesis, colon or assignment operator
                    if ($_src{$_ofs}=='(' || $_src{$_ofs}==':' || $_src{$_ofs}=='=') {
                        while ($_ptr<=strlen($_src)) {
                            $_str=strstr(substr($_src,$_ptr+1),'/',TRUE);
                            if (!strlen($_str) && $_src{$_ptr-1}!='/' || strpos($_str,"\n")) {
                                // Not a regex pattern
                                $_regex=FALSE;
                                break;
                            }
                            echo '/'.$_str;
                            $_ptr+=strlen($_str)+1;
                            // Continue pattern matching if / is preceded by a \
                            if ($_src{$_ptr-1}!='\\' || $_src{$_ptr-2}=='\\') {
                                echo '/';
                                $_ptr++;
                                break;
                            }
                        }
                        break;
                    }
                    elseif ($_src{$_ofs}!="\t" && $_src{$_ofs}!=' ') {
                        // Not a regex pattern
                        $_regex=FALSE;
                        break;
                    }
                }
                if ($_regex && _ofs<1)
                    $_regex=FALSE;
            }
            if (!$_regex || $_ptr<1) {
                if (substr($_src,$_ptr+1,2)=='*@') {
                    // JS conditional block statement
                    $_str=strstr(substr($_src,$_ptr+3),'@*/',TRUE);
                    echo '/*@'.$_str.$_src{$_ptr}.'@*/';
                    $_ptr+=strlen($_str)+6;
                }
                elseif ($_src{$_ptr+1}=='*') {
                    // Multiline comment
                    $_str=strstr(substr($_src,$_ptr+2),'*/',TRUE);
                    $_ptr+=strlen($_str)+4;
                }
                elseif ($_src{$_ptr+1}=='/') {
                    // Multiline comment
                    $_str=strstr(substr($_src,$_ptr+2),"\n",TRUE);
                    $_ptr+=strlen($_str)+2;
                }
                else {
                    // Division operator
                    echo $_src{$_ptr};
                    $_ptr++;
                }
            }
            continue;
        }
        elseif ($_src{$_ptr}=='\'' || $_src{$_ptr}=='"') {
            $_match=$_src{$_ptr};
            // String literal
            while ($_ptr<=strlen($_src)) {
                $_str=strstr(substr($_src,$_ptr+1),$_src{$_ptr},TRUE);
                echo $_match.$_str;
                $_ptr+=strlen($_str)+1;
                if ($_src{$_ptr-1}!='\\' || $_src{$_ptr-2}=='\\') {
                    echo $_match;
                    $_ptr++;
                    break;
                }
            }
            continue;
        }
        if ($_src{$_ptr}!="\r" && $_src{$_ptr}!="\n" && ($_src{$_ptr}!="\t" && $_src{$_ptr}!=' ' ||
                preg_match('/[\w\$]/',$_src{$_ptr-1}) && preg_match('/[\w\$]/',$_src{$_ptr+1})))
            // Ignore whitespaces
            echo str_replace("\t",' ',$_src{$_ptr});
        $_ptr++;
    }
    echo '/* Compressed in '.round(microtime(TRUE)-$_time,4).' secs */';
    $_out=ob_get_contents();
    ob_end_clean();
    return $_out;
}



/**
 * jsmin.php - PHP implementation of Douglas Crockford's JSMin.
 *
 * This is pretty much a direct port of jsmin.c to PHP with just a few
 * PHP-specific performance tweaks. Also, whereas jsmin.c reads from stdin and
 * outputs to stdout, this library accepts a string as input and returns another
 * string as output.
 *
 * PHP 5 or higher is required.
 *
 * Permission is hereby granted to use this version of the library under the
 * same terms as jsmin.c, which has the following license:
 *
 * --
 * Copyright (c) 2002 Douglas Crockford  (www.crockford.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is furnished to do
     * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * The Software shall be used for Good, not Evil.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * --
 *
 * @package JSMin
 * @author Ryan Grove <ryan@wonko.com>
 * @copyright 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
 * @copyright 2007 Ryan Grove <ryan@wonko.com> (PHP port)
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @version 1.0.0 (2007-05-04)
 * @link http://code.google.com/p/jsmin-php/
 */

define('ORD_LF', 10);
define('ORD_SPACE', 32);

class JSMin {

    var $a           = '';
    var $b           = '';
    var $input       = '';
    var $inputIndex  = 0;
    var $inputLength = 0;
    var $lookAhead   = null;
    var $output      = array();

    // -- Public Static Methods --------------------------------------------------

    function minify($js) {
        $jsmin = new JSMin($js);
        return $jsmin->jsminify();
    }

    // -- Public Instance Methods ------------------------------------------------

    function JSMin($input) {
        $this->input       = $input;
        $this->inputLength = strlen($input);
    }

    // -- Protected Instance Methods ---------------------------------------------

    function action($d) {
        switch($d) {
            case 1:
                $this->output[] = $this->a;

            case 2:
                $this->a = $this->b;

                if ($this->a === "'" || $this->a === '"') {
                    for (;;) {
                        $this->output[] = $this->a;
                        $this->a        = $this->get();

                        if ($this->a === $this->b) {
                            break;
                        }

                        if (ord($this->a) <= ORD_LF) {
                            die('Unterminated string literal.');
                        }

                        if ($this->a === '\\') {
                            $this->output[] = $this->a;
                            $this->a        = $this->get();
                        }
                    }
                }

            case 3:
                $this->b = $this->next();

                if ($this->b === '/' && (
                        $this->a === '(' || $this->a === ',' || $this->a === '=' ||
                        $this->a === ':' || $this->a === '[' || $this->a === '!' ||
                        $this->a === '&' || $this->a === '|' || $this->a === '?')) {

                    $this->output[] = $this->a;
                    $this->output[] = $this->b;

                    for (;;) {
                        $this->a = $this->get();

                        if ($this->a === '/') {
                            break;
                        }
                        elseif ($this->a === '\\') {
                            $this->output[] = $this->a;
                            $this->a        = $this->get();
                        }
                        elseif (ord($this->a) <= ORD_LF) {
                            die('Unterminated regular expression literal.');
                        }

                        $this->output[] = $this->a;
                    }

                    $this->b = $this->next();
                }
        }
    }

    function get() {
        $c = $this->lookAhead;
        $this->lookAhead = null;

        if ($c === null) {
            if ($this->inputIndex < $this->inputLength) {
                $c = $this->input[$this->inputIndex];
                $this->inputIndex += 1;
            }
            else {
                $c = null;
            }
        }

        if ($c === "\r") {
            return "\n";
        }

        if ($c === null || $c === "\n" || ord($c) >= ORD_SPACE) {
            return $c;
        }

        return ' ';
    }

    function isAlphaNum($c) {
        return ord($c) > 126 || $c === '\\' || preg_match('/^[\w\$]$/', $c) === 1;
    }

    function jsminify() {
        $this->a = "\n";
        $this->action(3);

        while ($this->a !== null) {
            switch ($this->a) {
                case ' ':
                    if ($this->isAlphaNum($this->b)) {
                        $this->action(1);
                    }
                    else {
                        $this->action(2);
                    }
                    break;

                case "\n":
                    switch ($this->b) {
                        case '{':
                        case '[':
                        case '(':
                        case '+':
                        case '-':
                            $this->action(1);
                            break;

                        case ' ':
                            $this->action(3);
                            break;

                        default:
                            if ($this->isAlphaNum($this->b)) {
                                $this->action(1);
                            }
                            else {
                                $this->action(2);
                            }
                    }
                    break;

                default:
                    switch ($this->b) {
                        case ' ':
                            if ($this->isAlphaNum($this->a)) {
                                $this->action(1);
                                break;
                            }

                            $this->action(3);
                            break;

                        case "\n":
                            switch ($this->a) {
                                case '}':
                                case ']':
                                case ')':
                                case '+':
                                case '-':
                                case '"':
                                case "'":
                                    $this->action(1);
                                    break;

                                default:
                                    if ($this->isAlphaNum($this->a)) {
                                        $this->action(1);
                                    }
                                    else {
                                        $this->action(3);
                                    }
                            }
                            break;

                        default:
                            $this->action(1);
                            break;
                    }
            }
        }

        return implode('', $this->output);
    }

    function next() {
        $c = $this->get();

        if ($c === '/') {
            switch($this->peek()) {
                case '/':
                    for (;;) {
                        $c = $this->get();

                        if (ord($c) <= ORD_LF) {
                            return $c;
                        }
                    }

                case '*':
                    $this->get();

                    for (;;) {
                        switch($this->get()) {
                            case '*':
                                if ($this->peek() === '/') {
                                    $this->get();
                                    return ' ';
                                }
                                break;

                            case null:
                                die('Unterminated comment.');
                        }
                    }

                default:
                    return $c;
            }
        }

        return $c;
    }

    function peek() {
        $this->lookAhead = $this->get();
        return $this->lookAhead;
    }
}


?>