<?php
include "config.php";

$Location = $_GET['hLocation'] ?? '';
$Title = $_GET['hTitle'] ?? '';
$maxPrice = $_GET['maxPrice'] ?? '';
$minPrice = $_GET['minPrice'] ?? '';

$query = "SELECT * FROM House WHERE 1=1";

$hasSearch = !empty($Location) || !empty($Title) || !empty($maxPrice) || !empty($minPrice);

if($hasSearch){
    if(!empty($Location)){
        $locationEscaped = $conn->real_escape_string($Location);
        $query .= " AND houseLocation LIKE '%$locationEscaped%'";
    }
    if(!empty($Title)){
        $titleEscaped = $conn->real_escape_string($Title);
        $query .= " AND houseTitle LIKE '%$titleEscaped%'";
    }
    if(!empty($maxPrice)){
        $query .= " AND housePrice <= " . (float)$maxPrice;
    }
    if(!empty($minPrice)){
        $query .= " AND housePrice >= " . (float)$minPrice;
    }
}

$query .= " ORDER BY houseId ASC";
$result = $conn->query($query);
$hDetails = $result ? $result->fetch_all(MYSQLI_ASSOC) : [] ; 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Bar 3</title>
    <link rel="icon" type="icon" href="images/favicon3.ico">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/searchBar3.css">
</head>
<body>
    <header>
        <a href="#">HOME</a>
        <a href="#">ABOUT US</a>
        <a href="#">LOG IN</a>
        <a href="#">SIGN UP</a>
    </header>
    <main>
        <form class="form1" method="GET" action="">
            <p>SEARCH:</p>
            <input class="input" type="text" name="hLocation" placeholder="Location">
            <input class="input" type="text" name="hTitle" placeholder="House Title">
            <input class="input" type="text" name="maxPrice" placeholder="Maximum Price">
            <input class="input" type="text" name="minPrice" placeholder="Minimum Price">
            <button type="submit">SUBMIT</button>
        </form>
        <br>
        <p>HOUSES DETAILS:</p>
        <?php
        if(empty($hDetails)){
            echo "No house details available";
        }else{
            ?>
            <table>
                <tr>
                    <th>House Id</th>
                    <th>House Title</th>
                    <th>House Location</th>
                    <th>House Price</th>
                    <th>House Description</th>
                    <th>Is House Approved?</th>
                    <th>Approval Date</th>
                    <th>Caretaker Id</th>
                    <th>Action</th>
                </tr>
                <?php
                foreach ($hDetails as $house){
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($house['houseId']) ?></td>
                        <td><?= htmlspecialchars($house['houseTitle']) ?></td>
                        <td><?= htmlspecialchars($house['houseLocation']) ?></td>
                        <td><?= htmlspecialchars($house['housePrice']) ?></td>
                        <td><?= htmlspecialchars($house['houseDescription']) ?></td>
                        <td>
                            <?php
                            $approval = htmlspecialchars($house['isApproved']);
                            if($approval == 1){
                                echo "Yes";
                            }else{
                                echo "No";
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($house['approvalDate']) ?></td>
                        <td><?= htmlspecialchars($house['caretakerId']) ?></td>
                        <td>
                            <form method="GET" action="viewimages3.php">
                                <input type="hidden" name="houseId" value="<?= (int)$house['houseId'] ?>">
                                <!--You need to use less than=? in value. If you don't, it will not output the value to html(even hidden input is outputted to html) therefore the id won't be submitted and no images will show in viewimages3-->
                                <!--The rule is that if its just logic like if else, foreach etc the you use less than=php but if it needs to be echoed like in between <td>, inside things like img src etc, you need to echo them-->
                                <!--Chat said: Anything outputted to html requires the echo but anything logic doesn't-->
                                <input type="submit" class="viewImages" value="View Images">
                            </form>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <?php
        }
        ?>
    </main>
    <footer>
        <ul>
            <li><a href="#">HOME</a></li>
            <li><a href="#">ABOUT US</a></li>
            <li><a href="#">LOG IN</a></li>
            <li><a href="#">SIGN UP</a></li>
        </ul>
        <ul>
            <li>CONTACTS</li>
            <li>+254147484929</li>
            <li>mukabwa@gmail.com</li>
        </ul>
    </footer>
</body>
</html>