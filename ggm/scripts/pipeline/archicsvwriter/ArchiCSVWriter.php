<?php

/**
 * Class general csvArchiWriter
 */
class ArchiCSVWriter {

	/**
	 * Put a data array into a csv-file
	 *
	 * @param array $csvContents
	 * @return false|string
	 */
	public static function createCsvFileFromArray( array $csvContents ) {
		$n = 0;
		$tmpFile = tempnam( sys_get_temp_dir(), uniqid() . '.csv' );
		$csvFile = fopen( $tmpFile, 'w' );
		$csvContents = array_unique($csvContents, SORT_REGULAR);
		foreach ( $csvContents as $csvLine ) {
			// first we get the headers only once (in this case the first line), the rest is the same
			if ( $n == 0 ) {
				fputcsv( $csvFile, array_map( static fn($item) => '"' . $item . '"', array_keys( $csvLine ) ) );
			}
	//		fputcsv( $csvFile, array_values( $csvLine ), ",", '"' );
			fputcsv($csvFile, array_map( static fn($item) => '"' . $item . '"', array_values( $csvLine ) ) );

			$n++;
		}

		fclose( $csvFile );
		file_put_contents( $tmpFile, str_replace( '"""', '"', self::file_get_contents_utf8( $tmpFile ) ) );
		return $tmpFile;
	}

