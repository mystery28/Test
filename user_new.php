<?
$json = ($_POST);
$json = json_encode($json);

//путь и сам файл
	$file = "db.json";
	 
	// если файла нет, то создадим его	 
//	if( !is_file($file)) 
{
	    $fp = fopen($file, "w+"); // ("r" - считывать "w" - создавать "a" - добовлять к тексту), мы перезаписываем файл
		fwrite($fp, $json); // записываем json в наш файл
	    fclose ($fp); // закрываем файл
}

?>