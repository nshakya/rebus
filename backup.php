<?php
require('db_configuration.php');
backup_tables(DATABASE_HOST,DATABASE_USER,DATABASE_PASSWORD,DATABASE_DATABASE);

/* backup the db OR just a table */
function backup_tables($host,$user,$pass,$name,$tables = '*')
{

    $link = mysqli_connect($host,$user,$pass);
    mysqli_select_db($link,$name);

    //get all of the tables
    if($tables == '*')
    {
        $tables = array();
        $result = mysqli_query($link,'SHOW TABLES');
        while($row = mysqli_fetch_row($result))
        {
            $tables[] = $row[0];
        }
    }
    else
    {
        $tables = is_array($tables) ? $tables : explode(',',$tables);
    }

    $return='';
    //cycle through
    foreach($tables as $table)
    {
        $result = mysqli_query($link,'SELECT * FROM '.$table);
        $num_fields = mysqli_num_fields($result);

        $return.= 'DROP TABLE '.$table.';';
        $row2 = mysqli_fetch_row(mysqli_query($link,'SHOW CREATE TABLE '.$table));
        $return.= "\n\n".$row2[1].";\n\n";

        for ($i = 0; $i < $num_fields; $i++)
        {
            while($row = mysqli_fetch_row($result))
            {
                $return.= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j < $num_fields; $j++)
                {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = preg_replace("#\n#", "\\n", $row[$j]);
                    if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                    if ($j < ($num_fields-1)) { $return.= ','; }
                }
                $return.= ");\n";
            }
        }
        $return.="\n\n\n";
    }

    //save file
    $today = date("m.d.y");
    $handle = fopen('..\\..\\htdocs\\Backup_'.$today.'.sql','w+');
    fwrite($handle,$return);
    fclose($handle);
}
header('Location: ' . $_SERVER['HTTP_REFERER']);
?>