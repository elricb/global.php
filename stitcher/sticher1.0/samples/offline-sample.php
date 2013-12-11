<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');

include "../Stitcher.php";
//$ri = new StitcherLive();
//$ri->includeJs("data/jq-elric.js");

$ri2 = new StitcherFirst("data/alljs6");
$ri2->includeJs("http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js");
$ri2->includeJs("js/test.js");
$ri2->includeJs("js/jq-elric.js");

$ri3 = new StitcherFirst("");
$ri3->includeJs("js/test.js");
$ri3->includeJs("js/jq-elric.js");

$ri4 = new StitcherFirst("js/alljs.js","js/all.css");
$ri4->includeJs("js/all.js");
$ri4->includeJs("js/jq-elric.js");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http‎://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
<meta http-equiv="Content-Language" content="en" />
<link rel="icon" type="image/ico" href="favicon.ico"></link>
<link rel="shortcut icon" type="image/ico" href="favicon.ico"></link>
<link rel="shortcut icon" type="image/gif" href="favicon.gif"></link>
<link rel="shortcut icon" href="favicon.ico"></link>
<title>Test the Resource Includer</title>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<?php 
$ri2->displayJs();
$ri2->displayCss();

$ri3->displayJs();
$ri3->displayCss();

$ri4->displayJs();
$ri4->displayCss();

//echo("\n");
//$ri->displayJs();
?>

</head>

<body>
test of test
</body>
</html>