<?php

include "config.php";

$Cid = $_GET["Cid"] ?? "";
$Cname = $_GET["Cname"] ?? "";
$Cemail = $_GET["Cemail"] ?? "";

$query = "SELECT * FROM Caretaker WHERE 1=1";

$hasSearch = !empty($Cid) || !empty($Cname) || !empty($Cemail);

if($hasSearch){
    if(!empty($Cid)){
        $query .= " AND caretakerId = " . (int)$Cid; //You still have to do this since type number only tells the browser to accept only numbers but all form data is still sent as a string
    }
    if(!empty($Cname)){
        $nameEscaped = $conn->real_escape_string($Cname);
        $query .= " AND caretakerName LIKE '%$nameEscaped%'";
    }
    if(!empty($Cemail)){
        $emailEscaped = $conn->real_escape_string($Cemail); //You still have to do this. Chat said that a malicious user can still find a way to bypass the instruction that the input should be an email. so everything text, do this
        $query .= " AND caretakerEmail Like '%$emailEscaped%'";
    }
}

$query .= " ORDER BY caretakerId ASC";
$result = $conn->query($query);
$data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Bar</title>
    <link rel="icon" type="icon" href="images/favicon2.ico">
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/searchBar2.css">
</head>
<body>
    <header>
        <ul>
            <li><a href="#">HOME</a></li>
            <li><a href="#">ABOUT US</a></li>
            <li><a href="#">SIGN UP</a></li>
            <li><a href="#">LOG IN</a></li>
        </ul>
    </header>

    <main>
        <form action="" method="GET">
            <p>SEARCH:</p>
            <input type="number" name="Cid" placeholder="Caretaker Id" >
            <input type="text" name="Cname" placeholder="Caretaker name">
            <input type="email" name="Cemail" placeholder="Caretaker email">
            <button type="submit">SUBMIT</button>

            <table>
                <tr>
                    <th>Caretaker Id</th>
                    <th>Caretaker Name</th>
                    <th>Caretaker Email</th>
                    <th>Caretaker Phone Number</th>
                </tr>
                <?php 
                if(empty($data)){ //Here you can also use if($data == []) However plz note that it is a double equals. If you use only one like this: $data = [], the code will first assign an empty array to data then check if data is empty. Your intended values won't be displayed, an error will occur
                    echo "No details available";
                }else{
                    foreach ($data as $row){
                        ?>
                        <tr>
                        <td><?= htmlspecialchars($row['caretakerId']) ?></td> <!--I am not converting it to an int cz we are not gonna use it in any logic in the future-->
                        <td><?= htmlspecialchars($row['caretakerName']) ?> </td>
                        <td><?= htmlspecialchars($row['caretakerEmail']) ?></td>
                        <td><?= htmlspecialchars($row['caretakerPhoneNumber']) ?></td> <!--I am using this instead of int cz if phone number has a zero or plus at the beginning and you make it an int, you lose those characters and numbers at the front-->
                        </tr>
                        <?php
                    }
                }
                ?>
            </table>
        </form>
    </main>

    <footer>
        <ul>
            <li><a href="#">HOME</a></li>
            <li><a href="#">ABOUT US</a></li>
            <li><a href="#">SIGN UP</a></li>
            <li><a href="#">LOG IN</a></li>
        </ul>
        <ul>
            <li>CONTACT INFO</li>
            <li>+254118823876</li>
            <li>mukabwa@gmail.com</li>
        </ul>
    </footer>
    
</body>
</html>