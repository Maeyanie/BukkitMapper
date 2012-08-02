#!/usr/bin/php
<?php
$lines = file("to_second_mappings");
foreach ($lines as $line) {
	list($mcp,$bukkit) = split(" ", $line, 2);
	$bukkit = trim($bukkit);
	$mappings[$bukkit][] = $mcp;
}

//print_r($mappings);

foreach ($mappings as $bukkit=>$mcp) {
	$count = count($mcp);
	if ($count > 1) {
		echo "Mapping $bukkit usage is $count:\n\t".implode($mcp, "\n\t")."\n\n";
	}
}
?>
