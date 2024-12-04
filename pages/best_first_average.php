<html>
  <head>
    <title>PHP Test</title>
  </head>
  <body>
    <?php 
    include_once 'connect.php';
    
$sql = "SELECT * FROM `person` WHERE person_id = " .$user_id." ";
 $conn = oci_connect($username = 'j.sui',
                          $password = 'Q38vOyjmt1DQngytzjMzmMnN',
                          $connection_string = '//oracle.cise.ufl.edu/orcl');
$result = $conn->query($sql);
if ($result->num_rows > 0) {
// output data of each row
while($row = $result->fetch_assoc()) {
    echo "user_id: " . $row["person_id"]. " - person_id: " . $row["person_first"]. " " . $row["person_last"]. "<br>";
    $person_id = $row["person_id"];
    $person_first = $row["person_first"];
}
} else {
echo $sql;
}
$conn->close();

?>
  </body>
</html>
