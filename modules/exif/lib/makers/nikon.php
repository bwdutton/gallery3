<?php defined("SYSPATH") or die("No direct script access.");
//================================================================================================
//================================================================================================
/*
	Exifer
	Extracts EXIF information from digital photos.
	
	Copyright � 2003 Jake Olefsky
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
function lookup_Nikon_tag($tag,$model) {
	
	if($model==0) {
		$tag = match ($tag) {
            "0003" => "Quality",
            "0004" => "ColorMode",
            "0005" => "ImageAdjustment",
            "0006" => "CCDSensitivity",
            "0007" => "WhiteBalance",
            "0008" => "Focus",
            "0009" => "Unknown2",
            "000a" => "DigitalZoom",
            "000b" => (string) t("Converter"),
            default => "unknown:".$tag,
        };
	} else if($model==1) {
		$tag = match ($tag) {
            "0002" => "ISOSetting",
            "0003" => "ColorMode",
            "0004" => "Quality",
            "0005" => "Whitebalance",
            "0006" => "ImageSharpening",
            "0007" => "FocusMode",
            "0008" => "FlashSetting",
            "0009" => "FlashMode",
            "000b" => "WhiteBalanceFine",
            "000c" => "WB_RBLevels",
            "000d" => "ProgramShift",
            "000e" => "ExposureDifference",
            "000f" => "ISOSelection",
            "0010" => "DataDump",
            "0011" => "NikonPreview",
            "0012" => "FlashExposureComp",
            "0013" => "ISOSetting2",
            "0014" => "ColorBalanceA",
            "0016" => "ImageBoundary",
            "0017" => "FlashExposureComp",
            "0018" => "FlashExposureBracketValue",
            "0019" => "ExposureBracketValue",
            "001a" => "ImageProcessing",
            "001b" => "CropHiSpeed",
            "001c" => "ExposureTuning",
            "001d" => "SerialNumber",
            "001e" => "ColorSpace",
            "001f" => "VRInfo",
            "0020" => "ImageAuthentication",
            "0022" => "ActiveD-Lighting",
            "0023" => "PictureControl",
            "0024" => "WorldTime",
            "0025" => "ISOInfo",
            "002a" => "VignetteControl",
            "002b" => "DistortInfo",
            "0080" => "ImageAdjustment",
            "0081" => "ToneCompensation",
            "0082" => "Adapter",
            "0083" => "LensType",
            "0084" => "LensInfo",
            "0085" => "ManualFocusDistance",
            "0086" => "DigitalZoom",
            "0087" => "FlashUsed",
            "0088" => "AFFocusPosition",
            "0089" => "ShootingMode",
            "008b" => "LensFStops",
            "008c" => "ContrastCurve",
            "008d" => "ColorMode",
            "0090" => "LightType",
            "0092" => "HueAdjustment",
            "0093" => "NEFCompression",
            "0094" => "Saturation",
            "0095" => "NoiseReduction",
            "009a" => "SensorPixelSize",
            default => "unknown:".$tag,
        };
	} 
	
	return $tag;
}


//=================
// Formats Data for the data type
//====================================================================
function formatNikonData($type,$tag,$intel,$model,$data) {
	switch ($type) {
		case "ASCII":
			break;	// do nothing!
		case "URATIONAL":
		case "SRATIONAL":
			switch ($tag) {
				case '0084':	// LensInfo
					$minFL = unRational(substr($data,0,8),$type,$intel);
					$maxFL = unRational(substr($data,8,8),$type,$intel);
					$minSP = unRational(substr($data,16,8),$type,$intel);
					$maxSP = unRational(substr($data,24,8),$type,$intel);
					if ($minFL == $maxFL) {
						$data = sprintf('%0.0f f/%0.0f',$minFL,$minSP);
					} elseif ($minSP == $maxSP) {
						$data = sprintf('%0.0f-%0.0fmm f/%0.1f',$minFL,$maxFL,$minSP);
					} else {
						$data = sprintf('%0.0f-%0.0fmm f/%0.1f-%0.1f',$minFL,$maxFL,$minSP,$maxSP);
					}
					break;
				case "0085":
					if ($model==1) $data=unRational($data,$type,$intel)." m";	//ManualFocusDistance
					break;
				case "0086":
					if ($model==1) $data=unRational($data,$type,$intel)."x";	//DigitalZoom
					break;
				case "000a":
					if ($model==0) $data=unRational($data,$type,$intel)."x";	//DigitalZoom
					break;
				default:
					$data=unRational($data,$type,$intel);
					break;
			}
			break;
		case "USHORT":
		case $type=="SSHORT":
		case $type=="ULONG":
		case $type=="SLONG":
		case $type=="FLOAT":
		case $type=="DOUBLE":
			$data = rational($data,$type,$intel);
			switch ($tag) {
				case "0003":
					if ($model==0) { //Quality
						$data = match ($data) {
                            1 => (string) t("VGA Basic"),
                            2 => (string) t("VGA Normal"),
                            3 => (string) t("VGA Fine"),
                            4 => (string) t("SXGA Basic"),
                            5 => (string) t("SXGA Normal"),
                            6 => (string) t("SXGA Fine"),
                            default => (string) t("Unknown").": ".$data,
                        };
					}
					break;
				case "0004":
					if ($model==0) { //Color
						$data = match ($data) {
                            1 => (string) t("Color"),
                            2 => (string) t("Monochrome"),
                            default => (string) t("Unknown").": ".$data,
                        };
					}
					break;
				case "0005":
					if ($model==0) { //Image Adjustment
						$data = match ($data) {
                            0 => (string) t("Normal"),
                            1 => (string) t("Bright+"),
                            2 => (string) t("Bright-"),
                            3 => (string) t("Contrast+"),
                            4 => (string) t("Contrast-"),
                            default => (string) t("Unknown").": ".$data,
                        };
					}
					break;
				case "0006":
					if ($model==0) { //CCD Sensitivity
						$data = match ($data) {
                            0 => "ISO-80",
                            2 => "ISO-160",
                            4 => "ISO-320",
                            5 => "ISO-100",
                            default => (string) t("Unknown").": ".$data,
                        };
					}
					break;
				case "0007":
					if ($model==0) { //White Balance
						$data = match ($data) {
                            0 => (string) t("Auto"),
                            1 => (string) t("Preset"),
                            2 => (string) t("Daylight"),
                            3 => (string) t("Incandescence"),
                            4 => (string) t("Fluorescence"),
                            5 => (string) t("Cloudy"),
                            6 => (string) t("SpeedLight"),
                            default => (string) t("Unknown").": ".$data,
                        };
					}
					break;
				case "000b":
					if ($model==0) { //Converter
						$data = match ($data) {
                            0 => (string) t("None"),
                            1 => (string) t("Fisheye"),
                            default => (string) t("Unknown").": ".$data,
                        };
					}
					break;
			}
			break;
		case "UNDEFINED":
			switch ($tag) {
				case "0001":
					if ($model==1) $data=(int)$data/100;	break;	//Unknown (Version?)
					break;
				case "0088":
					if ($model==1) { //AF Focus Position
						$temp = (string) t("Center");
						$data = bin2hex($data);
						$data = str_replace("01","Top",$data);
						$data = str_replace("02","Bottom",$data);
						$data = str_replace("03","Left",$data);
						$data = str_replace("04","Right",$data);
						$data = str_replace("00","",$data);
						if(strlen($data)==0) $data = $temp;
					}
					break;
			}
			break;
		default:
			$data = bin2hex($data);
			if($intel==1) $data = intel2Moto($data);
				switch ($tag) {
				case "0083":
					if ($model==1) { //Lens Type
						$data = hexdec(substr($data,0,2));
						$data = match ($data) {
                            0 => (string) t("AF non D"),
                            1 => (string) t("Manual"),
                            2 => "AF-D or AF-S",
                            6 => "AF-D G",
                            10 => "AF-D VR",
                            14 => "AF-D G VR",
                            default => (string) t("Unknown").": ".$data,
                        };
					}
					break;
				case "0087":
					if ($model==1) { //Flash type
						$data = hexdec(substr($data,0,2));
						if($data == 0) $data = (string) t("Did Not Fire");
						else if($data == 4) $data = (string) t("Unknown");
						else if($data == 7) $data = (string) t("External");
						else if($data == 9) $data = (string) t("On Camera");
						else $data = (string) t("Unknown").": ".$data;
					}
					break;
			}
			break;
	}
	return $data;
}


//=================
// Nikon Special data section
//====================================================================
function parseNikon($block,&$result) {	
	
	if($result['Endien']=="Intel") $intel=1;
	else $intel=0;
	
	$model = $result['IFD0']['Model'];

	//these 6 models start with "Nikon".  Other models dont.
	if($model=="E700\0" || $model=="E800\0" || $model=="E900\0" || $model=="E900S\0" || $model=="E910\0" || $model=="E950\0") {
		$place=8; //current place
		$model = 0;
		
		//Get number of tags (2 bytes)
		$num = bin2hex(substr($block,$place,2));$place+=2;
		if($intel==1) $num = intel2Moto($num);
		$result['SubIFD']['MakerNote']['MakerNoteNumTags'] = hexdec($num);
		
		//loop thru all tags  Each field is 12 bytes
		for($i=0;$i<hexdec($num);$i++) {
			//2 byte tag
			$tag = bin2hex(substr($block,$place,2));$place+=2;
			if($intel==1) $tag = intel2Moto($tag);
			$tag_name = lookup_Nikon_tag($tag, $model);
			
			//2 byte type
			$type = bin2hex(substr($block,$place,2));$place+=2;
			if($intel==1) $type = intel2Moto($type);
			lookup_type($type,$size);
			
			//4 byte count of number of data units
			$count = bin2hex(substr($block,$place,4));$place+=4;
			if($intel==1) $count = intel2Moto($count);
			$bytesofdata = $size*hexdec($count);
			
			//4 byte value of data or pointer to data
			$value = substr($block,$place,4);$place+=4;
			
			//if tag is 0002 then its the ASCII value which we know is at 140 so calc offset
			//THIS HACK ONLY WORKS WITH EARLY NIKON MODELS
			if($tag=="0002") $offset = hexdec($value)-140;
			if($bytesofdata<=4) {
				$data = $value;
			} else {
				$value = bin2hex($value);
				if($intel==1) $value = intel2Moto($value);
				$data = substr($block,hexdec($value)-$offset,$bytesofdata*2);
			}
			$formated_data = formatNikonData($type,$tag,$intel,$model,$data);
		
			if($result['VerboseOutput']==1) {
				$result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
				$result['SubIFD']['MakerNote'][$tag_name."_Verbose"]['RawData'] = $data;
				$result['SubIFD']['MakerNote'][$tag_name."_Verbose"]['Type'] = $type;
				$result['SubIFD']['MakerNote'][$tag_name."_Verbose"]['Bytes'] = $bytesofdata;
			} else {
				$result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
			}
		}
	
	} else {
		$place=0;//current place
		$model = 1;
		
		$nikon = substr($block,$place,8);$place+=8;
		$endien = substr($block,$place,4);$place+=4;
		
		//2 bytes of 0x002a
		$tag = bin2hex(substr($block,$place,2));$place+=2;
		
		//Then 4 bytes of offset to IFD0 (usually 8 which includes all 8 bytes of TIFF header)
		$offset = bin2hex(substr($block,$place,4));$place+=4;
		if($intel==1) $offset = intel2Moto($offset);
		if(hexdec($offset)>8) $place+=$offset-8;
		
		//Get number of tags (2 bytes)
		$num = bin2hex(substr($block,$place,2));$place+=2;
		if($intel==1) $num = intel2Moto($num);
		
		//loop thru all tags  Each field is 12 bytes
		for($i=0;$i<hexdec($num);$i++) {
			//2 byte tag
			$tag = bin2hex(substr($block,$place,2));$place+=2;
			if($intel==1) $tag = intel2Moto($tag);
			$tag_name = lookup_Nikon_tag($tag, $model);
			
			//2 byte type
			$type = bin2hex(substr($block,$place,2));$place+=2;
			if($intel==1) $type = intel2Moto($type);
			lookup_type($type,$size);
			
			//4 byte count of number of data units
			$count = bin2hex(substr($block,$place,4));$place+=4;
			if($intel==1) $count = intel2Moto($count);
			$bytesofdata = $size*hexdec($count);
			
			//4 byte value of data or pointer to data
			$value = substr($block,$place,4);$place+=4;
			
			if($bytesofdata<=4) {
				$data = $value;
			} else {
				$value = bin2hex($value);
				if($intel==1) $value = intel2Moto($value);
				$data = substr($block,hexdec($value)+hexdec($offset)+2,$bytesofdata);
			}
			$formated_data = formatNikonData($type,$tag,$intel,$model,$data);
		
			if($result['VerboseOutput']==1) {
				$result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
				if($type=="URATIONAL" || $type=="SRATIONAL" || $type=="USHORT" || $type=="SSHORT" || $type=="ULONG" || $type=="SLONG" || $type=="FLOAT" || $type=="DOUBLE") {
					$data = bin2hex($data);
					if($intel==1) $data = intel2Moto($data);
				}
				$result['SubIFD']['MakerNote'][$tag_name."_Verbose"]['RawData'] = $data;
				$result['SubIFD']['MakerNote'][$tag_name."_Verbose"]['Type'] = $type;
				$result['SubIFD']['MakerNote'][$tag_name."_Verbose"]['Bytes'] = $bytesofdata;
			} else {
				$result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
			}
		}
		
	}
}


?>
