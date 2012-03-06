Usage
-----

1. Create the following directories: bukkit mcp conf new_conf

2. Put the full decompiled Bukkit source in "bukkit"

3. Copy the Bukkit source from their Github over it

4. Put the full decompiled MCP source in "mcp"

5. Put the following files from MCP's "conf" directory in conf: server.srg fields.csv methods.csv

6. Run "mapclasses.php" and fill in any new classes. The similar.sh script may make finding the right classes easier.

7. Run "mapfields.php" and "mapmethods.php"

8. The "new_conf" directory now contains the updated server.srg, fields.csv, and methods.csv to use in porting.



Note: Some Bukkit sourcefiles will need to be edited for the mapper to work properly.
