<?php
  $name = $argv[1];
	
	printf("$name: <br>");
	
	sleep(2);
    
	

    $fp = fopen("log.txt", "a"); // Открываем файл в режиме записи
      $mytext = "$name: \r\n";
      $test = fwrite($fp, $mytext); // Запись в файл
    fclose($fp); //Закрытие файла

?>