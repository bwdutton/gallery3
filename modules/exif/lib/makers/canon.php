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
// Looks up the name of the tag for the MakerNote (Depends on Manufacturer)
//====================================================================
function lookup_Canon_tag($tag) {
	
	$tag = match ($tag) {
        "0001" => "Settings 1",
        "0004" => "Settings 4",
        "0006" => "ImageType",
        "0007" => "FirmwareVersion",
        "0008" => "ImageNumber",
        "0009" => "OwnerName",
        "000c" => "CameraSerialNumber",
        "000f" => "CustomFunctions",
        "0095" => "LensInfo",
        default => "unknown:".$tag,
    };
	
	return $tag;
}

//=================
// Formats Data for the data type
//====================================================================
function formatCanonData($type,$tag,$intel,$data,$exif,&$result) {
	$place = 0;
	
	
	if($type=="ASCII") {
		$result = $data = str_replace("\0", "", $data);
	} else if($type=="URATIONAL" || $type=="SRATIONAL") {
		$data = unRational($data,$type,$intel);
	
		if($tag=="0204") { //DigitalZoom
			$data=$data."x";
		} 
		
	} else if($type=="USHORT" || $type=="SSHORT" || $type=="ULONG" || $type=="SLONG" || $type=="FLOAT" || $type=="DOUBLE") {
		
		$data = rational($data,$type,$intel);
		$result['RAWDATA'] = $data;
	
		if($tag=="0001") { //first chunk
			$result['Bytes']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//0
			if ($result['Bytes'] != strlen($data) / 2) return $result; //Bad chunk
			$result['Macro']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//1
			$result['Macro'] = match ($result['Macro']) {
                1 => (string) t("Macro"),
                2 => (string) t("Normal"),
                default => (string) t("Unknown"),
            };
			$result['SelfTimer']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//2
			switch($result['SelfTimer']) {
				case 0: $result['SelfTimer'] = (string) t("Off"); break;
				default: $result['SelfTimer'] .= (string) t("/10s");
			}
			$result['Quality']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//3
			$result['Quality'] = match ($result['Quality']) {
                2 => (string) t("Normal"),
                3 => (string) t("Fine"),
                5 => (string) t("Superfine"),
                default => (string) t("Unknown"),
            };
			$result['Flash']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//4
			$result['Flash'] = match ($result['Flash']) {
                0 => (string) t("Off"),
                1 => (string) t("Auto"),
                2 => (string) t("On"),
                3 => (string) t("Red Eye Reduction"),
                4 => (string) t("Slow Synchro"),
                5 => (string) t("Auto + Red Eye Reduction"),
                6 => (string) t("On + Red Eye Reduction"),
                16 => (string) t("External Flash"),
                default => (string) t("Unknown"),
            };
			$result['DriveMode']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//5
			$result['DriveMode'] = match ($result['DriveMode']) {
                0 => (string) t("Single/Timer"),
                1 => (string) t("Continuous"),
                default => (string) t("Unknown"),
            };
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//6
			$result['FocusMode']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//7
			$result['FocusMode'] = match ($result['FocusMode']) {
                0 => (string) t("One-Shot"),
                1 => (string) t("AI Servo"),
                2 => (string) t("AI Focus"),
                3 => (string) t("Manual Focus"),
                4 => (string) t("Single"),
                5 => (string) t("Continuous"),
                6 => (string) t("Manual Focus"),
                default => (string) t("Unknown"),
            };
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//8
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//9
			$result['ImageSize']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//10
			$result['ImageSize'] = match ($result['ImageSize']) {
                0 => (string) t("Large"),
                1 => (string) t("Medium"),
                2 => (string) t("Small"),
                default => (string) t("Unknown"),
            };
			$result['EasyShooting']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//11
			$result['EasyShooting'] = match ($result['EasyShooting']) {
                0 => (string) t("Full Auto"),
                1 => (string) t("Manual"),
                2 => (string) t("Landscape"),
                3 => (string) t("Fast Shutter"),
                4 => (string) t("Slow Shutter"),
                5 => (string) t("Night"),
                6 => (string) t("Black & White"),
                7 => (string) t("Sepia"),
                8 => (string) t("Portrait"),
                9 => (string) t("Sport"),
                10 => (string) t("Macro/Close-Up"),
                11 => (string) t("Pan Focus"),
                default => (string) t("Unknown"),
            };
			$result['DigitalZoom']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//12
			$result['DigitalZoom'] = match ($result['DigitalZoom']) {
                0, 65535 => (string) t("None"),
                1 => (string) t("2x"),
                2 => (string) t("4x"),
                default => (string) t("Unknown"),
            };
			$result['Contrast']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//13
			$result['Contrast'] = match ($result['Contrast']) {
                0 => (string) t("Normal"),
                1 => (string) t("High"),
                65535 => (string) t("Low"),
                default => (string) t("Unknown"),
            };
			$result['Saturation']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//14
			$result['Saturation'] = match ($result['Saturation']) {
                0 => (string) t("Normal"),
                1 => (string) t("High"),
                65535 => (string) t("Low"),
                default => (string) t("Unknown"),
            };
			$result['Sharpness']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//15
			$result['Sharpness'] = match ($result['Sharpness']) {
                0 => (string) t("Normal"),
                1 => (string) t("High"),
                65535 => (string) t("Low"),
                default => (string) t("Unknown"),
            };
			$result['ISO']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//16
			$result['ISO'] = match ($result['ISO']) {
                32767, 0 => $exif['SubIFD']['ISOSpeedRatings'] ?? 'Unknown',
                15 => (string) t("Auto"),
                16 => (string) t("50"),
                17 => (string) t("100"),
                18 => (string) t("200"),
                19 => (string) t("400"),
                default => (string) t("Unknown"),
            };
			$result['MeteringMode']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//17
			$result['MeteringMode'] = match ($result['MeteringMode']) {
                3 => (string) t("Evaluative"),
                4 => (string) t("Partial"),
                5 => (string) t("Center-weighted"),
                default => (string) t("Unknown"),
            };
			$result['FocusType']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//18
			$result['FocusType'] = match ($result['FocusType']) {
                0 => (string) t("Manual"),
                1 => (string) t("Auto"),
                3 => (string) t("Close-up (Macro)"),
                8 => (string) t("Locked (Pan Mode)"),
                default => (string) t("Unknown"),
            };
			$result['AFPointSelected']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//19
			$result['AFPointSelected'] = match ($result['AFPointSelected']) {
                12288 => (string) t("Manual Focus"),
                12289 => (string) t("Auto Selected"),
                12290 => (string) t("Right"),
                12291 => (string) t("Center"),
                12292 => (string) t("Left"),
                default => (string) t("Unknown"),
            };
			$result['ExposureMode']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//20
			$result['ExposureMode'] = match ($result['ExposureMode']) {
                0 => (string) t("EasyShoot"),
                1 => (string) t("Program"),
                2 => (string) t("Tv"),
                3 => (string) t("Av"),
                4 => (string) t("Manual"),
                5 => (string) t("Auto-DEP"),
                default => (string) t("Unknown"),
            };
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//21
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//22
			$result['LongFocalLength']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//23
				$result['LongFocalLength'] .= " focal units";
			$result['ShortFocalLength']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//24
				$result['ShortFocalLength'] .= " focal units";
			$result['FocalUnits']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//25
				$result['FocalUnits'] .= " per mm";
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//26
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//27
			$result['FlashActivity']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//28
			$result['FlashActivity'] = match ($result['FlashActivity']) {
                0 => (string) t("Flash Did Not Fire"),
                1 => (string) t("Flash Fired"),
                default => (string) t("Unknown"),
            };
			$result['FlashDetails']=str_pad(base_convert(intel2Moto(substr($data,$place,4)), 16, 2), 16, "0", STR_PAD_LEFT);$place+=4;//29
				$flashDetails = [];
				if (substr($result['FlashDetails'], 1, 1) == 1) { $flashDetails[] = (string) t('External E-TTL'); }
				if (substr($result['FlashDetails'], 2, 1) == 1) { $flashDetails[] = (string) t('Internal Flash'); }
				if (substr($result['FlashDetails'], 4, 1) == 1) { $flashDetails[] = (string) t('FP sync used'); }
				if (substr($result['FlashDetails'], 8, 1) == 1) { $flashDetails[] = (string) t('2nd(rear)-curtain sync used'); }
				if (substr($result['FlashDetails'], 12, 1) == 1) { $flashDetails[] = (string) t('1st curtain sync'); }
				$result['FlashDetails']=implode(",", $flashDetails);
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//30
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//31
			$anotherFocusMode=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//32
			if(str_contains(strtoupper($exif['IFD0']['Model']), "G1")) {
				$result['FocusMode'] = match ($anotherFocusMode) {
                    0 => (string) t("Single"),
                    1 => (string) t("Continuous"),
                    default => (string) t("Unknown"),
                };
			}
			
		} else if($tag=="0004") { //second chunk
			$result['Bytes']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//0
			if ($result['Bytes'] != strlen($data) / 2) return $result; //Bad chunk
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//1
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//2
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//3
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//4
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//5
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//6
			$result['WhiteBalance']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//7
			$result['WhiteBalance'] = match ($result['WhiteBalance']) {
                0 => (string) t("Auto"),
                1 => (string) t("Sunny"),
                2 => (string) t("Cloudy"),
                3 => (string) t("Tungsten"),
                4 => (string) t("Fluorescent"),
                5 => (string) t("Flash"),
                6 => (string) t("Custom"),
                default => (string) t("Unknown"),
            };
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//8
			$result['SequenceNumber']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//9
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//10
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//11
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//12
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//13
			$result['AFPointUsed']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//14
				$afPointUsed = [];
				if ($result['AFPointUsed'] & 0x0001) $afPointUsed[] = (string) t("Right"); //bit 0
				if ($result['AFPointUsed'] & 0x0002) $afPointUsed[] = (string) t("Center"); //bit 1
				if ($result['AFPointUsed'] & 0x0004) $afPointUsed[] = (string) t("Left"); //bit 2
				if ($result['AFPointUsed'] & 0x0800) $afPointUsed[] = (string) t("12"); //bit 12
				if ($result['AFPointUsed'] & 0x1000) $afPointUsed[] = (string) t("13"); //bit 13
				if ($result['AFPointUsed'] & 0x2000) $afPointUsed[] = (string) t("14"); //bit 14
				if ($result['AFPointUsed'] & 0x4000) $afPointUsed[] = (string) t("15"); //bit 15
				$result['AFPointUsed'] = implode(",", $afPointUsed);
			$result['FlashBias']=intel2Moto(substr($data,$place,4));$place+=4;//15
			$result['FlashBias'] = match ($result['FlashBias']) {
                'ffc0' => "-2 EV",
                'ffcc' => "-1.67 EV",
                'ffd0' => "-1.5 EV",
                'ffd4' => "-1.33 EV",
                'ffe0' => "-1 EV",
                'ffec' => "-0.67 EV",
                'fff0' => "-0.5 EV",
                'fff4' => "-0.33 EV",
                '0000' => "0 EV",
                '000c' => "0.33 EV",
                '0010' => "0.5 EV",
                '0014' => "0.67 EV",
                '0020' => "1 EV",
                '002c' => "1.33 EV",
                '0030' => "1.5 EV",
                '0034' => "1.67 EV",
                '0040' => "2 EV",
                default => (string) t("Unknown"),
            };
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//16
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//17
			$result['Unknown']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//18
			$result['SubjectDistance']=hexdec(intel2Moto(substr($data,$place,4)));$place+=4;//19
				$result['SubjectDistance'] .= "/100 m";
			
		} else if($tag=="0008") { //image number
			if($intel==1) $data = intel2Moto($data);
			$data=hexdec($data);
			$result = round($data/10000)."-".$data%10000;
		} else if($tag=="000c") { //camera serial number
			if($intel==1) $data = intel2Moto($data);
			$data=hexdec($data);
			$result = "#".bin2hex(substr($data,0,16)).substr($data,16,16);
		}
		
	} else if($type=="UNDEFINED") {
		
	
		
	} else {
		$data = bin2hex($data);
		if($intel==1) $data = intel2Moto($data);
	}
	
	return $data;
}



