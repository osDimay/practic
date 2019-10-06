<?php
require_once ("stormindex.php");

echo "<html><head></head><body>";
    echo "<table id = example class=display cellspacing= 0 width= 100% border=1px solid black>";
        echo "<thead>";
        echo "<tr>";
            echo "<th>id</th>";
            echo "<th>name</th>";
            echo "<th>parent id</th>";
            echo "<th>creation time</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
            $db = new stormproject\DbClass();
            $mass = $db->extractDataFromDb();
            foreach ($mass as $row) {
            echo "<tr>";
                    echo"<td>"; echo $row["id"]; echo "</td>";
                    echo"<td>"; echo $row["name"]; echo "</td>";
                    echo"<td>"; echo $row["parent_id"]; echo "</td>";
                    echo"<td>"; echo $row["creation_time"]; echo "</td>";
            echo "</tr>";
            }
echo "</tbody></table></body></html>";