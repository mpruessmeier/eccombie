<?php
// Parameter

require "settings.php";

function curl_post($url, array $post = NULL, array $options = array())
{
    $defaults = array(
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_URL => $url,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_TIMEOUT => 4,
        //CURLOPT_POSTFIELDS => http_build_query($post)
    );

    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));
    if( ! $result = curl_exec($ch))
    {
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    return $result;
} 

if ($argv[1]=='')
{	
	die("Usage: $argv[0] <Loggerverzeichnis> [all] [really]\nVerarbeitet den juensten Drops in emoncms.\nImmer nur ein File wird verarbeitet, bei [all] alle im Stack, zwingend muss [really] mitgegeben werden.\n");
} else {
	$logger = $argv[1];
	$loggerdir = "../dropzone/".$logger;
	if (file_exists($loggerdir)==False)
	{
		die("Parameter 1: Dropverzeichnis ".$logger." existiert nicht !!!\n");
	}
}

if ($argv[1]=='automatic')
{
	echo "Automatik, alle Verzeichnisse 'all really'\n";

	$loggerdir = "../dropzone/";

	$alleDirs = scandir($loggerdir,1);
	foreach($alleDirs as $Dir)
	{
		// Zu verarbeitende Datei:
		echo "\n\n$Dir wird verarbeitet";
		if (is_dir($loggerdir.$Dir))
		{
			echo " als Directory\n";
			system("php inject-emoncms.php ".$Dir." all really 2> ".$Dir.".out");
		} else {
			echo " nicht\n";
		}
	}

	die("done.\n");
}

// Variablen setzten

mkdir($loggerdir."/done");

$log_file = "test.txt"; // predefine

// While a Datafile is There

$alleDateien = scandir($loggerdir,1);
foreach($alleDateien as $Datei)
{
	// Zu verarbeitende Datei:
	$DateiInfo = pathinfo($loggerdir."/".$Datei);

	// SQLITE Dateien aus Loggern 
	if ($DateiInfo['extension']=="sqlite") // wenn nicht, nächste
	{
		// SQLITE Dateien öffnen und zu emoncms injecten

		echo "\n\n$Datei wird verarbeitet";

		// sqlite Datei öffnen
		// muss noch !!!

    		// query absetzen und bis zum schluss verarbeiten.
		// muss noch !!!

		// Zeilenweise injecten
		// muss noch !!!

		// verarbeitete Datei in DONE transportieren 
		
		if (rename($loggerdir."/".$Datei, $loggerdir."/done/".$Datei)==false)
		{
			die("Kopieren misslungen von: ".$loggerdir."/".$Datei." nach: ".$loggerdir."/done/".$Datei."\n");
		}
		
		// Key in PICKUPZONE schreiben
		// muss noch

		// Ausstieg wenn nicht Param 2 = all
		if ($argv[2]<>"all") 
		{
			die("File für File !!!\n");
		}

		echo "\n";
	}	

	// CSV Dateien aus Loggern	
	if ($DateiInfo['extension']=="csv") // wenn nicht, nächste
	{
		$log_file=$loggerdir."/".$Datei;

		// Read Datafile + Inject Rows to emoncms

		$ID = 0;
		$zeilen = file($log_file);

		// Alle Zeilen verarbeiten

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

			// Inject über Curl für jeden einzelnen Value
			$post[0]= "time=".$rDate;
			$post[1] = "csv=";
			
			//if (curl_post($http_addr."/input/post.json",$post,[]))
			if ($ID>1)
			{
				for ($index=1;$index<$Index;$index++)
				{
					if ($index>1) $post[1].=",";
					error_reporting(0);
					$post[1].=strtr( $Value[$index], ',', '.');
					
					// $query = "INSERT INTO measurement (SensorID, Value, AnswerDate, RequestDate) VALUES ($index, $sValue, $rDate, $rDate)";
					// $result = $db->exec($query);
					//ECHO "INSERT INTO measurement (SensorID, Value, AnswerDate, RequestDate) VALUES ($index, $sValue, $rDate, $rDate)\n";
					//ECHO "http://localhost/emoncms/input/post.json?time=$rDate&json=\{$index:$sValue\}\n\n";
				}
			}

			$sendung = $http_addr."/input/post.json"."?".$post[0]."&".$post[1]."&apikey=6cc6bda9e7bd7c3d9e4ff0b04be5055c";
			//echo $sendung."\n";

			if (curl_post($sendung,[],[]))
			{
				//echo "OK.\n";
			}
			else {
				echo "Fehler im Curl bei $sendung\n";
			}		

			if (SHORT_ECHO) {echo "\n";}
		}

		// Move Datafile
		
		if (rename($loggerdir."/".$Datei, $loggerdir."/done/".$Datei)==false)
		{
			die("Kopieren misslungen von: ".$loggerdir."/".$Datei." nach: ".$loggerdir."/done/".$Datei."\n");
		}
        echo $Datei." done.\n";
		// Ausstieg wenn nicht Param 2 = all
		if ($argv[2]<>"all") 
		{
			die("File fuer File !!!\n");
		}

		echo "\n";
	}

	// END-While
}

die("All done !!!\n");

?>
