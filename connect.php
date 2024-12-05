 $conn = oci_connect($username = 'j.sui',
                          $password = 'Q38vOyjmt1DQngytzjMzmMnN',
                          $connection_string = '//oracle.cise.ufl.edu/orcl');
    if (!$conn) {
        $e = oci_error();
        echo "Connection failed: " . $e['message'];
        exit;
    }
