<?php

	require_once(dirname(__FILE__) . "/Downgen.php");

	$generator = new Downgen(dirname(__FILE__));
	$generator->cssUrl = "downgen.css";
	$generator->render();

?>