#!/usr/bin/php
<?php
$mcpfiles = glob("mcp/*.java");
$bukkitfiles = glob("bukkit/*.java");

$mcpmapped = array();
$bukkitmapped = array();

$lines = file("to_second_mappings");
foreach ($lines as $line) {
	list($mcp,$bukkit) = split(" ", $line, 2);
	$bukkit = trim($bukkit);
	
	$mcpmapped[] = "mcp/$mcp.java";
	$bukkitmapped[] = "bukkit/$bukkit.java";
}

sort($mcpmapped);
sort($bukkitmapped);

foreach (array_diff($mcpfiles, $mcpmapped) as $missing) {
	echo "Unmapped MCP file: $missing\n";
}
echo "\n";
foreach (array_diff($bukkitfiles, $bukkitmapped) as $missing) {
	echo "Unmapped Bukkit file: $missing\n";
}
?>
