<?php

include 'client_detection.class.php';

echo("<h2>client_detection_class.php tests</h2>");

 $client = new client_detection();
 echo "<p><h3>instantiated</h3>" . print_r($client->browser, true) . "</p>";
 
 echo "<p><h3>static</h3>";
 echo " mobile: " . ((client_detection::isMobile())?"true":"false") . "<br />";
 echo " tablet: " . ((client_detection::isTablet())?"true":"false") . "<br />";
 echo " device: " . ((client_detection::isDevice())?"true":"false") . "<br />";
 echo " getclient: " . print_r(client_detection::getClientInfo(), true) . "<br />";
 echo " js tablet: <pre> " . client_detection::javascript('tablet') . "</pre><br />"; 
 echo " js mobile: <pre> " . client_detection::javascript('mobile') . "</pre><br />"; 
 echo " js browser: <pre> " . client_detection::javascript('browser') . "</pre><br />"; 
 echo " js all: <pre> " . client_detection::javascript() . "</pre><br />"; 
 echo "</p>";
 