//=================
// Cannon Special data section
// Useful:  http://www.burren.cx/david/canon.html
// http://www.burren.cx/david/canon.html
// http://www.ozhiker.com/electronics/pjmt/jpeg_info/canon_mn.html
//====================================================================
function parseCanon($block,&$result,$seek, $globalOffset) {	
	$place = 0; //current place
		
	if($result['Endien']=="Intel") $intel=1;
	else $intel=0;
	
	$model = $result['IFD0']['Model'];
	
		//Get number of tags (2 bytes)
	$num = bin2hex(substr($block,$place,2));$place+=2;
	if($intel==1) $num = intel2Moto($num);
	$result['SubIFD']['MakerNote']['MakerNoteNumTags'] = hexdec($num);
	
	//loop thru all tags  Each field is 12 bytes
	for($i=0;$i<hexdec($num);$i++) {
		
			//2 byte tag
		$tag = bin2hex(substr($block,$place,2));$place+=2;
		if($intel==1) $tag = intel2Moto($tag);
		$tag_name = lookup_Canon_tag($tag);
		
			//2 byte type
		$type = bin2hex(substr($block,$place,2));$place+=2;
		if($intel==1) $type = intel2Moto($type);
		lookup_type($type,$size);		
		
			//4 byte count of number of data units
		$count = bin2hex(substr($block,$place,4));$place+=4;
		if($intel==1) $count = intel2Moto($count);
		$bytesofdata = $size*hexdec($count);
		if($bytesofdata<=0) {
			return; //if this value is 0 or less then we have read all the tags we can
		}
		
			//4 byte value of data or pointer to data
		$value = substr($block,$place,4);$place+=4;
		if($bytesofdata<=4) {
			$data = $value;
		} else {
			$value = bin2hex($value);
			if($intel==1) $value = intel2Moto($value);
			$v = fseek($seek,$globalOffset+hexdec($value));  //offsets are from TIFF header which is 12 bytes from the start of the file
			if (isset($GLOBALS['exiferFileSize'])) {
				$exiferFileSize = $GLOBALS['exiferFileSize'];
			} else {
				$exiferFileSize = 0;
			}
			if($v==0 && $bytesofdata < $exiferFileSize) {
				$data = fread($seek, $bytesofdata);
			} else if($v==-1) {
				$result['Errors'] = $result['Errors']++;
				$data = '';
			} else {
				$data = '';
			}
		}
		$result['SubIFD']['MakerNote'][$tag_name] = []; // insure the index exists
		$formated_data = formatCanonData($type,$tag,$intel,$data,$result,$result['SubIFD']['MakerNote'][$tag_name]);
		
		if($result['VerboseOutput']==1) {
			//$result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
			if($type=="URATIONAL" || $type=="SRATIONAL" || $type=="USHORT" || $type=="SSHORT" || $type=="ULONG" || $type=="SLONG" || $type=="FLOAT" || $type=="DOUBLE") {
				$data = bin2hex($data);
				if($intel==1) $data = intel2Moto($data);
			}
			$result['SubIFD']['MakerNote'][$tag_name."_Verbose"]['RawData'] = $data;
			$result['SubIFD']['MakerNote'][$tag_name."_Verbose"]['Type'] = $type;
			$result['SubIFD']['MakerNote'][$tag_name."_Verbose"]['Bytes'] = $bytesofdata;
		} else {
			//$result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
		}
	}
}


?>
