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

	// function: convert file to data
	function fileToData($fileName, $filePath) {
	  $fileSave = $filePath . $fileName;
	  $escaped = '';
		if ($fileName && file_exists($fileSave)) {
		  $binaryData = file_get_contents($fileSave);
		  if ($binaryData)
		    $escaped = pg_escape_bytea($binaryData);
		  unlink($fileSave);
		}
	  return $escaped;
	}

	// function: convert data to file
	function dataToFile($fileData, $fileName, $filePath) {
	  if ($fileName && $fileData) {
		$saveFile = $filePath . $fileName;
		$unescaped = pg_unescape_bytea($fileData);
		file_put_contents($saveFile, $unescaped, LOCK_EX);
	  }
	  else $fileName = "empty";
	  return $fileName;
	}
	
	// function: execute query and return records: array objects
	function executeQuery($connection, $query) {
	  $result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
	  $rows = pg_num_rows($result);
	  if ($rows > 0) {
	    for ($i=0; $i<$rows; $i++) {
		  $row = pg_fetch_object($result, $i);
		  $exResult[$i] = $row;
	    }
	  }
	  return $exResult;	
	}
	
	// edit db
	$query = '';
	if ($editType == 'insertBook'	||
		$editType == 'editBook'	||
		$editType == 'deleteBook') {
	  $query = "BEGIN WORK";
	  $result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
	  
	  if ($editType == 'insertBook') {
		$query = "select nextval('book_id_new') as id";
		$result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
		$editData[0] = pg_fetch_result($result, 'id');
		
		$editData[6] = basename($editData[6]);
		$escaped = fileToData($editData[6], './');
	    if ($escaped) $editData[6] = str_replace(" ","_",$editData[6]); else $editData[6] = '';
			
	    $query = "INSERT INTO book(id, title, publisher_id, author_id, description, file_type, binary_data) 
				  VALUES($editData[0], '$editData[1]', $editData[2], $editData[3], '$editData[5]', '$editData[6]', '$escaped')"; 
		$result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
	  }

	  if ($editType == 'editBook') {
		$editData[6] = basename($editData[6]);
		$escaped = fileToData($editData[6], './');
	    $editData[6] = str_replace(" ","_",$editData[6]);

	    $query = "UPDATE book 
				  SET title = '$editData[1]', publisher_id = $editData[2], author_id = $editData[3], description = '$editData[5]', file_type = '$editData[6]'";
				  if ($escaped) $query .= ", binary_data = '$escaped' ";
				  $query .=  " WHERE id = $editData[0]";
		$result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
	  }

	  if ($editType == 'deleteBook') {
	    $query = "DELETE FROM book_technology WHERE book_id = $editData[0]";
		$result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));

	    $query = "DELETE FROM book 
				  WHERE id = $editData[0]";
		$result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
	  }

	  if ($editType == 'editBook') {
	    $query = "DELETE FROM book_technology WHERE book_id = $editData[0]";
		$result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
	  }
	  
	  if ($editType == 'editBook' || $editType == 'insertBook') {		
		for ($i = 0, $size = count($editData[4]); $i < $size; $i++) {
		  $technology_id = $editData[4][$i]; 
		  $query = "INSERT INTO book_technology (book_id, technology_id) 
					VALUES ($editData[0], $technology_id)";
		  $result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
		}
	  }

	  echo($editData[0]);
	  
	  $query = "COMMIT";
	  $result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));
	}

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
	$query = '';
	if ($queryType == 'query1') { 
	  $query = "SELECT * FROM $queryData t";
	  if ($querySelect == 'true' && $querySelectField[4] != 0) {
		$query .= " left join book_technology b on b.technology_id = t.id and b.book_id = $querySelectField[4] ";
	  }
	  $data = executeQuery($connection, $query);
	  echo json_encode($data);
	}
		
	if ($queryType == 'queryAdmins') {
	  $query = "SELECT * FROM $queryData";
	  $admins = executeQuery($connection, $query);
	  echo json_encode($admins);
	}
	if ($queryType == 'queryBook') {
	  $query = "SELECT b.id, b.title, b.publisher_id, p.name as publisher_name, b.author_id, a.name as author_name, b.description, b.file_type 
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
		if ($querySelectField[4] != 0)	{ $query .= $prefSelect." b.id = $querySelectField[4] ";	$prefSelect = "AND "; }
	  }
	  if ($querySort && $querySort <> 'none') $query .=" ORDER BY $querySortField $querySort ";

	  $book = executeQuery($connection, $query);
	  echo json_encode($book);
	}

	if ($queryType == 'queryBookData') {
	  $query = "SELECT b.id, b.file_type, b.binary_data 
			    FROM book b 
				WHERE b.id = $querySelectField[4] ";

	  $result = pg_query($connection, $query) or die("Error in query: $query." . pg_last_error($connection));	  
	  $fileName = pg_fetch_result($result, 'file_type');
	  $fileData = pg_fetch_result($result, 'binary_data');
	  
	  $fileName = dataToFile($fileData, $fileName, './');
	  
	  echo $fileName;
	}			

	// disconnect db
	pg_close($connection);	
  ?>
