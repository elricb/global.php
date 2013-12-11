<?php
/*
 *
 * Stitcher (thanks for the name tyler)
 * Last Update:  Aug 14 2012 (elricb)
 * 
 * Runs in two versions:
 * FirstRun:  includes and minifies on first run
 *     compares $target date to latest last update in js/css files
 * Compiler:  create minified resource files offline 
 *
 * To Use First Run
 *     $ri = new Stitcher(["filename"]); //filename without extension (if not included, builds css and js based on php page name
 *     $ri->addJs("file1.js");
 *     $ri->addJs("/folder/file2.js"); //works based on folder root, not url root
 *     $ri->addCss("http://site.com/folder/file2.css");
 *     $ri->includeJs(["target"]); //compiles file and echos include statement
 *     $ri->includeCss(); //compiles file and echos include statement
 *     
 * To Use Compiler
 *     //Create PHP wrapper and suppress output:
 *     $ri = new Stitcher(["filename"]); //filename without extension (if not included, builds css and js based on php page name
 *     $ri->addJs("file1.js");
 *     $ri->addJs("/folder/file2.js"); //works based on folder root, not url root
 *     $ri->addCss("http://site.com/folder/file2.css");
 *     $ri->compile(["target"]); //return true == success
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


/*
 * Stitcher(target[,targetCss]);
 */
class Stitcher
{//Compares compiled vs included file dates each time class is run.
    private $filesJs;
    private $filesCss;
    private $targetJs='';
    private $targetCss='';
    private $targetDateJs=FALSE;
    private $sourceDateJs=FALSE;
    private $targetDateCss=FALSE;
    private $sourceDateCss=FALSE;
    private $maxLength = 50000;
    private $errs;
    public  $force_write=false;
    public  $verbose=false;
    public  $debug=false;


    function Stitcher($target='')
    {//assign properties to defaults
        $this->errs = array();
        $this->maxLength = (function_exists("memory_get_peak_usage"))?memory_get_peak_usage()/5:$this->maxLength; //Only since PHP5
        $this->filesJs = array();
        $this->filesCss = array();
        $this->setTargetJs($target,".js");
        $this->setTargetCss($target,".css");
    }
    function __construct($target='',$targetCss=''){$this->Stitcher($target,$targetCss);} //PHP5 constructor

    public function setTargetCss($target)
    {//accepts full path or relative location
        if(gettype($target)!='string' || !$target) return false;
        $this->targetCss = $this->setFile($target,".css");
        $this->targetDateCss = $this->fileLastMod( $this->targetCss ); //if no date, assumes doesn't exist and tries to create, if increatible, spits raw js to page
        return true;
    }

    public function setTargetJs($target)
    {//accepts full path or relative location
        if(gettype($target)!='string' || !$target) return false;
        $this->targetJs = $this->setFile($target,".js");
        $this->targetDateJs = $this->fileLastMod( $this->targetJs ); //if no date, assumes doesn't exist and tries to create, if increatible, spits raw js to page
        return true;
    }

    function setFile($file,$ext='')
    {//blank will default to this filename (file calling this script), Extension will be set to $ext if not sent in file argument.
        $file = (!$file)?$this->pathinfoFilename(basename($_SERVER["SCRIPT_NAME"])):$file;
        $ext1 = pathinfo($file, PATHINFO_EXTENSION);
        if($ext1===NULL || !$ext1)
            return $file.$ext;
        return $file;
    }

    function fileLastMod($file)
    {
        if(file_exists($file)) //will fail on URL locations - expected behaviour
            return filemtime($file);  //returns FALSE on errors
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
        echo "//targetJs: ".$this->targetJs . " ({$this->date_to_string($this->fileLastMod( $this->targetJs ))})\n<br />";
        echo "//targetCss: ".$this->targetCss . " ({$this->date_to_string($this->fileLastMod( $this->targetCss ))})\n<br />";
        foreach($this->filesJs as $value)
        {
            echo "    //Js include: ".$value . " ({$this->date_to_string($this->fileLastMod( $value ))})\n<br />";
        }
    }

    public function addJs($file='')
    {
        if(gettype($file)!='string' || !$file) return false;
        $this->filesJs[] = $file;
        if(($temp = $this->fileLastMod($file)) > $this->targetDateJs) //integer date values: NULL > date (don't save), NULL > NULL (don't save), date > NULL|date (save)
            $this->sourceDateJs = $temp;
    }

