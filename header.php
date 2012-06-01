<?php
$header = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-gb" lang="en-gb" >
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="robots" content="index, follow" />
		<meta name="keywords" content="" />
		<meta name="title" content="" />
		<meta name="description" content="" />
   
		<title>PHP Wrapper for CKAN API</title>
		<link href="/'.CATALOG_PATH.'/css/common.css" type="text/css" rel="stylesheet" />
   		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
		<script src="/'.CATALOG_PATH.'/js/jquery.tagcloud.js"></script>
		<script type="text/javascript" charset="utf-8">
		$(document).ready(function(){
			$.fn.tagcloud.defaults = {
				size: {start: 10, end: 30, unit: "px"},
				color: {start: "#000", end: "#ffcccc"}
			};
			$("#tagcloud a").tagcloud();
		})
		</script>
	</head>
	<body>
		<h1>PHP Wrapper for CKAN API</h1>';

echo $header;
?>