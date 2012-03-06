#!/usr/bin/php
<?php

$classes = array();
$methods = array();
$classmap = array();


echo "Reading conf/server.srg...\n";
$lines = file("conf/server.srg");
foreach ($lines as $line) {
	$line = trim($line, "\r\n");
	// PK: . net/minecraft/src
	if (preg_match("/^PK: (.*) (.*)$/", $line, $matches)) {
		continue;
	}
	
	// CL: a net/minecraft/src/Packet7UseEntity
	if (preg_match("/^CL: (.*) (.*)$/", $line, $matches)) {
		//echo "Class: $matches[1] -> $matches[2]\n";
		$classes[] = array("notch"=>$matches[1], "mcp"=>$matches[2], "methods"=>array());
		continue;
	}
	
	// FD: a/a net/minecraft/src/Packet7UseEntity/field_9019_a
	if (preg_match("/^FD: (.*) (.*)$/", $line, $matches)) {
		//echo "Field: $matches[1] -> $matches[2]\n";
		continue;
	}
	
	// MD: a/a ()I net/minecraft/src/Packet7UseEntity/func_71_a ()I
	if (preg_match("/^MD: (.+) (.+) (.+) (.+)$/", $line, $matches)) {
		$method = array("notch"=>$matches[1], "method"=>$matches[3], "mcp"=>substr(strrchr($matches[3], "/"), 1));
		$methods[] =& $method;
		$class = substr($matches[1], 0, strrpos($matches[1], "/"));
		$found = 0;
		for ($x = 0; $x < count($classes); $x++) {
			if ($classes[$x]["notch"] == $class) {
				$classes[$x]["methods"][] =& $method;
				$found = 1;
				break;
			}
		}
		if ($found == 0) die("Could not find class $class when loading methods.\n");
		unset($method);
		continue;
	}
	
	die("Unrecognized line: $line\n");
}
unset($lines);
echo "Read ".count($classes)." classes and ".count($methods)." methods.\n\n";

echo "Loading class mappings...\n";
$lines = file("to_second_mappings");
foreach ($lines as $line) {
	$line = trim($line);
	list($mcp, $bukkit) = explode(" ", $line);
	$classmap["$mcp"] = $bukkit;
}
unset($lines);
echo "Done.\n";



// method mapping
echo "Loading conf/methods.csv...\n";
$lines = file("conf/methods.csv");
foreach ($lines as $line) {
	list($searge,$name,$side,$desc) = explode(",", $line, 4);
	if ($side != 1) continue;
	$found = 0;
	for ($x = 0; $x < count($methods); $x++) {
		$methodname = $methods[$x]["method"];
		$methodname = substr($methodname, strrpos($methodname, "/")+1);
		//echo "$methodname == $searge\n";
		if ($methodname == $searge) {
			$methods[$x]["mcp"] = $name;
			$found = 1;
		}
	}
	//if ($found == 0) echo("Could not find method $searge / $name\n");
}
unset($lines);
echo "Done.\n";



echo "Mapping methods...\n";
for ($c = 0; $c < count($classes); $c++) {
	$class =& $classes[$c];
	$class["bukkit"] = $classmap[substr(strrchr($class["mcp"], "/"), 1)];

	$mcpmethods = array();
	$level = 0;
	
	$mcpclass = substr(strrchr($class["mcp"], "/"), 1);
	$lines = file("mcp/$mcpclass.java");
	if (!$lines) die("Could not read MCP sourcefile for class $class[notch] / $class[mcp] / $class[bukkit].\n");
	
	for ($x = 0; $x < count($lines); $x++) {
		if ($level == 1) {
			// public int foo(int bar) {
			if (preg_match("/^\s*\w[\w\s\[\]<>]+ (\w+)\(/", $lines[$x], $matches)) {
				if ($matches[1] != $mcpclass)
					$mcpmethods[] = $matches[1];
			}
		}
		$level += substr_count($lines[$x], "{");
		$level -= substr_count($lines[$x], "}");
	}
	unset($lines);
	
	$bukkitmethods = array();
	$level = 0;
	$inbukkit = false;
	$lines = file("bukkit/$class[bukkit].java");
	if (!$lines) die("Could not read Bukkit sourcefile for class $class[notch] / $class[mcp] / $class[bukkit].\n");
	
	for ($x = 0; $x < count($lines); $x++) {
		if (stripos($lines[$x], "// CraftBukkit start") !== false) $inbukkit = true;
		//if ($inbukkit && $level == 1) { echo $lines[$x]; }
		if (stripos($lines[$x], "// CraftBukkit end") !== false) $inbukkit = false;

		if ($level == 1 && !$inbukkit) {
			// public int foo(int bar) {
			if (preg_match("/^\s*\w[\w\s\[\]<>]+ (\w+)\(/", $lines[$x], $matches)) {
				if ($matches[1] != $class["bukkit"])
					$bukkitmethods[] = $matches[1];
			}
		}
		$level += substr_count($lines[$x], "{");
		$level -= substr_count($lines[$x], "}");
	}
	unset($lines);
	
	for ($x = 0; $x < count($mcpmethods) && $x < count($bukkitmethods); $x++) {
		$found = 0;
		for ($y = 0; $y < count($class["methods"]); $y++) {
			//echo $class["methods"][$y]["mcp"]." == $mcpmethods[$x]\n";
			if ($class["methods"][$y]["mcp"] == $mcpmethods[$x]) {
				//echo "Mapped method $mcpclass.$mcpmethods[$x] -> $class[bukkit].$bukkitmethods[$x]\n";
				$class["methods"][$y]["bukkit"] = $bukkitmethods[$x];
				$found = 1;
			}
		}
		if ($found == 0) die("Could not find method $mcpmethods[$x] in class $class[mcp]\n".print_r($class, true));
	}
	
	//$classes[$c] = $class;
	unset($class);
}



echo "Writing to new_conf/methods.csv...\n";
@unlink("new_conf/methods.csv");
$lines = file("conf/methods.csv");
foreach ($lines as $line) {
	list($searge,$name,$side,$desc) = explode(",", $line, 4);
	if ($side != 1) {
		file_put_contents("new_conf/methods.csv", $line, FILE_APPEND);
		continue;
	}
	
	$bukkit = "";
	for ($x = 0; $x < count($methods); $x++) {
		$methodname = $methods[$x]["method"];
		$methodname = substr($methodname, strrpos($methodname, "/")+1);
		//echo "$methodname == $searge\n";
		if ($methodname == $searge) {
			if (!isset($methods[$x]["bukkit"])) {
				echo("Bukkit name not set for method:\n".print_r($methods[$x], true)."\n");
				$bukkit = $name;
			} else {
				$bukkit = $methods[$x]["bukkit"];
			}
			break;
		}
	}
	if ($bukkit == "") {
		// Some methods are in methods.csv which no longer exist in the code, so this is expected.
		//die("Could not find method $searge\n");
		$bukkit = $name;
	}
	
	file_put_contents("new_conf/methods.csv", "$searge,$bukkit,$side,$desc", FILE_APPEND);
}




echo "All done.\n";
?>
