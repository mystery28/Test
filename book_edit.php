<HTML>
  <HEAD>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html"; charset="Win1251">
	<TITLE>book edit</TITLE>

    <link rel="stylesheet" href="style_i.css" type="text/css">
	
	<?
	  $bookId = ($_GET['bookId']);
	?>
	
	<script type="text/javascript">
	
	function loadItemsTable() {
	  var bookId = '<? echo ($bookId); ?>',
		  typeFilter = ['%', 0, 0, 0, bookId]; 
	
	  if (bookId == -1) {
	    document.getElementById("inputTitle").setAttribute('data-id', -1);
		loadDB('query1','publisher','false',['%', 0, 0, 0,0]);
		loadDB('query1','author','false',['%', 0, 0, 0,0]);
		loadDB('query1','technology','false',['%', 0, 0, 0,0]);
	  }
	  
	  loadDB('queryBook', 'Book', 'true', typeFilter);
	}

	function loadDB(queryType, queryData, querySelect, querySelectField, editType, editData) {
	  var req = new XMLHttpRequest(),
		  params = 'queryType=' + encodeURIComponent(queryType) +
				   '&queryData=' + encodeURIComponent(queryData) +
				   '&querySelect=' + encodeURIComponent(querySelect) +
				   '&querySelectField=' + encodeURIComponent(JSON.stringify(querySelectField)) +
				   '&editType=' + encodeURIComponent(editType) +
				   '&editData=' + encodeURIComponent(JSON.stringify(editData));
	  req.open('POST', 'work_base.php', true);
	  req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	  req.onreadystatechange = function() { loadData(req, queryType, queryData, querySelectField); };   // обработчик
	  req.send(params);
	}

	function loadData(req,queryType,queryData,querySelectField) {
	  if (req.readyState == 4) {
	    if (req.status == 200) {
	      var db = eval('(' + req.responseText + ')');
		  		  
		  switch (queryType) {
			case 'query1':		loadSelectNames(db,queryData,querySelectField[4]);	break;
			case 'queryBook':	loadInputData(db); 									break;
			case 'exit':		document.location.href = "index.html";
		  }
	    }
	  }
	}

	function loadInputData(db) {
	  if (db) {
		document.getElementById("inputTitle").setAttribute('data-id', db[0].id);
		document.getElementById("inputTitle").value = db[0].title;

		if (db[0].id) {
		  loadDB('query1','publisher','false',['%', 0, 0, 0, db[0].publisher_id]);
		  loadDB('query1','author','false',['%', 0, 0, 0, db[0].author_id]);
		  loadDB('query1','technology','true',['%', 0, 0, 0, db[0].id]);
		}

		document.getElementById("inputDescription").value = db[0].description;
		document.getElementById("inputFilePath").value = db[0].file_type;
	  }
	}

	function editBook() {
	  var bookId = document.getElementById("inputTitle").getAttribute('data-id');
		  bookTitle = document.getElementById("inputTitle").value,
		  publisherId = document.getElementById("selectPublisher").value,
		  authorId = document.getElementById("selectAuthor").value,
		  technologyId = technologySelectedId(),
		  description = document.getElementById("inputDescription").value,
		  fileType = document.getElementById('inputFilePath').value;

	  if (bookTitle == '') alert('empty title');
	  else if (publisherId == 0) alert('select publisher');
	  else if (authorId == 0) alert('select author');
	  else if (!technologyId) alert('select technology');
	  else {
		if (bookId == -1) 
		  loadDB('exit', 'Book', '', '', 'insertBook', [0,bookTitle,publisherId,authorId,technologyId,description,fileType]);
		else {
		  loadDB('exit', 'Book', '', '', 'editBook', [bookId,bookTitle,publisherId,authorId,technologyId,description,fileType]);
		}
	  }
	}
	
	function technologySelectedId() {
	  var result = new Array();
	  
	  for (var i=1; i<document.getElementById("selectTechnology").options.length; i++) {
		if (document.getElementById("selectTechnology").options[i].selected) 
			result.push(document.getElementById("selectTechnology").options[i].value);
	  }
	  
	  return result;
	}
						  
	function deleteBook() {
	  var bookId = document.getElementById("inputTitle").getAttribute('data-id');
		
		if (bookId != -1) {
		  loadDB('exit', 'Book', '', '', 'deleteBook', [bookId]);
		}
	}

	function loadSelectNames(dbData,tableName,selectID) {
	  var idObj = 'select'+(tableName.charAt(0).toUpperCase() + tableName.substr(1)),
		  i = 0;
	  
	  document.getElementById(idObj).options[i++] = new Option('select '+tableName, '0');
	  for (var value in dbData) {
	    document.getElementById(idObj).options[i++] = new Option(dbData[value].name, dbData[value].id);
	  }
	  
	  i = 1;
	  if (tableName == 'technology') 
		for (var value in dbData) {
		  if (dbData[value].book_id == selectID) 
			document.getElementById(idObj).options[i].selected = true;
		  
		  i++;
		}

	  if (tableName == 'publisher' || tableName == 'author')
	    document.getElementById(idObj).value = selectID;
	}
	
	function loadValue() {
	  document.getElementById('inputFilePath').setAttribute('style','background-color: #FFFFFF');
	  document.getElementById('inputFilePath').value = document.getElementById('selectFile').value;
	}

	function uploadResult(text) {
//	  document.getElementById('result').innerHTML = text;
	  if (text == 'success') document.getElementById('inputFilePath').setAttribute('style','background-color: #9CEE90');
	  if (text == 'error')   document.getElementById('inputFilePath').setAttribute('style','background-color: #F5DEB3');
	}
	
	</script>

  </HEAD>
  <BODY onload='loadItemsTable();'>
  
  <div class="bookEditForm" id="bookEdit">
	<p align="center">book add/edit</p>
	<div><span class="label"> title: </span><input type="text" id="inputTitle"></div>
	<div><span class="label"> publisher: </span><select class="selectEditBookPA" id="selectPublisher"></select></div>
	<div><span class="label"> author: </span><select class="selectEditBookPA" id="selectAuthor"></select></div>
	<div class="label2"> technologies: </div>
	<div><select multiple="" id="selectTechnology"></select></div>
	<div class="label2"> description: </div>
	<div><textarea id="inputDescription"></textarea></div>
	
	<form id="loadFile" enctype="multipart/form-data" method="POST" action="load_file.php" target="uploader">
	<input  type="hidden" name="MAX_FILE_SIZE" value="100000000"  />
	<iframe name="uploader" src="" style="display: none"></iframe>
	<div><input type="text" id="inputFilePath"><input id="buttonUpload" type="submit" value="Upload"/></div>
	<div class="blockSelectFile"></div>
	  <input class="customFile" name="fileBook" title="choose file" type="file" id="selectFile" onchange="loadValue();" />
	<div><a class="newFileButton" href="#">choose...</a></div>
	<!-- <div id="result"></div> -->
	</form>

	<div id="bookEditFoot">
	<br>
	  <div class="pBookEdit"><a href="#" onClick="editBook();">[ save ]</a></div>
	  <div class="pBookEdit"><a href="#" onClick="deleteBook();">[ delete ]</a></div>
	  <div class="pBookEdit"><a href="index.html" onClick="">[ close ]</a></div>
	</div>	
  </div>
  
  </BODY>
</HTML>
