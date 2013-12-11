<?php 
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '1');

include "../Stitcher.php";

$ri2 = new Stitcher("js/alljs-min");
$ri2->force_write=true;
$ri2->verbose=true;
$ri2->addJs("http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js");
$ri2->addJs("js/scale.js");
$ri2->addJs("js/mobile.js");
$ri2->compile();

$ri2 = new Stitcher("js/alljs-min2");
$ri2->force_write=false;
$ri2->verbose=false;
$ri2->addJs("http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js");
$ri2->addJs("js/scale.js");
$ri2->addJs("js/mobile.js");
$ri2->compile();


$ri2->flush();
$ri2->addJs("http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js");
$ri2->addJs("js/scale.js");
$ri2->addJs("js/mobile.js");
$ri2->compile("js/alljs-min3");

?>