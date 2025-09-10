<?php 
include 'config.php';

$location = $_GET['Location']  ?? '';
$maxPrice = $_GET['maxPrice']  ?? '';
$minPrice = $_GET['minPrice']  ?? '';

$query = "
SELECT h.houseId, h.houseTitle, h.houseLocation, h.housePrice, hi.imageUrl
FROM House h
LEFT JOIN(
SELECT houseId, MIN(imageUrl) AS imageUrl
FROM HouseImages
GROUP BY houseId
)
hi ON h.houseId = hi.houseId
WHERE isApproved = 1
";
//The reason we are specifying h.houseId etc above is because different tables have similar names so we need
//to specify that some of the data is coming from House and some is coming from HouseImages
//If the names were different, we wouldn't need to specify with h and hi
//We are getting the houseId from HouseImages to use it to join the two tables
//The point of group by:
//MIN(imageUrl) is an aggregate function (it looks at multiple rows and returns one value).
//houseId is not. SQL requires you to tell it how to group rows so it knows which values go with which house.
//So the rule is: if you use an aggregate function, every other selected column must either be aggregated or included in GROUP BY
//So we are using GROUP BY to tell it, for this unique houseId, assign it with the first file alphabetically
//A simpler version of the query above that I will probably use next time instead:
//SELECT House.houseId, House.houseTitle, House.houseLocation, House.housePrice, MIN(HouseImages.imageUrl) AS imageUrl
//FROM House
//LEFT JOIN HouseImages ON House.houseId = HouseImages.houseId
//WHERE isApproved = 1
//GROUP BY House.houseId, House.houseTitle, House.houseLocation, House.housePrice
//Notice that in this query, I have used the non-aggregated columns(Location,Title etc) to goup the aggregated function
//Also, notice that the WHERE coumes b4 the group here.
//The right order for MySQL is: SELECT -> FROM -> JOIN -> WHERE -> GROUP -> ORDER
//In the first version, WHERE came after bcz the selecting from HouseImages was a subquery. And we needed to GROUP the aggregated function to a non-aggregated column before joining the data. This is allowed



$hasSearch = !empty($location) || !empty($maxPrice) || !empty($minPrice);

if($hasSearch){

    if(!empty($location)){
    $locationEscaped = $conn->real_escape_string($location);
    $query .= " AND h.houseLocation LIKE '%$locationEscaped%'";
    }

    if(!empty($maxPrice)){
    $query .= " AND h.housePrice <= " . (float)$maxPrice;
    }

    if(!empty($minPrice)){
    $query .= " AND h.housePrice >= " . (float)$minPrice;
    }
    
}

$query .= " ORDER BY approvalDate DESC";

$result = $conn->query($query);
$houses = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Bar 1</title>
    <link rel="icon" type="icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/searchBar1.css">
</head>


<body>
    <header>
        <img src="images/black image.jpeg">
        <ul>
            <li><a>HOME</a></li>
            <li><a>ABOUT US</a></li>
            <li><a>LOG IN</a></li>
            <li><a>SIGN UP</a></li>
        </ul>
    </header>

    <main>
        <form action="" method="GET" >
        <!--In an HTML form, the action attribute tells the browser where to send the form data once the user submits it (usually by clicking the submit button). Since we don't have a submit button, we leave it empty-->
          <p>SEARCH:</p>
          <input type="text" name="Location" placeholder="Location" value="<?= htmlspecialchars($location) ?>">
          <input type="text" name="maxPrice" placeholder="Maximum Price" value="<?= htmlspecialchars($maxPrice) ?>">
          <input type="text" name="minPrice" placeholder="Minimum Price" value="<?= htmlspecialchars($minPrice) ?>">
          <button type="submit">ENTER</button>
        </form>

        <p class="available">AVAILABLE HOUSES:</p>

        <section>
            <?php
            if(count($houses) > 0){
                $count = 0;
                ?>
                <div class="house-row">
                <?php
                foreach ($houses as $house){
                    //This loop makes a house looks like this:
                    /**$house = [
                    "houseId" => 1,
                    "houseTitle" => "Green Villa",
                    "imageUrl" => "villa.jpg" etc.
                    ];
                    */
                    //This is why we don't need to use it like this: hi.imageUrl below. 
                    //We were using that to get the specific houses but now that we have them and have broken them down in single arrays, we don't need to do that
                    $image = !empty($house['imageUrl']) ? $house['imageUrl'] : 'images/default.png';
                    $houseId = (int)$house['houseId'];
                    // Below <?=...is a short form of <?php echo so u're kinda continuing this echo here ?>
                    <div class="house-details">
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($house['houseTitle']) ?>">
                    <div class="writings">
                    <p class="title">Title: <?= htmlspecialchars($house['houseTitle']) ?></p>
                    <p>Location: <?= htmlspecialchars($house['houseLocation']) ?></p>
                    <p>Price: <?= number_format($house['housePrice'], 2) ?></p> <!--Remember the syntax is:number_format($price, 2); The 2 here means decimal places-->
                    </div>
                    </div>
                    <?php 
                    $count++;
                    if($count % 4 == 0 && $count < count($houses)){
                        ?> </div><div class="house-row"> <?php
                    }
                }
                ?> </div> <?php
            }else{
                ?> <p class="available2">No houses available</p> <?php
            }
            ?>
        </section>
    </main>
    

    <footer>
        <ul>
            <li><a>HOME</a></li>
            <li><a>ABOUT US</a></li>
            <li><a>LOG IN</a></li>
            <li><a>SIGN UP</a></li>
        </ul>
        <ul>
            <li>Contact Us</li>
            <li>Mobile Number: +25417383931</li>
            <li>Email: mukabwa@gmail.com</li>
        </ul>
    </footer>
</body>

</html>