	private static function file_get_contents_utf8($fn) {
		$content = file_get_contents($fn);
		return mb_convert_encoding($content, 'UTF-8',
			mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
	}


	/**
	 * write the data to downloadable zip
	 */
	public  static function writeData( $archiElementsData, $archiPropertiesData, $archiRelationsData, $archiReplacementsData, $target, $listOfMessages = null ) {

		$propertiesFile = self::createCsvFileFromArray( $archiPropertiesData );
		$relationsFile = self::createCsvFileFromArray( $archiRelationsData );
		$elementsFile = self::createCsvFileFromArray( $archiElementsData );
		if (!is_null($listOfMessages)) {
			$messagesFile = self::createCsvFileFromArray( $listOfMessages );
		}
		if (!is_null($archiReplacementsData)) {
			$replacementsFile = self::createCsvFileFromArray( $archiReplacementsData );
		}



		$zip = new ZipArchive(); // Load zip library
		$zip_name = tempnam( sys_get_temp_dir(), uniqid() . '.zip' );
		if ( $zip->open( $zip_name, ZIPARCHIVE::CREATE ) ) {
			if ( !$zip->addFile( $elementsFile, 'elements.csv' ) ) {
				throw new \Exception( "Couldn't add ZIP file" );
			}
			if ( !$zip->addFile( $propertiesFile, 'properties.csv' ) ) {
				throw new \Exception( "Couldn't add ZIP file" );
			}
			if ( !$zip->addFile( $relationsFile, 'relations.csv' ) ) {
				throw new \Exception( "Couldn't add ZIP file" );
			}
			if (!is_null($listOfMessages)) {
				if ( !$zip->addFile( $messagesFile, 'messages.csv' ) ) {
					throw new \Exception( "Couldn't add ZIP file" );
				}
			}
			if (!is_null($archiReplacementsData)) {
				if ( !$zip->addFile( $replacementsFile, 'replacements.csv' ) ) {
					throw new \Exception( "Couldn't add ZIP file" );
				}
			}

		}
		$zip->close();
		$fname = 'ArchiCSVfiles_' . date('m-d-Y_H:i:s') . $target . '.zip';
		header( 'Content-type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $fname . '"' );
		readfile( $zip_name );

		// Remove files once they are downloaded
		unlink( $zip_name );
		unlink( $elementsFile );
		unlink( $propertiesFile );
		unlink( $relationsFile );
		if (!is_null($listOfMessages)) {
			unlink( $messagesFile );
		}

	}

	/**
	 * write the data to downloadable zip
	 */
	public  static function writeData2GGM( $ggmElementsData, $ggmRelationsData, $target = '', $listOfMessages = null ) {

		$elementsFile = self::createCsvFileFromArray( $ggmElementsData );
		$relationsFile = self::createCsvFileFromArray( $ggmRelationsData );
		if (!is_null($listOfMessages)) {
			$messagesFile = self::createCsvFileFromArray( $listOfMessages );
		}


		$zip = new ZipArchive(); // Load zip library
		$zip_name = tempnam( sys_get_temp_dir(), uniqid() . '.zip' );
		if ( $zip->open( $zip_name, ZIPARCHIVE::CREATE ) ) {
			if ( !$zip->addFile( $elementsFile, 'GGM-elements.csv' ) ) {
				throw new \Exception( "Couldn't add ZIP file" );
			}
			if ( !$zip->addFile( $relationsFile, 'GGM-relations.csv' ) ) {
				throw new \Exception( "Couldn't add ZIP file" );
			}
			if (!is_null($listOfMessages)) {
				if ( !$zip->addFile( $messagesFile, 'messages.csv' ) ) {
					throw new \Exception( "Couldn't add ZIP file" );
				}
			}
		}
		$zip->close();
		$fname = 'GGM-CSVfiles_' . date('m-d-Y_H:i:s') . $target . '.zip';
		header( 'Content-type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $fname . '"' );
		readfile( $zip_name );

		// Remove files once they are downloaded
		unlink( $zip_name );
		unlink( $elementsFile );
		unlink( $relationsFile );
		unlink( $messagesFile );

	}

	public static function dumpDataOnScreen ( $data ) {
		echo '<pre>';
		var_dump ($data);
		die;
	}

	/**
	 * Test data by sending to the screen
	 */
	public static function showOnScreen ( $listOfElements, $listOfProperties = null, $listOfRelations) {

		echo "<style type=\"text/css\">

			table {
				border: 1px black solid;
				width: 100%;
  			}
  			
  			table tr {
    			border: 1px solid black;
			}
			table td, table th {
    			border: 1px solid black;
			}
			tbody > tr:nth-child(odd) { background-color: #eee; }
    		tbody > tr:nth-child(even) { background-color: white; }

		</style>";
		echo "<h2>ELEMENTEN</h2>";
		echo "aantal elementen: " . count($listOfElements);
		echo "<br/>";
		//echo "<table style='border:1px solid;'>";
		echo "<table>";
		echo "<tr><th>ID</th><th>Type</th><th>Name</th><th>Documentation</th><th>Specialization</th></tr>";
		echo "<br/>";

		$checklist = array();
		foreach ( $listOfElements as $properties ) {
			if (!empty($properties["ID"])) {
				echo '<tr>';
				echo "<td>" . $properties["ID"] . '</td>' .
					"<td>" . (!empty($properties["Type"])?$properties["Type"]:"<span style=\"color:red\">No Type</span>") .  '</td>' .
					"<td>" . (!empty($properties["Name"])?$properties["Name"]:"<span style=\"color:red\">No Name</span>") .  '</td>' .
					"<td>" . (!empty($properties["Documentation"])?$properties["Documentation"]:"<span style=\"color:red\">No Documentation</span>")  . "," . '</td>' .
					"<td>" . (!empty($properties["Specialization"])?$properties["Specialization"]:"<span style=\"color:red\">No Specialization</span>") . '</td>' ;
				echo '</tr>';
				$checklist[] = $properties["ID"];
			}
		}
		echo "</table>";

		if (!is_null($listOfProperties)) {
			echo "<h2>PROPERTIES</h2>";
			echo "aantal properties: " . count( $listOfProperties );
			echo "<br/>";
			echo "<table>";
			echo "<tr><th>ID</th><th>Key</th><th>Value</th></tr>";
			echo "<br/>";
			foreach ( $listOfProperties as $properties ) {
//			echo '<pre>';
//			var_dump( $listOfElements);
//			die;
				if ( !empty( $properties["ID"] ) && !empty( $properties["Key"] ) && !empty( $properties["Value"] ) ) {
					$fontColor = array_search( $properties["ID"], $checklist ) ? "black" : "red";
					echo '<tr>';
					echo "<td> <span style=\"$fontColor;\">" . $properties["ID"] . '</span></td>' . "<td>" . ( !empty( $properties["Key"] ) ? $properties["Key"] : "<span style=\"color:red\">No Key</span>" ) . '</td>' . "<td>" . ( !empty( $properties["Value"] ) ? $properties["Value"] : "<span style=\"color:red\">No Value</span>" ) . '</td>' . "</tr>";
				}
			}
			echo "</table>";
		}
		echo "<table>";
		echo "<h2>RELATIONS</h2>";
		echo "aantal relations: " . count($listOfRelations);
		echo "<br/>";
		echo "<table>";
		echo "<tr><th>ID</th><th>Type</th><th>Name</th><th>Documentation</th><th>Source</th><th>Target</th><th>Specialization</th><tr>";
		echo "<br/>";
//		var_dump($checklist);
//		die;
		foreach ( $listOfRelations as $properties ) {
//			if (array_search( $properties["Source"], $checklist	)) {
//				echo $properties["Source"] . " Bestaat";
//			} else {
//				echo $properties["Source"] . " Bestaat niet";
//			}
//			echo "<br/>";
			$fontColorSource = array_search( $properties["Source"], $checklist ) ? "black" : "red";
			$fontColorTarget = array_search( $properties["Target"], $checklist ) ? "black" : "red";
			if (!empty($properties["ID"]) && !empty($properties["Type"]) && !empty($properties["Source"]) && !empty($properties["Target"]) ) {
				echo '<tr>';
				echo  "<td>" . $properties["ID"] . '</td>' .
					"<td>" . (!empty($properties["Type"])?$properties["Type"]:"<span style=\"color:red\">No Type</span>") .  '</td>' .
					"<td>" . (!empty($properties["Name"])?$properties["Name"]:"<span style=\"color:red\">No Name</span>").  '</td>' .
					"<td>" . (!empty($properties["Documentation"])?$properties["Documentation"]:"<span style=\"color:red\">No Documentation</span>")  .  '</td>' .
					"<td>" . (!empty($properties["Source"])
						?"<span style=\"color:$fontColorSource\">". $properties["Source"] . "</span>"
						:"<span style=\"color:red\">No Source</span>") .  '</td>' .
					"<td>" . (!empty($properties["Target"])
						?"<span style=\"color:$fontColorTarget\">". $properties["Target"] . "</span>"
						:"<span style=\"color:red\">No Target</span>") .  '</td>' .
					"<td>" . (!empty($properties["Specialization"])?$properties["Specialization"]:"<span style=\"color:red\">No Specialization</span>") .  '</td>' .
					"</tr>";
			}
		}
		echo "</table>";
	}
	private static function arraySearchSafe ( string $value, string $column, array $array ): ?int {
		// Array search gaat niet kapot als de kolom niet bestaat,
		// dus let daar op.
		$index = array_search( $value, array_column( $array, $column ) );

		// We hebben alleen een valide index, als de kolom bestaat en ook daadwerkelijk
		// dezelfde value heeft als we verwachten
		if ( $index && array_key_exists( $column, $array[ $index ] ) && $array[$index][$column] === $value ) {
			return $index;
		}

		foreach ( $array as $index => $item ) {
			// If the column doesn't exist, skip this item
			if ( !array_key_exists( $column, $item ) ) {
				continue;
			}

			if ( $item[ $column ] === $value ) {
				return $index;
			}
		}
		return null;
	}
}