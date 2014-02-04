<?php
// Parameter

define("SHORT_ECHO", false);

if ($argv[1]=='automatic')
{
	echo "Automatik, alle Verzeichnisse 'all really'\n";

	$loggerdir = "../../logger_dbs/";

	$alleDirs = scandir($loggerdir,1);
	foreach($alleDirs as $Dir)
	{
		// Zu verarbeitende Datei:
		echo "\n\n$Dir wird verarbeitet";
		if (is_dir($loggerdir.$Dir))
		{
			echo " als Directory\n";
			system("php create-db.php ".$Dir." all really 2> ".$Dir.".out");
		} else {
			echo " nicht\n";
		}
	}

	die("done.\n");
}

if ($argv[1]=='')
{	
	die("Usage: $argv[0] <Loggerverzeichnis> [all] [really]\nVerarbeitet den jünsten Logfile in die DB.\nImmer nur ein File wird verarbeitet, bei [all] alle im Stack, zwingend muss [really] mitgegeben werden.\n");
} else {
	$logger = $argv[1];
	$loggerdir = "../../logger_dbs/".$logger;
	if (file_exists($loggerdir)==False)
	{
		die("Parameter 1: Loggerverzeichnis ".$logger." existiert nicht !!!\n");
	}
}

// Variablen setzten

mkdir($loggerdir."/dbase");
mkdir($loggerdir."/done");

$db_file = $loggerdir."/dbase/gen_".$logger.".sqlite";
$log_file = "test.txt"; // predefine

// IF DB exists open
if (file_exists($db_file))
{
	if ($argv[2]=="all")
	{
		if ($argv[3]=="really")
		{
			$db = new SQLite3($db_file); 	
		} else {
			die("Datenbankfile: ".$db_file." existiert bereits.\n");
		}
	} else {
		$db = new SQLite3($db_file); 
	}
// ELSE Create DB, Open DB, Build Table, 
} else {
	$db = new SQLite3($db_file) or die("Can't open database file '$db_file'");
	$result = $db->exec('CREATE TABLE measurement (ID INTEGER PRIMARY KEY, SensorID INTEGER, Value REAL, AnswerDate INTEGER, RequestDate INTEGER)');
	$result = $db->exec('CREATE UNIQUE INDEX Sensor_Timestamp ON measurement (SensorID, RequestDate)');
}

// While a Datafile is There

$alleDateien = scandir($loggerdir,1);
foreach($alleDateien as $Datei)
{
	// Zu verarbeitende Datei:
	$DateiInfo = pathinfo($loggerdir."/".$Datei);
	
	if ($DateiInfo['extension']=="sqlite") // wenn nicht, nächste
	{
		echo "\n\n$Datei wird verarbeitet";

		// select Block
    
    		$query2 = "ATTACH '".$loggerdir."/".$Datei."' AS URSPRUNG;";
		echo "query: ".$query2."\n";
	    	$res2 = $db->query($query2);

    		// query absetzen und bis zum schluss verarbeiten.

		$query = "INSERT OR IGNORE INTO measurement (SensorID, Value, AnswerDate, RequestDate) SELECT SensorID, Value, AnswerDate, RequestDate FROM URSPRUNG.measurement";
		echo "query: ".$query."\n";
		$result = $db->exec($query);


// 		Move Datafile
		
		if (rename($loggerdir."/".$Datei, $loggerdir."/done/".$Datei)==false)
		{
			die("Kopieren misslungen von: ".$loggerdir."/".$Datei." nach: ".$loggerdir."/done/".$Datei."\n");
		}

//		Ausstieg wenn nicht Param 2 = all
		if ($argv[2]<>"all") 
		{
			die("File für File !!!\n");
		}

//	Ende erlaubte "csv" endung
	echo "\n";
	}	
	
	if ($DateiInfo['extension']=="csv") // wenn nicht, nächste
	{
		$log_file=$loggerdir."/".$Datei;

// 		Read Datafile + Insert Rows in DB

		$ID = 0;
		$zeilen = file($log_file);

//		Alle Zeilen verarbeiten

		foreach($zeilen as $zeile)
		{
			$ID++;
			$werte = explode (";",$zeile); // ; separated Zerlegen
			$pos = 0;
			$rDate=strtotime("now"); // vorbelegung
			$Index=0;

			foreach($werte as $wert)	// Jeden Parameter verarbeiten
			{
				$pos++;
				if($pos<4)	// Datumsbereich
				{
					if($pos==1)
					{
						if (SHORT_ECHO) {echo "$wert : ";}
					} else
					{
						if ($pos==2)
						{
						   	$sDate1=$wert;
						} else
						{
							$sDate=$wert;
							$sDate.=" ";
							$sDate.=$sDate1;
							$rDate = strtotime($sDate);
							if (SHORT_ECHO) {echo '/'.$sDate.'/'.$rDate.'/'.strftime('%Y-%m-%d-%H:%M:%S', $rDate);}
						}
					}
				} else 		// Wertebereich (2 Params)
				{
					$index = $pos-3;
					$halbe = floor($index/2);
					$gerade = $index - $halbe*2;
					if ($gerade>0)
					{
						$Index = $halbe+1;
						if (SHORT_ECHO) {echo "($Index) $wert ";}  // Wert ausgeben
						$Value[$Index]=$wert;	 // Wert ablegen
					}
				}
			}

// 			DB write , für jeden Wert eine Zeile generieren per execute

			if ($ID>1)
			{
				for ($index=1;$index<$Index;$index++)
				{
					$sValue="'$Value[$index]'";
					$query = "INSERT INTO measurement (SensorID, Value, AnswerDate, RequestDate) VALUES ($index, $sValue, $rDate, $rDate)";
					$result = $db->exec($query);
				}
			}
			if (SHORT_ECHO) {echo "\n";}
		}

// 		Close Datafile, unnötig bei file()

// 		Move Datafile
		
		if (rename($loggerdir."/".$Datei, $loggerdir."/done/".$Datei)==false)
		{
			die("Kopieren misslungen von: ".$loggerdir."/".$Datei." nach: ".$loggerdir."/done/".$Datei."\n");
		}

//		Ausstieg wenn nicht Param 2 = all
		if ($argv[2]<>"all") 
		{
			die("File für File !!!\n");
		}

//	Ende erlaubte "csv" endung
	echo "\n";
	}

// END-While
}

// Close DB

die("All done !!!\n");

?>
