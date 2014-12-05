  <?
    // post data
	$uploadfile = "./".basename($_FILES['fileBook']['name']);
	
	if (move_uploaded_file($_FILES['fileBook']['tmp_name'], $uploadfile)) {
	  $result = "success";
	} 
	else {
	  $result = "error";
	}

//	$result = '<span>(file upload: '.$_FILES['fileBook']['name'].')</span>';
  ?>
  
  <script type="text/javascript">top.uploadResult('<?=$result?>');</script>  