<?php
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "db_name";
$db_serverport = "3306";
$backup_file_name = $db_name . '_backup_' . time() . '.sql';
$sqlScript = "";

$conn = new mysqli($db_server, $db_user,$db_pass,$db_name,$db_serverport);
$conn->set_charset("utf8");
//Selecciona solo tablas, las vistas son ignoradas
$sql = "SELECT table_name AS Tables FROM information_schema.TABLES  WHERE table_schema = '".$db_name."' and Table_Type != 'VIEW'";
$result = $conn->query($sql);

  if (mysqli_num_rows($result) == true) {
    $lista_tablas = array();
    while($row = $result->fetch_assoc())
        {
            $tablas  = $row["Tables"];
            // echo $tablas . "<br>";
            array_push($lista_tablas,$tablas);
        }
    } 
//Recorre tablas para extraer los datos
  foreach ($lista_tablas as &$tabla) {
    $sql = "SHOW CREATE TABLE ". $tabla;
    $result = $conn->query($sql);
    $crear_tabla = mysqli_fetch_row($result);
    $sqlScript .= "\n\n" . $crear_tabla[1]. ";\n\n";

    $sql = "SELECT * FROM ". $tabla;
    $result = $conn->query($sql);
    $columnCount = mysqli_num_fields($result);
      for ($i = 0; $i < $columnCount; $i ++) {
        while ($row = mysqli_fetch_row($result)) {
            $sqlScript .= "INSERT INTO $tabla VALUES(";
            for ($j = 0; $j < $columnCount; $j ++) {
                $row[$j] = $row[$j];
                
                if (isset($row[$j])) {
                  //Reemplaza comillas dobles para evitar errrores en la importacion
                  $row[$j] = str_replace(chr(34)," ", $row[$j] );
                    $sqlScript .= '"' . $row[$j] . '"';
                } else {
                    $sqlScript .= '""';
                }
                if ($j < ($columnCount - 1)) {
                    $sqlScript .= ',';
                }
            }
            $sqlScript .= ");\n";
        }
    }
  $sqlScript .= "\n"; 
}

$conn->close();
if(!empty($sqlScript)){
      // Guardar data en archivo de backup
      $fileHandler = fopen($backup_file_name, 'w+');
      $number_of_lines = fwrite($fileHandler, $sqlScript);
      fclose($fileHandler); 
      // Descargar el archivo de backup
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename=' . basename($backup_file_name));
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize($backup_file_name));
      ob_clean();
      flush();
      readfile($backup_file_name);
      exec('rm ' . $backup_file_name); 
  }
?>