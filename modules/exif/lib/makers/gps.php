<?php defined("SYSPATH") or die("No direct script access.");
//================================================================================================
//================================================================================================
/*
	Exifer
	Extracts EXIF information from digital photos.
	
	Copyright © 2003 Jake Olefsky
	http://www.offsky.com/software/exif/index.php
	jake@olefsky.com
	
	Please see exif.php for the complete information about this software.
	
	------------
	
	This program is free software; you can redistribute it and/or modify it under the terms of 
	the GNU General Public License as published by the Free Software Foundation; either version 2 
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
	without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
	See the GNU General Public License for more details. http://www.gnu.org/copyleft/gpl.html
*/
//================================================================================================
//================================================================================================
//================================================================================================



//=================
// Looks up the name of the tag
//====================================================================
function lookup_GPS_tag($tag) {

	$tag = match ($tag) {
        "0000" => "Version",
        "0001" => "Latitude Reference",
        "0002" => "Latitude",
        "0003" => "Longitude Reference",
        "0004" => "Longitude",
        "0005" => "Altitude Reference",
        "0006" => "Altitude",
        "0007" => "Time",
        "0008" => "Satellite",
        "0009" => "ReceiveStatus",
        "000a" => "MeasurementMode",
        "000b" => "MeasurementPrecision",
        "000c" => "SpeedUnit",
        "000d" => "ReceiverSpeed",
        "000e" => "MovementDirectionRef",
        "000f" => "MovementDirection",
        "0010" => "ImageDirectionRef",
        "0011" => "ImageDirection",
        "0012" => "GeodeticSurveyData",
        "0013" => "DestLatitudeRef",
        "0014" => "DestinationLatitude",
        "0015" => "DestLongitudeRef",
        "0016" => "DestinationLongitude",
        "0017" => "DestBearingRef",
        "0018" => "DestinationBearing",
        "0019" => "DestDistanceRef",
        "001a" => "DestinationDistance",
        "001b" => "ProcessingMethod",
        "001c" => "AreaInformation",
        "001d" => "Datestamp",
        "001e" => "DifferentialCorrection",
        default => "unknown:".$tag,
    };
	
	return $tag;
}

//=================
// Formats Data for the data type
//====================================================================
function formatGPSData($type,$tag,$intel,$data) {

	if($type=="ASCII") {
		if($tag=="0001" || $tag=="0003"){ // Latitude Reference, Longitude Reference
			$data = ($data[1] == $data[2] && $data[1] == $data[3]) ? $data[0] : $data;
		}

	} else if($type=="URATIONAL" || $type=="SRATIONAL") {
		if($tag=="0002" || $tag=="0004" || $tag=='0007') { //Latitude, Longitude, Time
			$datum = [];
			for ($i=0;$i<strlen($data);$i=$i+8) {
				array_push($datum,substr($data, $i, 8));
			}
			$hour = unRational($datum[0],$type,$intel);
			$minutes = unRational($datum[1],$type,$intel);
			$seconds = unRational($datum[2],$type,$intel);
			if($tag=="0007") { //Time
				$data = $hour.":".$minutes.":".$seconds;
			} else {
				$data = $hour+$minutes/60+$seconds/3600;
			}
		} else {
			$data = unRational($data,$type,$intel);
			
			if($tag=="0006"){
				$data .= 'm';
			}
		}
	} else if($type=="USHORT" || $type=="SSHORT" || $type=="ULONG" || $type=="SLONG" || $type=="FLOAT" || $type=="DOUBLE") {
		$data = rational($data,$type,$intel);


	} else if($type=="UNDEFINED") {



	} else if($type=="UBYTE") {
		$data = bin2hex($data);
		if($intel==1) $num = intel2Moto($data);

			
		if($tag=="0000") { // VersionID
			$data =  hexdec(substr($data,0,2)) .
												".". hexdec(substr($data,2,2)) .
												".". hexdec(substr($data,4,2)) .
												".". hexdec(substr($data,6,2));

		} else if($tag=="0005"){ // Altitude Reference
			if($data == "00000000"){ $data = '+'; }
			else if($data == "01000000"){ $data = '-'; }
		}

	} else {
		$data = bin2hex($data);
		if($intel==1) $data = intel2Moto($data);
	}

	return $data;
}


//=================
// GPS Special data section
// Useful websites
// http://drewnoakes.com/code/exif/sampleOutput.html
// http://www.geosnapper.com
//====================================================================
function parseGPS($block,&$result,$offset,$seek, $globalOffset) {	
	
	if($result['Endien']=="Intel") $intel=1;
	else $intel=0;
		
	$v = fseek($seek,$globalOffset+$offset);  //offsets are from TIFF header which is 12 bytes from the start of the file
	if($v==-1) {
		$result['Errors'] = $result['Errors']++;
	}

	$num = bin2hex(fread( $seek, 2 ));
	if($intel==1) $num = intel2Moto($num);
	$num=hexdec($num);
	$result['GPS']['NumTags'] = $num;

	if ($num == 0) {
		return;
	}

	$block = fread( $seek, $num*12 );
	$place = 0;
	
	//loop thru all tags  Each field is 12 bytes
	for($i=0;$i<$num;$i++) {
			//2 byte tag
		$tag = bin2hex(substr($block,$place,2));$place+=2;
		if($intel==1) $tag = intel2Moto($tag);
		$tag_name = lookup_GPS_tag($tag);
		
		//2 byte datatype
		$type = bin2hex(substr($block,$place,2));$place+=2;
		if($intel==1) $type = intel2Moto($type);
		lookup_type($type,$size);
		
		//4 byte number of elements
		$count = bin2hex(substr($block,$place,4));$place+=4;
		if($intel==1) $count = intel2Moto($count);
		$bytesofdata = $size*hexdec($count);
		
		//4 byte value or pointer to value if larger than 4 bytes
		$value = substr($block,$place,4);$place+=4;
		if($bytesofdata<=4) {
			$data = $value;
		} else {
			if (str_contains('unknown',$tag_name) || $bytesofdata > 1024) {
				$result['Errors'] = $result['Errors']++;
				$data = '';
				$type = 'ASCII';
			} else {
				$value = bin2hex($value);
				if($intel==1) $value = intel2Moto($value);
				$v = fseek($seek,$globalOffset+hexdec($value));  //offsets are from TIFF header which is 12 bytes from the start of the file
				if($v==0) {
					$data = fread($seek, $bytesofdata);
				} else {
					$result['Errors'] = $result['Errors']++;
					$data = '';
					$type = 'ASCII';
				}
			}
		}
		if($result['VerboseOutput']==1) {
			$result['GPS'][$tag_name] = formatGPSData($type,$tag,$intel,$data);
			$result['GPS'][$tag_name."_Verbose"]['RawData'] = bin2hex($data);
			$result['GPS'][$tag_name."_Verbose"]['Type'] = $type;
			$result['GPS'][$tag_name."_Verbose"]['Bytes'] = $bytesofdata;
		} else {
			$result['GPS'][$tag_name] = formatGPSData($type,$tag,$intel,$data);
		}
	}
}


?>
