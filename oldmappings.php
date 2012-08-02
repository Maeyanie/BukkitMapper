#!/usr/bin/php
<?php
$lines = file("to_second_mappings");
foreach ($lines as $line) {
	list($mcp,$bukkit) = split(" ", $line, 2);
	$bukkit = trim($bukkit);
	
	if (!file_exists("mcp/$mcp.java") && !file_exists("bukkit/$bukkit.java")) {
		if ($mcp == $bukkit) continue;
		echo "Both files missing in $mcp $bukkit\n";
	} else if (!file_exists("mcp/$mcp.java")) {
		echo "MCP file '$mcp.java' missing in $mcp $bukkit\n";
	} else if (!file_exists("bukkit/$bukkit.java")) {
		echo "Bukkit file '$bukkit.java' missing in $mcp $bukkit\n";
	}
}
?>
