#!/usr/bin/php
<?php

$packages = array();
$classes = array();
$fields = array();
$methods = array();

function bukkitclass($mcpclass) {
	global $classes;
	foreach ($classes as $class) {
		if ($class["mcp"] == $mcpclass)
			return $class["bukkit"];
	}
	die("Class '$mcpclass' not found.\n");
}

echo "Reading conf/server.srg...\n";
$lines = file("conf/server.srg");
foreach ($lines as $line) {
	$line = trim($line, "\r\n");
	// PK: . net/minecraft/src
	if (preg_match("/^PK: (.*) (.*)$/", $line, $matches)) {
		//echo "Package: $matches[1] -> $matches[2]\n";
		$packages[] = array("notch"=>$matches[1], "mcp"=>$matches[2]);
		continue;
	}
	
	// CL: a net/minecraft/src/Packet7UseEntity
	if (preg_match("/^CL: (.*) (.*)$/", $line, $matches)) {
		//echo "Class: $matches[1] -> $matches[2]\n";
		$classes[] = array("notch"=>$matches[1], "mcp"=>$matches[2]);
		continue;
	}
	
	// FD: a/a net/minecraft/src/Packet7UseEntity/field_9019_a
	if (preg_match("/^FD: (.*) (.*)$/", $line, $matches)) {
		//echo "Field: $matches[1] -> $matches[2]\n";
		$fields[] = array("notch"=>$matches[1], "mcp"=>$matches[2]);
		continue;
	}
	
	// MD: a/a ()I net/minecraft/src/Packet7UseEntity/func_71_a ()I
	if (preg_match("/^MD: (.+) (.+) (.+) (.+)$/", $line, $matches)) {
		//echo "Method: $matches[1]:$matches[2] -> $matches[3]:$matches[4]\n";
		$methods[] = array("notch"=>$matches[1], "notchsig"=>$matches[2], "mcp"=>$matches[3], "mcpsig"=>$matches[4]);
		continue;
	}
	
	die("Unrecognized line: $line\n");
}
unset($lines);
echo "Read ".count($classes)." classes, ".count($fields)." fields, and ".count($methods)." methods.\n\n";



// Class mapping.
echo "Mapping classes...\n";
$lines = file("to_second_mappings");
foreach ($lines as $line) {
	$line = trim($line);
	list($mcp, $bukkit) = explode(" ", $line);
	for ($x = 0; $x < count($classes); $x++) {
		$class = $classes[$x];
		//echo substr(strrchr($class["mcp"], "/"), 1)." == $mcp\n";
		if (substr(strrchr($class["mcp"], "/"), 1) == $mcp) {
			$class["bukkit"] = $bukkit;
			$classes[$x] = $class;
			break;
		}
	}
}
unset($lines);

for ($x = 0; $x < count($classes); $x++) {
	$class = $classes[$x];
	if (!isset($class["bukkit"])) {
		$shortname = substr(strrchr($class["mcp"], "/"), 1);
		echo "No class mapping defined for: $shortname\n";
		$line = readline("Bukkit class: ");
		readline_add_history($line);
		$class["bukkit"] = $line;
		file_put_contents("to_second_mappings", "$shortname $class[bukkit]\n", FILE_APPEND);
		$classes[$x] = $class;
	}
}
echo "Class mapping complete.\n\n";


echo "Writing to new_conf/server.srg...\n";
$lines = array();
$rep_mcp = array();
$rep_bukkit = array();
@unlink("new_conf/server.srg");
foreach ($packages as $package) {
	file_put_contents("new_conf/server.srg", "PK: $package[notch] $package[mcp]\n", FILE_APPEND);
}
foreach ($classes as $class) {
	$bukkit = substr($class["mcp"], 0, strrpos($class["mcp"], "/"))."/".$class["bukkit"];
	//echo "CL: $class[notch] $bukkit\n";
	file_put_contents("new_conf/server.srg", "CL: $class[notch] $bukkit\n", FILE_APPEND);
	$rep_mcp[] = $class["mcp"];
	$rep_bukkit[] = $bukkit;
}
foreach ($fields as $field) {
	$fieldbukkit = str_replace($rep_mcp, $rep_bukkit, $field["mcp"]);
	file_put_contents("new_conf/server.srg", "FD: $field[notch] $fieldbukkit\n", FILE_APPEND);
}
foreach ($methods as $method) {
	$sigbukkit = str_replace($rep_mcp, $rep_bukkit, $method["mcpsig"]);
	file_put_contents("new_conf/server.srg", "MD: $method[notch] $method[notchsig] $method[mcp] $sigbukkit\n", FILE_APPEND);
}



echo "All done.\n";
?>
