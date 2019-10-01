<?php
require_once ("stormindex.php");
?>

<html>

<head>
</head>

<body>
    <table id="example" class="display" cellspacing="0" width="100%" border="1px solid black">
        <thead>
        <tr>
            <th>id</th>
            <th>name</th>
            <th>parent id</th>
            <th>creation time</th>
            <th>otvetstvennye</th>
        </tr>
        </thead>

        <tbody>
            <?php
            $db = new DbClass();
            $db->selectMass();
            foreach ($mass as $row):;
            ?>
                <tr>
                    <td><?php echo $row["id"];?></td>
                    <td><?php echo $row["name"];?></td>
                    <td><?php echo $row["parent_id"];?></td>
                    <td><?php echo $row["creation_time"];?></td>
                    <td><?php echo $row["otvetstvennye"];?></td>
                </tr>
            <?php endforeach;?>
        </tbody>
    </table>
</body>

</html>