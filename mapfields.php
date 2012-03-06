#!/usr/bin/php
<?php

$classes = array();
$fields = array();
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
		$classes[] = array("notch"=>$matches[1], "mcp"=>$matches[2], "fields"=>array());
		continue;
	}
	
	// FD: a/a net/minecraft/src/Packet7UseEntity/field_9019_a
	if (preg_match("/^FD: (.*) (.*)$/", $line, $matches)) {
		//echo "Field: $matches[1] -> $matches[2]\n";
		$field = array("notch"=>$matches[1], "field"=>$matches[2], "mcp"=>substr(strrchr($matches[2], "/"), 1));
		$fields[] =& $field;
		$class = substr($matches[1], 0, strrpos($matches[1], "/"));
		$found = 0;
		for ($x = 0; $x < count($classes); $x++) {
			if ($classes[$x]["notch"] == $class) {
				$classes[$x]["fields"][] =& $field;
				$found = 1;
				break;
			}
		}
		if ($found == 0) die("Could not find class $class when loading fields.\n");
		unset($field);
		continue;
	}
	
	// MD: a/a ()I net/minecraft/src/Packet7UseEntity/func_71_a ()I
	if (preg_match("/^MD: (.+) (.+) (.+) (.+)$/", $line, $matches)) {
		continue;
	}
	
	die("Unrecognized line: $line\n");
}
unset($lines);
echo "Read ".count($classes)." classes and ".count($fields)." fields.\n\n";

echo "Loading class mappings...\n";
$lines = file("to_second_mappings");
foreach ($lines as $line) {
	$line = trim($line);
	list($mcp, $bukkit) = explode(" ", $line);
	$classmap["$mcp"] = $bukkit;
}
unset($lines);
echo "Done.\n";



// Field mapping
echo "Loading conf/fields.csv...\n";
$lines = file("conf/fields.csv");
foreach ($lines as $line) {
	list($searge,$name,$side,$desc) = explode(",", $line, 4);
	if ($side != 1) continue;
	$found = 0;
	for ($x = 0; $x < count($fields); $x++) {
		$fieldname = $fields[$x]["field"];
		$fieldname = substr($fieldname, strrpos($fieldname, "/")+1);
		//echo "$fieldname == $searge\n";
		if ($fieldname == $searge) {
			$fields[$x]["mcp"] = $name;
			$found = 1;
			break;
		}
	}
	//if ($found == 0) echo("Could not find field $searge / $name\n");
}
echo "Done.\n";



echo "Mapping fields...\n";
for ($c = 0; $c < count($classes); $c++) {
	$class =& $classes[$c];
	$class["bukkit"] = $classmap[substr(strrchr($class["mcp"], "/"), 1)];

	$mcpfields = array();
	$level = 0;
	
	$lines = file("mcp/".substr(strrchr($class["mcp"], "/"), 1).".java");
	if (!$lines) die("Could not read MCP sourcefile for class $class[notch] / $class[mcp] / $class[bukkit].\n");
	
	for ($x = 0; $x < count($lines); $x++) {
		$level += substr_count($lines[$x], "{");
		$level -= substr_count($lines[$x], "}");
		if ($level == 1) {
			// public int foo;
			// public int bar = 1;
			if (preg_match("/^\s*[\w\s\[\]<>]+ (\w+);/", $lines[$x], $matches)) {
				$mcpfields[] = $matches[1];
			} else if (preg_match("/^\s*[\w\s\[\]<>]+ (\w+) =/", $lines[$x], $matches)) {
				$mcpfields[] = $matches[1];
			}
		}
	}
	
	$bukkitfields = array();
	$level = 0;
	$inbukkit = false;
	$lines = file("bukkit/$class[bukkit].java");
	if (!$lines) die("Could not read Bukkit sourcefile for class $class[notch] / $class[mcp] / $class[bukkit].\n");
	
	for ($x = 0; $x < count($lines); $x++) {
		$level += substr_count($lines[$x], "{");
		$level -= substr_count($lines[$x], "}");
		
		if (stripos($lines[$x], "// CraftBukkit start") !== false) $inbukkit = true;
		if (stripos($lines[$x], "// CraftBukkit end") !== false) $inbukkit = false;
		if ($inbukkit) continue;
		
		if ($level == 1) {
			// public int foo;
			// public int bar = 1;
			if (preg_match("/^\s*[\w\s\[\]<>]+ (\w+);/", $lines[$x], $matches)) {
				$bukkitfields[] = $matches[1];
			} else if (preg_match("/^\s*[\w\s\[\]<>]+ (\w+) =/", $lines[$x], $matches)) {
				$bukkitfields[] = $matches[1];
			}
		}
	}
	
	for ($x = 0; $x < count($mcpfields) && $x < count($bukkitfields); $x++) {
		$found = 0;
		for ($y = 0; $y < count($class["fields"]); $y++) {
			if ($class["fields"][$y]["mcp"] == $mcpfields[$x]) {
				//echo "Mapped field $mcpfields[$x] -> $bukkitfields[$x]\n";
				$class["fields"][$y]["bukkit"] = $bukkitfields[$x];
				$found = 1;
				break;
			}
		}
		if ($found == 0) die("Could not find field $mcpfields[$x] in class $class[mcp]\n");
	}
	
	//$classes[$c] = $class;
	unset($class);
}



echo "Writing to new_conf/fields.csv...\n";
@unlink("new_conf/fields.csv");
$lines = file("conf/fields.csv");
foreach ($lines as $line) {
	list($searge,$name,$side,$desc) = explode(",", $line, 4);
	if ($side != 1) {
		file_put_contents("new_conf/fields.csv", $line, FILE_APPEND);
		continue;
	}
	
	$bukkit = "";
	for ($x = 0; $x < count($fields); $x++) {
		$fieldname = $fields[$x]["field"];
		$fieldname = substr($fieldname, strrpos($fieldname, "/")+1);
		//echo "$fieldname == $searge\n";
		if ($fieldname == $searge) {
			if (!isset($fields[$x]["bukkit"]))
				die("Bukkit name not set for field:\n".print_r($fields[$x], true)."\n");
			$bukkit = $fields[$x]["bukkit"];
			break;
		}
	}
	if ($bukkit == "") {
		// Some fields are in fields.csv which no longer exist in the code, so this is expected.
		//die("Could not find field $searge\n");
		$bukkit = $name;
	}
	
	file_put_contents("new_conf/fields.csv", "$searge,$bukkit,$side,$desc", FILE_APPEND);
}




echo "All done.\n";
?>
