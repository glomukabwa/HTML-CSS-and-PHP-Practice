<?php
include "config.php";

$houseId = intval($_GET['houseId']) ?? '';
//OR:
/**
 * $hId = $_GET['houseId'];
 * $houseId = (int)$hId OR intval($hId)
 */

$stmt = $conn->prepare("SELECT imageUrl FROM HouseImages WHERE houseId = ?");
$stmt->bind_param("i", $houseId);
$stmt->execute();
$result = $stmt->get_result();//The result is in the form of rows of a table

if($result->num_rows > 0){//We are doing this to check if the table has the url since the urls are stored in two different tables depending on if the house is approved
    ?> 
    <p class="p">House Id: <?= $houseId ?></p>
    <div class="HouseRow">
    <?php
    while($houseImgs = $result->fetch_assoc()){//fetch_assoc fetches a row from the table in the result we have gotten above
        //while checks if the fetch_assoc() is  returning arrays. If it returns null which mean there is no more data in the table, the while becomes false so we stop
        //Why do we need to iterate? So imagine we have a table like:
        // 1 => image1.jpg
        // 1 => image2.jpg
        //fetch_assoc will fetch the first one then will give the image the src as indicated below so that the image is displayed
        //If we don't iterate, it will only fetch the first image and then stop and in out database, a house can have many images
        ?> 
        <img class="Houseimages" src="<?= htmlspecialchars($houseImgs['imageUrl']) ?>">
        <?php
    }
    ?>
    </div>
    <?php
    
}else{
    $stmt = $conn->prepare("SELECT imageUrl FROM PendingHouseImages WHERE houseId = ?");
    $stmt->bind_param("i", $houseId);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        ?> 
        <p class="p">House Id: <?= $houseId ?></p>
        <div class="HouseRow">
        <?php
        while($houseImgs2 = $result->fetch_assoc()){
            ?>
            <img class="Houseimages" src="<?= htmlspecialchars($houseImgs2['imageUrl']) ?>" alt="">
            <?php
        }
        ?>
        </div>
        <?php
    }else{
        echo "No house images found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Images</title>
    <link rel="icon" type="icon" href="images/favicon3.ico">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/searchBar3.css">
</head>
<body>
</body>
</html>