  <?
    // post data
	$queryType = ($_POST['queryType']);
	$queryData = ($_POST['queryData']);
	$querySort = ($_POST['querySort']);
	$querySortField = ($_POST['querySortField']);
	$querySelect = ($_POST['querySelect']);
	$querySelectField = json_decode($_POST['querySelectField']);
	$editType = ($_POST['editType']);
	$editData = json_decode($_POST['editData']);
  
    // connection params
	$host = "localhost";
	$user = "postgres";
	$pass = "postgres";
	$db = "library";  
	
	// connect db
	$connection = pg_connect("host=$host dbname=$db user=$user password=$pass");
	if (!$connection) {
	  die("Could not open connection to database server");
	}
	
	// edit db
	if ($editType == 'insertAdmins'	||
		$editType == 'editAdmins'	||
		$editType == 'deleteAdmins') {
	  $query = "BEGIN WORK";
	  $result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
	  
	  if ($editType == 'insertAdmins')
	    $query = "INSERT INTO admins(id, login, pass) 
				  VALUES(nextval('user_id'), '$editData[1]', '$editData[2]')";

	  if ($editType == 'editAdmins')
	    $query = "UPDATE admins 
				  SET login = '$editData[1]', pass = '$editData[2]' 
				  WHERE id = $editData[0]";

	  if ($editType == 'deleteAdmins')
	    $query = "DELETE FROM admins 
				  WHERE id = $editData[0]";

				  $result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
	  
	  $query = "COMMIT";
	  $result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
	}

	// query db
	if ($queryType == 'query1') $query = "SELECT * FROM $queryData";
	if ($queryType == 'queryAdmins') $query = "SELECT * FROM $queryData";
	if ($queryType == 'queryBook') {
	  $query = "SELECT b.id, b.title, p.name as publisher_name, a.name as author_name 
			    FROM book b 
			    INNER JOIN publisher p ON p.id = b.publisher_id 
			    INNER JOIN author a ON a.id = b.author_id ";
	  if ($querySelect == 'true') {
		$prefSelect = " WHERE ";
		if ($querySelectField[0] != '')	{ $query .= $prefSelect." UPPER(b.title) like UPPER('%$querySelectField[0]%') ";	$prefSelect = "AND "; }
		if ($querySelectField[1] != 0)	{ $query .= $prefSelect." b.publisher_id = $querySelectField[1] ";					$prefSelect = "AND "; }
		if ($querySelectField[2] != 0)	{ $query .= $prefSelect." b.author_id = $querySelectField[2] ";						$prefSelect = "AND "; }
		if ($querySelectField[3] != 0)	{ $query .= $prefSelect." EXISTS (SELECT * 
																		  FROM book_technology bt 
																		  WHERE bt.book_id=b.id 
																		  AND bt.technology_id IN ($querySelectField[3]))"; $prefSelect = "AND "; }
	  }
	  if ($querySort <> 'none') $query .=" ORDER BY $querySortField $querySort ";
	}

	// execute & return result db
	if ($query) {
	  $result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));

	  $rows = pg_num_rows($result);
	  if ($rows > 0) {
	    for ($i=0; $i<$rows; $i++) {
		  $row = pg_fetch_object($result, $i);
		  $book[$i] = $row;
	    }
	  }
	
	  echo json_encode($book);
	}

	// disconnect db
	pg_close($connection);	
  ?>
