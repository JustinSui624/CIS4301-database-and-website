<html>
  <head>
    <title>PHP Test</title>
  </head>
  <body>
    <?php 
    include_once 'connect.php';
    
$sql = "SELECT * FROM persons WHERE id = "2003LIDO01";
 $conn = oci_connect($username = 'j.sui',
                          $password = 'Q38vOyjmt1DQngytzjMzmMnN',
                          $connection_string = '//oracle.cise.ufl.edu/orcl');
$result = $conn->query($sql);
if ($result->num_rows > 0) {
echo $result;

} else {
echo $sql;
}
$conn->close();

?>
  </body>
</html>