    public function addCss($file='')
    {
        if(!$file || gettype($file)!='string') return false;
        $this->filesCss[] = $file;
        if(($temp = $this->fileLastMod($file)) > $this->sourceDateCss) //integer date values: NULL > date (don't save), NULL > NULL (don't save), date > NULL|date (save)
            $this->sourceDateCss = $temp;
    }
    
    public function includeJs($target='')
    {
        if($target)
            $this->setTargetJs($target,".js");

        $compiled = $this->compileJs();
        if($compiled)
        {
            echo("<script type=\"text/JavaScript\" src=\"".$this->targetJs."\"></script>\n");
            return true;
        }
        echo("<!-- failed to include \"".$this->targetJs."\" (error here) --!>\n");
        return false;
    }

    public function includeCss($target='')
    {
        if($target)
            $this->setTargetCss($target,".css");

        $compiled = $this->compileCss();
        if($compiled)
        {
            echo("<link rel=\"stylesheet\" type=\"text/css\" href=\"".$this->targetCss."\" />\n");
            return true;
        }
        echo("<!-- failed to include \"".$this->targetCss."\" (error here) --!>\n");
        return false;
    }

    function getJsContents()
    {
        $contents = "";
        if($this->force_write || ($this->targetDateJs === FALSE || $this->targetDateJs < $this->sourceDateJs) )
        {//target doesn't exist or it's not up-to-date
            if($this->verbose) 
            {//easier to debug with source name
                foreach($this->filesJs as $value){
                    $contents .= "\n/*".basename($value)." */\n";
                    $contents .= $this->minifyJS(file_get_contents($value,false,NULL,-1,$this->maxLength)); //will err if file is over max, but at 50meg+, a js file should fail
                }
            }
            else 
            {//faster to dump all resource contents into one file
                foreach($this->filesJs as $value){
                    $contents .= file_get_contents($value,false,NULL,-1,$this->maxLength); //will err if file is over max, but at 50meg+, a js file should fail
                }
                $contents = $this->minifyJS($contents);
            }
        }
        return $contents;
    }

    function getCssContents()
    {
        $contents = "";
        if($this->force_write || $this->targetDateCss === FALSE || $this->targetDateCss < $this->sourceDateCss)
        {//target file doesn't exist or it's not up to date
            foreach($this->filesCss as $value)
                $contents .= file_get_contents($value,false,NULL,-1,$this->maxLength); 
            $contents = $this->minifyCSS($contents);
        }
        return $contents;
    }

    public function compileJs($target='')
    {
        if($target) 
            $this->setTargetJs($target,".js");

        $contents = $this->getJsContents();
        if($contents) 
            $temp = file_put_contents ($this->targetJs, ($contents));

        $return = true;
        if(!$contents || $temp===FALSE || !$temp)
            $return = false;

        if($this->verbose)
            if($return)
                echo("successfully compiled Js to '".$this->targetJs."' [".implode(",",$this->filesJs)."]\n");
            else if($contents)
                echo("failed to compile Js to '".$this->targetJs."'\n");
        else if(!$this->verbose && !$return && $contents)
            echo("<!-- failed to compile Js to '".$this->targetJs."' -->\n");

        return $return;
    }

    public function compileCss($target='')
    {
        if($target) 
            $this->setTargetCss($target,".css");

        $contents = $this->getCssContents();
        if($contents) 
            $temp = file_put_contents ($this->targetCss, ($contents));

        $return = true;
        if(!$contents || $temp===FALSE || !$temp)
            $return = false;

        if($this->verbose)
            if($return)
                echo("successfully compiled Css to '".$this->targetCss."' [".implode(",",$this->filesCss)."]\n");
            else if($contents)
                echo("failed to compile Css to '".$this->targetCss."'\n");
        else if(!$this->verbose && !$return && $contents)
            echo("<!-- failed to compile Css to '".$this->targetCss."' -->\n");

        return $return;
    }

    public function compile($target='')
    {
        if(!$this->compileJs($target))
            return false;
        if(!$this->compileCss($target))
            return false;
        return true;
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
    function minifyJS($js,$simple=true) 
    {
        if($simple)
            return(minifyWhitespace($js));
        else
            return JSMin::minify($js); //requires php5 +
    }
    
} //Stitcher




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