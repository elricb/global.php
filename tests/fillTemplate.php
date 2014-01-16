<?php

include("../V.php");
$a = array(
        "hi"=>"iamhi",
        "var"=>array("subvar"=>"iamsub"),
        "loop"=> array(
            array("a"=>"woota1"),
            array("a"=>"woota2.1"),
            array("a"=>"woota3.2")
        ),
        "loop2"=> array(
            array("a"=>"woot1"),
            array("a"=>"woot2.1"),
            array("a"=>"woot3.2")
        )
    );

echo( V::fillTemplate(
    "<p>this is a test {{hi}} of the {{var.subvar}} template [[loop2: <div>bye loop {{a}}?[v1]</div>]] {{joe|nojoe}} [[loop: <div>hi loop {{a}}?[v2]</div>]] system</p>",
    $a
) );


?>