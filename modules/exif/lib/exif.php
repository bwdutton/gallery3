<?php defined("SYSPATH") or die("No direct script access.");
/*
	Exifer 1.6
	Extracts EXIF information from digital photos.
	
	Originally created by:
	Copyright © 2005 Jake Olefsky
	http:// www.offsky.com/software/exif/index.php
	jake@olefsky.com
	
	This program is free software; you can redistribute it and/or modify it under the terms of 
	the GNU General Public License as published by the Free Software Foundation; either version 2 
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
	without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
	See the GNU General Public License for more details. http:// www.gnu.org/copyleft/gpl.html
	
	SUMMARY:
				This script will correctly parse all of the EXIF data included in images taken
				with digital cameras.  It will read the IDF0, IDF1, SubIDF and InteroperabilityIFD
				fields as well as parsing some of the MakerNote fields that vary depending on
				camera make and model.  This script parses more tags than the internal PHP exif
				implementation and it will correctly identify and decode what all the values mean.
				
				This version will correctly parse the MakerNote field for Nikon, Olympus, and Canon 
				digital cameras.  Others will follow.
				
	TESTED WITH:
				Nikon CoolPix 700
				Nikon CoolPix E3200
				Nikon CoolPix 4500
				Nikon CoolPix 950
				Nikon Coolpix 5700
				Canon PowerShot S200
				Canon PowerShot S110
				Olympus C2040Z 
				Olympus C960
				Olumpus E-300
				Olympus E-410
				Olympus E-500
				Olympus E-510
				Olympus E-3
				Canon Ixus
				Canon EOS 300D
				Canon Digital Rebel
				Canon EOS 10D
				Canon PowerShot G2
				FujiFilm DX 10
				FujiFilm MX-1200
				FujiFilm FinePix2400
				FujiFilm FinePix2600
				FujiFilm FinePix S602
				FujiFilm FinePix40i
				Sony D700
				Sony Cybershot
				Kodak DC210
				Kodak DC240
				Kodak DC4800
				Kodak DX3215
				Ricoh RDC-5300 
				Sanyo VPC-G250
				Sanyo VPC-SX550
				Epson 3100z

	
	VERSION HISTORY:
	
	1.0   September 23, 2002   
	
		+ First Public Release
	
	1.1    January 25, 2003
		
		+ Gracefully handled the error case where you pass an empty string to this library
		+ Fixed an inconsistency in the Olympus Camera parsing module
		+ Added support for parsing the MakerNote of Canon images.
		+ Modified how the imagefile is opened so it works for windows machines.
		+ Correctly parses the FocalPlaneResolutionUnit and PhotometricInterpretation fields
		+ Negative rational numbers are properly displayed
		+ Strange old cameras that use Motorola endineness are now properly supported
		+ Tested with several more cameras
		
		Potential Problem: Negative Shorts and Negative Longs may not be correctly displayed, but I
			have not yet found an example of negative shorts or longs being used.
	
	1.2    March 30, 2003
	
		+ Fixed an error that was displayed if you edited your image with WinXP's image viewer
		+ Fixed a bug that caused some images saved from 3rd party software to not parse correctly
		+ Changed the ExposureTime tag to display in fractional seconds rather than decimal
		+ Updated the ShutterSpeedValue tag to have the units of 'sec'
		+ Added support for parsing the MakeNote of FujiFilm images
		+ Added support for parsing the MakeNote of Sanyo images
		+ Fixed a bug with parsing some Olympus MakerNote tags
		+ Tested with several more cameras
			
	1.3    June 15, 2003
	
		+ Fixed Canon MakerNote support for some models
 			(Canon has very difficult and inconsistent MakerNote syntax)
		+ Negative signed shorts and negative signed longs are properly displayed
		+ Several more tags are defined
		+ More information in my comments about what each tag is
		+ Parses and Displays GPS information if available
		+ Tested with several more cameras
			
	1.4    September 14, 2003
		
		+ This software is now licensed under the GNU General Public License
		+ Exposure time is now correctly displayed when the numerator is 10
		+ Fixed the calculation and display of ShutterSpeedValue, ApertureValue and MaxApertureValue 
		+ Fixed a bug with the GPS code
		+ Tested with several more cameras
			
	1.5    February 18, 2005
	
		+ It now gracefully deals with a passed in file that cannot be found.
		+ Fixed a GPS bug for the parsing of Altitude and other signed rational numbers
		+ Defined more values for Canon cameras.
		+ Added 'bulb' detection for ShutterSpeed
		+ Made script loading a little faster and less memory intensive.
		+ Bug fixes
		+ Better error reporting
		+ Graceful failure for files with corrupt exif info.
		+ QuickTime (including iPhoto) messes up the Makernote tag for certain photos (no workaround yet)
		+ Now reads exif information when the jpeg markers are out of order
		+ Gives raw data output for IPTC, COM and APP2 fields which are sometimes set by other applications
		+ Improvements to Nikon Makernote parsing
			
	1.6    March 25th, 2007 [Zenphoto]
	
		+ Adopted into the Zenphoto gallery project, at http://www.zenphoto.org
		+ Fixed a bug where strings had trailing null bytes.
		+ Formatted selected strings better.
		+ Added calculation of 35mm-equivalent focal length when possible.
		+ Cleaned up code for readability and efficiency.
		
	1.7    April 11th, 2008 [Zenphoto]
	  
	  + Fixed bug with newer Olympus cameras where number of fields was miscalculated leading to bad performance.
		+ More logical fraction calculation for shutter speed.

2009: For all further changes, see the Zenphoto change logs.
		
*/



	


//================================================================================================
// Converts from Intel to Motorola endien.  Just reverses the bytes (assumes hex is passed in)
//================================================================================================

function intel2Moto($intel) {
	static $cache = [];
	if (isset($cache[$intel])) {
		return $cache[$intel];
	}

	$cache[$intel] = '';
	$len  = strlen($intel);
	if ($len > 1000) { // an unreasonable length, override it.
		$len = 1000;
	}
	for($i = 0; $i <= $len; $i += 2) {		
		$cache[$intel] .= substr($intel, $len-$i, 2);
	}
	return $cache[$intel];
}


//================================================================================================
// Looks up the name of the tag
//================================================================================================
function lookup_tag($tag) {
	$tag = match ($tag) {
        '000b' => 'ACDComment',
        '00fe' => 'ImageType',
        '0106' => 'PhotometricInterpret',
        '010e' => 'ImageDescription',
        '010f' => 'Make',
        '0110' => 'Model',
        '0112' => 'Orientation',
        '0115' => 'SamplePerPixel',
        '011a' => 'xResolution',
        '011b' => 'yResolution',
        '011c' => 'PlanarConfig',
        '0128' => 'ResolutionUnit',
        '0131' => 'Software',
        '0132' => 'DateTime',
        '013b' => 'Artist',
        '013c' => 'HostComputer',
        '013e' => 'WhitePoint',
        '013f' => 'PrimaryChromaticities',
        '0211' => 'YCbCrCoefficients',
        '0213' => 'YCbCrPositioning',
        '0214' => 'ReferenceBlackWhite',
        '8298' => 'Copyright',
        '8649' => 'PhotoshopSettings',
        '8769' => 'ExifOffset',
        '8825' => 'GPSInfoOffset',
        '9286' => 'UserCommentOld',
        '829a' => 'ExposureTime',
        '829d' => 'FNumber',
        '8822' => 'ExposureProgram',
        '8824' => 'SpectralSensitivity',
        '8827' => 'ISOSpeedRatings',
        '9000' => 'ExifVersion',
        '9003' => 'DateTimeOriginal',
        '9004' => 'DateTimeDigitized',
        '9101' => 'ComponentsConfiguration',
        '9102' => 'CompressedBitsPerPixel',
        '9201' => 'ShutterSpeedValue',
        '9202' => 'ApertureValue',
        '9203' => 'BrightnessValue',
        '9204' => 'ExposureBiasValue',
        '9205' => 'MaxApertureValue',
        '9206' => 'SubjectDistance',
        '9207' => 'MeteringMode',
        '9208' => 'LightSource',
        '9209' => 'Flash',
        '920a' => 'FocalLength',
        '9213' => 'ImageHistory',
        '927c' => 'MakerNote',
        '9286' => 'UserComment',
        '9290' => 'SubsecTime',
        '9291' => 'SubsecTimeOriginal',
        '9292' => 'SubsecTimeDigitized',
        'a000' => 'FlashPixVersion',
        'a001' => 'ColorSpace',
        'a002' => 'ExifImageWidth',
        'a003' => 'ExifImageHeight',
        'a004' => 'RelatedSoundFile',
        'a005' => 'ExifInteroperabilityOffset',
        'a20c' => 'SpacialFreqResponse',
        'a20b' => 'FlashEnergy',
        'a20e' => 'FocalPlaneXResolution',
        'a20f' => 'FocalPlaneYResolution',
        'a210' => 'FocalPlaneResolutionUnit',
        'a214' => 'SubjectLocation',
        'a215' => 'ExposureIndex',
        'a217' => 'SensingMethod',
        'a300' => 'FileSource',
        'a301' => 'SceneType',
        'a302' => 'CFAPattern',
        'a401' => 'CustomerRender',
        'a402' => 'ExposureMode',
        'a403' => 'WhiteBalance',
        'a404' => 'DigitalZoomRatio',
        'a405' => 'FocalLengthIn35mmFilm',
        'a406' => 'SceneCaptureMode',
        'a407' => 'GainControl',
        'a408' => 'Contrast',
        'a409' => 'Saturation',
        'a40a' => 'Sharpness',
        '0001' => 'InteroperabilityIndex',
        '0002' => 'InteroperabilityVersion',
        '1000' => 'RelatedImageFileFormat',
        '1001' => 'RelatedImageWidth',
        '1002' => 'RelatedImageLength',
        '0100' => 'ImageWidth',
        '0101' => 'ImageLength',
        '0102' => 'BitsPerSample',
        '0103' => 'Compression',
        '0106' => 'PhotometricInterpretation',
        '010e' => 'ThumbnailDescription',
        '010f' => 'ThumbnailMake',
        '0110' => 'ThumbnailModel',
        '0111' => 'StripOffsets',
        '0112' => 'ThumbnailOrientation',
        '0115' => 'SamplesPerPixel',
        '0116' => 'RowsPerStrip',
        '0117' => 'StripByteCounts',
        '011a' => 'ThumbnailXResolution',
        '011b' => 'ThumbnailYResolution',
        '011c' => 'PlanarConfiguration',
        '0128' => 'ThumbnailResolutionUnit',
        '0201' => 'JpegIFOffset',
        '0202' => 'JpegIFByteCount',
        '0212' => 'YCbCrSubSampling',
        '00ff' => 'SubfileType',
        '012d' => 'TransferFunction',
        '013d' => 'Predictor',
        '0142' => 'TileWidth',
        '0143' => 'TileLength',
        '0144' => 'TileOffsets',
        '0145' => 'TileByteCounts',
        '014a' => 'SubIFDs',
        '015b' => 'JPEGTables',
        '828d' => 'CFARepeatPatternDim',
        '828e' => 'CFAPattern',
        '828f' => 'BatteryLevel',
        '83bb' => 'IPTC/NAA',
        '8773' => 'InterColorProfile',
        '8828' => 'OECF',
        '8829' => 'Interlace',
        '882a' => 'TimeZoneOffset',
        '882b' => 'SelfTimerMode',
        '920b' => 'FlashEnergy',
        '920c' => 'SpatialFrequencyResponse',
        '920d' => 'Noise',
        '9211' => 'ImageNumber',
        '9212' => 'SecurityClassification',
        '9214' => 'SubjectLocation',
        '9215' => 'ExposureIndex',
        '9216' => 'TIFF/EPStandardID',
        'a20b' => 'FlashEnergy',
        default => 'unknown:'.$tag,
    };
	return $tag;

}


//================================================================================================
// Looks up the datatype
//================================================================================================
function lookup_type(&$type,&$size) {
	switch($type) {
		case '0001': $type = 'UBYTE'; $size=1; break;
		case '0002': $type = 'ASCII'; $size=1; break;
		case '0003': $type = 'USHORT'; $size=2; break;
		case '0004': $type = 'ULONG'; $size=4; break;
		case '0005': $type = 'URATIONAL'; $size=8; break;
		case '0006': $type = 'SBYTE'; $size=1; break;
		case '0007': $type = 'UNDEFINED'; $size=1; break;
		case '0008': $type = 'SSHORT'; $size=2; break;
		case '0009': $type = 'SLONG'; $size=4; break;
		case '000a': $type = 'SRATIONAL'; $size=8; break;
		case '000b': $type = 'FLOAT'; $size=4; break;
		case '000c': $type = 'DOUBLE'; $size=8; break;
		default: $type = 'error:'.$type; $size=0; break;
	}
	return $type;
}

//================================================================================================
// processes a irrational number
//================================================================================================
function unRational($data, $type, $intel) {
		$data = bin2hex($data);
		if ($intel == 1) {
			$data = intel2Moto($data);
			$top = hexdec(substr($data,8,8));   // intel stores them bottom-top
			$bottom = hexdec(substr($data,0,8));  // intel stores them bottom-top
		} else {
			$top = hexdec(substr($data,0,8));        // motorola stores them top-bottom
			$bottom = hexdec(substr($data,8,8));      // motorola stores them top-bottom
		}
		
		if ($type == 'SRATIONAL' && $top > 2147483647) $top = $top - 4294967296;    // this makes the number signed instead of unsigned
		if ($bottom != 0)
			$data=$top/$bottom;
		else 
			if ($top == 0)
				$data = 0;
			else
				$data = $top.'/'.$bottom;
	return $data;
}

//================================================================================================
// processes a rational number
//================================================================================================
function rational($data,$type,$intel) {
	if (($type == 'USHORT' || $type == 'SSHORT')) {
		$data = substr($data,0,2);
	}
	$data = bin2hex($data);
	if ($intel == 1) {
		$data = intel2Moto($data);
	}
	$data = hexdec($data);
	if ($type == 'SSHORT' && $data > 32767)     $data = $data - 65536;  // this makes the number signed instead of unsigned
	if ($type == 'SLONG' && $data > 2147483647) $data = $data - 4294967296;  // this makes the number signed instead of unsigned
	return $data;
}

//================================================================================================
// Formats Data for the data type
//================================================================================================
function formatData($type,$tag,$intel,$data) {
	switch ($type) {
		case 'ASCII':
			if (($pos = strpos($data, chr(0))) !== false) {	// Search for a null byte and stop there.
				$data = substr($data, 0, $pos);
			}
			if ($tag == '010f') $data = ucwords(strtolower(trim($data)));	// Format certain kinds of strings nicely (Camera make etc.)
			break;
		case 'URATIONAL':
		case 'SRATIONAL':
			switch ($tag) {
				case '011a': // XResolution
				case '011b': // YResolution
					$data = round(unRational($data,$type,$intel)).' dots per ResolutionUnit';
					break;
				case '829a': // Exposure Time
					$data = formatExposure(unRational($data,$type,$intel));
					break;
				case '829d': // FNumber
					$data = 'f/'.unRational($data,$type,$intel);
					break;
				case '9204': // ExposureBiasValue
					$data = round(unRational($data,$type,$intel), 2) . ' EV';
					break;
				case '9205': // ApertureValue
				case '9202': // MaxApertureValue
					// ApertureValue is given in the APEX Mode. Many thanks to Matthieu Froment for this code
					// The formula is : Aperture = 2*log2(FNumber) <=> FNumber = e((Aperture.ln(2))/2)
					$datum = exp((unRational($data,$type,$intel)*log(2))/2);
					$data = round($datum, 1);// Focal is given with a precision of 1 digit.
					$data='f/'.$datum; 
					break;
				case '920a': // FocalLength
					$data = unRational($data,$type,$intel).' mm';
					break;
				case '9201': // ShutterSpeedValue
					// The ShutterSpeedValue is given in the APEX mode. Many thanks to Matthieu Froment for this code
					// The formula is : Shutter = - log2(exposureTime) (Appendix C of EXIF spec.)
					// Where shutter is in APEX, log2(exposure) = ln(exposure)/ln(2)
					// So final formula is : exposure = exp(-ln(2).shutter)
					// The formula can be developed : exposure = 1/(exp(ln(2).shutter))
					$datum = exp(unRational($data,$type,$intel) * log(2));
					if ($datum != 0) $datum = 1/$datum;
					$data = formatExposure($datum);
					break;
				default:
					$data = unRational($data,$type,$intel);
					break;
			}
			break;
		case 'USHORT':
		case 'SSHORT':
		case 'ULONG':
		case 'SLONG':
		case 'FLOAT':
		case 'DOUBLE':
			$data = rational($data,$type,$intel);
			switch ($tag) {
				case '0112':	// Orientation
					// Example of how all of these tag formatters should be...
					$data = match ($data) {
                        0, 1 => (string) t('1: Normal (0 deg)'),
                        2 => (string) t('2: Mirrored'),
                        3 => (string) t('3: Upside-down'),
                        4 => (string) t('4: Upside-down Mirrored'),
                        5 => (string) t('5: 90 deg CW Mirrored'),
                        6 => (string) t('6: 90 deg CCW'),
                        7 => (string) t('7: 90 deg CCW Mirrored'),
                        8 => (string) t('8: 90 deg CW'),
                        default => sprintf((string) t('%d: Unknown'),$data),
                    };
					break;
				case '0128':	// ResolutionUnit
				case 'a210':	// FocalPlaneResolutionUnit
				case '0128':	// ThumbnailResolutionUnit
					switch ($data) {
						case 1:	$data = (string) t('No Unit');	break;
						case 2:	$data = (string) t('Inch');	break;
						case 3:	$data = (string) t('Centimeter');	break;
					}
					break;
				case '0213':	// YCbCrPositioning
					switch ($data) {
						case 1:	$data = (string) t('Center of Pixel Array');	break;
						case 2:	$data = (string) t('Datum Point');	break;
					}
					break;
				case '8822':	// ExposureProgram
					$data = match ($data) {
                        1 => (string) t('Manual'),
                        2 => (string) t('Program'),
                        3 => (string) t('Aperture Priority'),
                        4 => (string) t('Shutter Priority'),
                        5 => (string) t('Program Creative'),
                        6 => (string) t('Program Action'),
                        7 => (string) t('Portrait'),
                        8 => (string) t('Landscape'),
                        default => (string) t('Unknown').': '.$data,
                    };
					break;
				case '9207':	// MeteringMode
					$data = match ($data) {
                        1 => (string) t('Average'),
                        2 => (string) t('Center Weighted Average'),
                        3 => (string) t('Spot'),
                        4 => (string) t('Multi-Spot'),
                        5 => (string) t('Pattern'),
                        6 => (string) t('Partial'),
                        255 => (string) t('Other'),
                        default => (string) t('Unknown').': '.$data,
                    };
					break;
				case '9208':	// LightSource
					$data = match ($data) {
                        1 => (string) t('Daylight'),
                        2 => (string) t('Fluorescent'),
                        3 => (string) t('Tungsten'),
                        10 => (string) t('Flash'),
                        17 => (string) t('Standard Light A'),
                        18 => (string) t('Standard Light B'),
                        19 => (string) t('Standard Light C'),
                        20 => (string) t('D55'),
                        21 => (string) t('D65'),
                        22 => (string) t('D75'),
                        23 => (string) t('D50'),
                        24 => (string) t('ISO Studio Tungsten'),
                        255 => (string) t('Other'),
                        default => (string) t('Unknown').': '.$data,
                    };
					break;
				case '9209':	// Flash
					$data = match ($data) {
                        0 => (string) t('No Flash'),
                        1 => (string) t('Flash'),
                        5 => (string) t('Flash, strobe return light not detected'),
                        7 => (string) t('Flash, strobe return light detected'),
                        9 => (string) t('Compulsory Flash'),
                        13 => (string) t('Compulsory Flash, Return light not detected'),
                        15 => (string) t('Compulsory Flash, Return light detected'),
                        16 => (string) t('No Flash'),
                        24 => (string) t('No Flash'),
                        25 => (string) t('Flash, Auto-Mode'),
                        29 => (string) t('Flash, Auto-Mode, Return light not detected'),
                        31 => (string) t('Flash, Auto-Mode, Return light detected'),
                        32 => (string) t('No Flash'),
                        65 => (string) t('Red Eye'),
                        69 => (string) t('Red Eye, Return light not detected'),
                        71 => (string) t('Red Eye, Return light detected'),
                        73 => (string) t('Red Eye, Compulsory Flash'),
                        77 => (string) t('Red Eye, Compulsory Flash, Return light not detected'),
                        79 => (string) t('Red Eye, Compulsory Flash, Return light detected'),
                        89 => (string) t('Red Eye, Auto-Mode'),
                        93 => (string) t('Red Eye, Auto-Mode, Return light not detected'),
                        95 => (string) t('Red Eye, Auto-Mode, Return light detected'),
                        default => (string) t('Unknown').': '.$data,
                    };
					break;
				case 'a001':	// ColorSpace
					if ($data == 1)         $data = (string) t('sRGB');
					else                    $data = (string) t('Uncalibrated');
					break;
				case 'a002':	// ExifImageWidth
				case 'a003':	// ExifImageHeight
					$data = $data. ' '.(string) t('pixels');
					break;
				case '0103':	// Compression
					$data = match ($data) {
                        1 => (string) t('No Compression'),
                        6 => (string) t('Jpeg Compression'),
                        default => (string) t('Unknown').': '.$data,
                    };
					break;
				case 'a217':	// SensingMethod
					$data = match ($data) {
                        1 => (string) t('Not defined'),
                        2 => (string) t('One Chip Color Area Sensor'),
                        3 => (string) t('Two Chip Color Area Sensor'),
                        4 => (string) t('Three Chip Color Area Sensor'),
                        5 => (string) t('Color Sequential Area Sensor'),
                        7 => (string) t('Trilinear Sensor'),
                        8 => (string) t('Color Sequential Linear Sensor'),
                        default => (string) t('Unknown').': '.$data,
                    };
					break;
				case '0106':	// PhotometricInterpretation
					$data = match ($data) {
                        1 => (string) t('Monochrome'),
                        2 => (string) t('RGB'),
                        6 => (string) t('YCbCr'),
                        default => (string) t('Unknown').': '.$data,
                    };
					break;
				//case "a408":	// Contrast
				//case "a40a":	//Sharpness
				//	switch($data) {
				//		case 0: $data="Normal"; break;
				//		case 1: $data="Soft"; break;
				//		case 2: $data="Hard"; break;
				//		default: $data="Unknown"; break;
				//	}
				//	break;
				//case "a409":	// Saturation
				//	switch($data) {
				//		case 0: $data="Normal"; break;
				//		case 1: $data="Low saturation"; break;
				//		case 2: $data="High saturation"; break;
				//		default: $data="Unknown"; break;
				//	}
				//	break;
				//case "a402":	// Exposure Mode
				//	switch($data) {
				//		case 0: $data="Auto exposure"; break;
				//		case 1: $data="Manual exposure"; break;
				//		case 2: $data="Auto bracket"; break;
				//		default: $data="Unknown"; break;
				//	}
				//	break;
			}
			break;
		case 'UNDEFINED':
			switch ($tag) {
				case '9000':	// ExifVersion
				case 'a000':	// FlashPixVersion
				case '0002':	// InteroperabilityVersion
					$data=(string) t('version').' '.($data && is_numeric($data) ? $data/100 : $data);
					break;
				case 'a300':	// FileSource
					$data = bin2hex($data);
					$data = str_replace('00','',$data);
					$data = str_replace('03',(string) t('Digital Still Camera'),$data);
					break;
				case 'a301':	// SceneType
					$data = bin2hex($data);
					$data = str_replace('00','',$data);
					$data = str_replace('01',(string) t('Directly Photographed'),$data);
					break;
				case '9101':	// ComponentsConfiguration
					$data = bin2hex($data);
					$data = str_replace('01','Y',$data);
					$data = str_replace('02','Cb',$data);
					$data = str_replace('03','Cr',$data);
					$data = str_replace('04','R',$data);
					$data = str_replace('05','G',$data);
					$data = str_replace('06','B',$data);
					$data = str_replace('00','',$data);
					break;
				//case "9286":	//UserComment
				//	$encoding	= rtrim(substr($data, 0, 8));
				//	$data		= rtrim(substr($data, 8));
				//	break;
			}
			break;
		default:
			$data = bin2hex($data);
			if ($intel == 1) $data = intel2Moto($data);
			break;
	}
	return $data;
}

function formatExposure($data) {
	if (!str_contains($data,'/')) {
		if ($data > 1) {
			return round($data, 2).' '.(string) t('sec');
		} else {
			$n=0; $d=0;
			ConvertToFraction($data, $n, $d);
			return $n.'/'.$d.' '.(string) t('sec');
		}
	} else {
		return (string) t('Bulb');
	}
}

//================================================================================================
// Reads one standard IFD entry
//================================================================================================
function read_entry(&$result,$in,$seek,$intel,$ifd_name,$globalOffset) {
	
	if (feof($in)) { // test to make sure we can still read.
		$result['Errors'] = $result['Errors']+1;
		return;
	}
	
	// 2 byte tag
	$tag = bin2hex(fread($in, 2));
	if ($intel == 1) $tag = intel2Moto($tag);
	$tag_name = lookup_tag($tag);
	
	// 2 byte datatype
	$type = bin2hex(fread($in, 2));
	if ($intel == 1) $type = intel2Moto($type);
	lookup_type($type, $size);
	
	if (str_contains($tag_name, 'unknown:') && str_contains($type, 'error:')) { // we have an error
		$result['Errors'] = $result['Errors']+1;
		return;
	}
	
	// 4 byte number of elements
	$count = bin2hex(fread($in, 4));
	if ($intel == 1) $count = intel2Moto($count);
	$bytesofdata = $size*hexdec($count);
	
	// 4 byte value or pointer to value if larger than 4 bytes
	$value = fread( $in, 4 );
	
	if ($bytesofdata <= 4) {   // if datatype is 4 bytes or less, its the value
		$data = $value;
	} else if ($bytesofdata < 100000) {        // otherwise its a pointer to the value, so lets go get it
		$value = bin2hex($value);
		if ($intel == 1) $value = intel2Moto($value);
		$v = fseek($seek, $globalOffset+hexdec($value));  // offsets are from TIFF header which is 12 bytes from the start of the file
		if ($v == 0) {
			$data = fread($seek, $bytesofdata);
		} else if ($v == -1) {
			$result['Errors'] = $result['Errors']+1;
		}
	} else { // bytesofdata was too big, so the exif had an error
		$result['Errors'] = $result['Errors']+1;
		return;
	}
	if ($tag_name == 'MakerNote') { // if its a maker tag, we need to parse this specially
		$make = $result['IFD0']['Make'];
		
		if ($result['VerboseOutput'] == 1) {
			$result[$ifd_name]['MakerNote']['RawData'] = $data;
		}
		if (preg_match('/NIKON/i',$make)) {
			require_once(__DIR__.'/makers/nikon.php');
			parseNikon($data,$result);
			$result[$ifd_name]['KnownMaker'] = 1;
		} else if (preg_match('/OLYMPUS/i',$make)) {
			require_once(__DIR__.'/makers/olympus.php');
			parseOlympus($data,$result,$seek,$globalOffset);
			$result[$ifd_name]['KnownMaker'] = 1;
		} else if (preg_match('/Canon/i',$make)) {
			require_once(__DIR__.'/makers/canon.php');
			parseCanon($data,$result,$seek,$globalOffset);
			$result[$ifd_name]['KnownMaker'] = 1;
		} else if (preg_match('/FUJIFILM/i',$make)) {
			require_once(__DIR__.'/makers/fujifilm.php');
			parseFujifilm($data,$result);
			$result[$ifd_name]['KnownMaker'] = 1;
		} else if (preg_match('/SANYO/i',$make)) {
			require_once(__DIR__.'/makers/sanyo.php');
			parseSanyo($data,$result,$seek,$globalOffset);
			$result[$ifd_name]['KnownMaker'] = 1;
		} else if (preg_match('/Panasonic/i',$make)) { 
			require_once(__DIR__.'/makers/panasonic.php'); 
			parsePanasonic($data,$result,$seek,$globalOffset); 
			$result[$ifd_name]['KnownMaker'] = 1; 
		} else {
			$result[$ifd_name]['KnownMaker'] = 0;
		}
	} else if ($tag_name == 'GPSInfoOffset') {
		require_once(__DIR__.'/makers/gps.php');
		$formated_data = formatData($type,$tag,$intel,$data);
		$result[$ifd_name]['GPSInfo'] = $formated_data;
		parseGPS($data,$result,$formated_data,$seek,$globalOffset);
	} else {
		// Format the data depending on the type and tag
		$formated_data = formatData($type,$tag,$intel,$data);
		
		$result[$ifd_name][$tag_name] = $formated_data;
		
		if ($result['VerboseOutput'] == 1) {
			if ($type == 'URATIONAL' || $type == 'SRATIONAL' || $type == 'USHORT' || $type == 'SSHORT' || $type == 'ULONG' || $type == 'SLONG' || $type == 'FLOAT' || $type == 'DOUBLE') {
				$data = bin2hex($data);
				if ($intel == 1) $data = intel2Moto($data);
			}
		$result[$ifd_name][$tag_name.'_Verbose']['RawData'] = $data;
		$result[$ifd_name][$tag_name.'_Verbose']['Type'] = $type;
		$result[$ifd_name][$tag_name.'_Verbose']['Bytes'] = $bytesofdata;
		}
	}
}


//================================================================================================
// Pass in a file and this reads the EXIF data
//
// Usefull resources
// http:// www.ba.wakwak.com/~tsuruzoh/Computer/Digicams/exif-e.html
// http:// www.w3.org/Graphics/JPEG/jfif.txt
// http:// exif.org/
// http:// www.ozhiker.com/electronics/pjmt/library/list_contents.php4
// http:// www.ozhiker.com/electronics/pjmt/jpeg_info/makernotes.html
// http:// pel.sourceforge.net/
// http:// us2.php.net/manual/en/function.exif-read-data.php
//================================================================================================
function read_exif_data_raw($path,$verbose) {
	
	if ($path == '' || $path == 'none') return;
	
	$in = @fopen($path, 'rb'); // the b is for windows machines to open in binary mode
	$seek = @fopen($path, 'rb'); // There may be an elegant way to do this with one file handle.
	
	$globalOffset = 0;
	
	$verbose ??= 0;
	
	$result['VerboseOutput'] = $verbose;
	$result['Errors'] = 0;
	
	if (!$in || !$seek) {  // if the path was invalid, this error will catch it
		$result['Errors'] = 1;
		$result['Error'][$result['Errors']] = (string) t('The file could not be found.');
		return $result;
	}

	$GLOBALS['exiferFileSize'] = filesize($path);
	
	// First 2 bytes of JPEG are 0xFFD8 
	$data = bin2hex(fread( $in, 2 ));
	if ($data == 'ffd8') {
		$result['ValidJpeg'] = 1;
	} else {
		$result['ValidJpeg'] = 0;
		fseek($in, 0);
	}  
	
	$result['ValidIPTCData'] = 0;
	$result['ValidJFIFData'] = 0;
	$result['ValidEXIFData'] = 0;
	$result['ValidAPP2Data'] = 0;
	$result['ValidCOMData'] = 0;
	
if ($result['ValidJpeg'] == 1) {
	// Next 2 bytes are MARKER tag (0xFFE#)
	$data = bin2hex(fread( $in, 2 ));
	$size = bin2hex(fread( $in, 2 ));

	// LOOP THROUGH MARKERS TILL YOU GET TO FFE1  (exif marker)
	$abortCount = 0;
	while(!feof($in) && $data!='ffe1' && $data!='ffc0' && $data!='ffd9' && ++$abortCount < 200) {
		if ($data == 'ffe0') { // JFIF Marker
			$result['ValidJFIFData'] = 1;
			$result['JFIF']['Size'] = hexdec($size);
			
			if (hexdec($size)-2 > 0) {
				$data = fread( $in, hexdec($size)-2);
				$result['JFIF']['Data'] = $data;
			} 
			
			$result['JFIF']['Identifier'] = substr($data,0,5);;
			$result['JFIF']['ExtensionCode'] =  bin2hex(substr($data,6,1));
			
			$globalOffset+=hexdec($size)+2;
			
		} else if ($data == 'ffed') {  // IPTC Marker
			$result['ValidIPTCData'] = 1;
			$result['IPTC']['Size'] = hexdec($size);
			
			if (hexdec($size)-2 > 0) {
				$data = fread( $in, hexdec($size)-2);
				$result['IPTC']['Data'] = $data ;
			} 
			$globalOffset+=hexdec($size)+2;
			
		} else if ($data == 'ffe2') {  // EXIF extension Marker
			$result['ValidAPP2Data'] = 1;
			$result['APP2']['Size'] = hexdec($size);
			
			if (hexdec($size)-2 > 0) {
				$data = fread( $in, hexdec($size)-2);
				$result['APP2']['Data'] = $data ;
			} 
			$globalOffset+=hexdec($size)+2;
			
		} else if ($data == 'fffe') {  // COM extension Marker
			$result['ValidCOMData'] = 1;
			$result['COM']['Size'] = hexdec($size);
			
			if (hexdec($size)-2 > 0) {
				$data = fread( $in, hexdec($size)-2);
				$result['COM']['Data'] = $data ;
			} 
			$globalOffset+=hexdec($size)+2;
			
		} else if ($data == 'ffe1') {
			$result['ValidEXIFData'] = 1;
		}
	
		$data = bin2hex(fread( $in, 2 ));
		$size = bin2hex(fread( $in, 2 ));
	}
	// END MARKER LOOP
	
	if ($data == 'ffe1') {
		$result['ValidEXIFData'] = 1;
	} else {
		fclose($in);
		fclose($seek);
		return $result;
	}
	
	// Size of APP1 
	$result['APP1Size'] = hexdec($size);
	
	// Start of APP1 block starts with 'Exif' header (6 bytes)
	$header = fread( $in, 6 );

} // END IF ValidJpeg
	
	// Then theres a TIFF header with 2 bytes of endieness (II or MM) 
	$header = fread( $in, 2 );
	if ($header==='II') {
		$intel=1;
		$result['Endien'] = 'Intel';
	} else if ($header==='MM') {
		$intel=0;
		$result['Endien'] = 'Motorola';
	} else {
		$intel=1; // not sure what the default should be, but this seems reasonable
		$result['Endien'] = 'Unknown';
	}
	
	// 2 bytes of 0x002a
	$tag = bin2hex(fread( $in, 2 ));
	
	// Then 4 bytes of offset to IFD0 (usually 8 which includes all 8 bytes of TIFF header)
	$offset = bin2hex(fread( $in, 4 ));
	if ($intel == 1) $offset = intel2Moto($offset);
	
	// Check for extremely large values here
	if (hexdec($offset) > 100000) {
			$result['ValidEXIFData'] = 0;
		fclose($in);
		fclose($seek);
		return $result;
	}

	if (hexdec($offset)>8) $unknown = fread( $in, hexdec($offset)-8); // fixed this bug in 1.3
	
	// add 12 to the offset to account for TIFF header
	if ($result['ValidJpeg'] == 1) {
		$globalOffset+=12;
	}
	
	
	//===========================================================
	// Start of IFD0
	$num = bin2hex(fread( $in, 2 ));
	if ($intel == 1) $num = intel2Moto($num);
	$num = hexdec($num);
	$result['IFD0NumTags'] = $num;
	
	if ($num<1000) { // 1000 entries is too much and is probably an error.
		for($i=0; $i<$num; $i++) {
			read_entry($result,$in,$seek,$intel,'IFD0',$globalOffset);
		}
	} else {
		$result['Errors'] = $result['Errors']+1;
		$result['Error'][$result['Errors']] = 'Illegal size for IFD0';
	}
	
	// store offset to IFD1
	$offset = bin2hex(fread( $in, 4 ));
	if ($intel == 1) $offset = intel2Moto($offset);
	$result['IFD1Offset'] = hexdec($offset);
	
	// Check for SubIFD
	if (!isset($result['IFD0']['ExifOffset']) || $result['IFD0']['ExifOffset'] == 0) {
		fclose($in);
		fclose($seek);
		return $result;
	}
	
	// seek to SubIFD (Value of ExifOffset tag) above.
	$ExitOffset = $result['IFD0']['ExifOffset'];
	$v = fseek($in,$globalOffset+$ExitOffset);
	if ($v == -1) {
		$result['Errors'] = $result['Errors']+1;
		$result['Error'][$result['Errors']] = (string) t('Could not Find SubIFD');
	}
	
	//===========================================================
	// Start of SubIFD
	$num = bin2hex(fread( $in, 2 ));
	if ($intel == 1) $num = intel2Moto($num);
	$num = hexdec($num);
	$result['SubIFDNumTags'] = $num;
	
	if ($num<1000) { // 1000 entries is too much and is probably an error.
		for($i=0; $i<$num; $i++) {
			read_entry($result,$in,$seek,$intel,'SubIFD',$globalOffset);
		}
	} else {
		$result['Errors'] = $result['Errors']+1;
		$result['Error'][$result['Errors']] = (string) t('Illegal size for SubIFD');
	}
	
	// Add the 35mm equivalent focal length:
	if (isset($result['IFD0']['FocalLengthIn35mmFilm']) && !isset($result['SubIFD']['FocalLengthIn35mmFilm'])) { // found in the wrong place
		$result['SubIFD']['FocalLengthIn35mmFilm'] = $result['IFD0']['FocalLengthIn35mmFilm'];
	}
	$result['SubIFD']['FocalLengthIn35mmFilm'] ??= get35mmEquivFocalLength($result);
	
	// Check for IFD1
	if (!isset($result['IFD1Offset']) || $result['IFD1Offset'] == 0) {
		fclose($in);
		fclose($seek);
		return $result;
	}
	// seek to IFD1
	$v = fseek($in,$globalOffset+$result['IFD1Offset']);
	if ($v == -1) {
		$result['Errors'] = $result['Errors']+1;
		$result['Error'][$result['Errors']] = (string) t('Could not Find IFD1');
	}
	
	//===========================================================
	// Start of IFD1
	$num = bin2hex(fread( $in, 2 ));
	if ($intel == 1) $num = intel2Moto($num);
	$num = hexdec($num);
	$result['IFD1NumTags'] = $num;
	
	if ($num<1000) { // 1000 entries is too much and is probably an error.
		for($i=0; $i<$num; $i++) {
			read_entry($result,$in,$seek,$intel,'IFD1',$globalOffset);
		}
	} else {
		$result['Errors'] = $result['Errors']+1;
		$result['Error'][$result['Errors']] = (string) t('Illegal size for IFD1');
	}
	// If verbose output is on, include the thumbnail raw data...
	if ($result['VerboseOutput'] == 1 && $result['IFD1']['JpegIFOffset']>0 && $result['IFD1']['JpegIFByteCount']>0) {
			$v = fseek($seek,$globalOffset+$result['IFD1']['JpegIFOffset']);
			if ($v == 0) {
				$data = fread($seek, $result['IFD1']['JpegIFByteCount']);
			} else if ($v == -1) {
				$result['Errors'] = $result['Errors']+1;
			}
			$result['IFD1']['ThumbnailData'] = $data;
	} 
	
	
	// Check for Interoperability IFD
	if (!isset($result['SubIFD']['ExifInteroperabilityOffset']) || $result['SubIFD']['ExifInteroperabilityOffset'] == 0) {
		fclose($in);
		fclose($seek);
		return $result;
	}
	// Seek to InteroperabilityIFD
	$v = fseek($in,$globalOffset+$result['SubIFD']['ExifInteroperabilityOffset']);
	if ($v == -1) {
		$result['Errors'] = $result['Errors']+1;
		$result['Error'][$result['Errors']] = (string) t('Could not Find InteroperabilityIFD');
	}
	
	//===========================================================
	// Start of InteroperabilityIFD
	$num = bin2hex(fread( $in, 2 ));
	if ($intel == 1) $num = intel2Moto($num);
	$num = hexdec($num);
	$result['InteroperabilityIFDNumTags'] = $num;
	
	if ($num<1000) { // 1000 entries is too much and is probably an error.
		for($i=0; $i<$num; $i++) {
			read_entry($result,$in,$seek,$intel,'InteroperabilityIFD',$globalOffset);
		}
	} else {
		$result['Errors'] = $result['Errors']+1;
		$result['Error'][$result['Errors']] = (string) t('Illegal size for InteroperabilityIFD');
	}
	fclose($in);
	fclose($seek);
	return $result;
}

//================================================================================================
// Converts a floating point number into a fraction.  Many thanks to Matthieu Froment for this code
//================================================================================================
function ConvertToFraction($v, &$n, &$d)
{
	if ($v == 0) {
		$n = 0;
		$d = 1;
		return;
	}
	for ($n=1; $n<100; $n++) {
		$v1 = 1/$v*$n;
		$d = round($v1, 0);
		if (abs($d - $v1) < 0.02) return;// within tolarance
	}
}  

//================================================================================================
// Calculates the 35mm-equivalent focal length from the reported sensor resolution, by Tristan Harward.
//================================================================================================
function get35mmEquivFocalLength(&$result) {
	if (isset($result['SubIFD']['ExifImageWidth'])) {
		$width = $result['SubIFD']['ExifImageWidth'];
		// some cameras have "XXXX pixels"
		$width = str_replace(' pixels', '', $width);
	} else {
		$width = 0;
	}
	if (isset($result['SubIFD']['FocalPlaneResolutionUnit'])) {
		$units = $result['SubIFD']['FocalPlaneResolutionUnit'];
	} else {
		$units = '';
	}
	$unitfactor = 1;
	$unitfactor = match ($units) {
        'Inch' => 25.4,
        'Centimeter' => 10,
        'No Unit' => 25.4,
        default => 25.4,
    };
	if (isset($result['SubIFD']['FocalPlaneXResolution'])) {
		$xres = $result['SubIFD']['FocalPlaneXResolution'];
	} else {
		$xres = '';
	}
	if (isset($result['SubIFD']['FocalLength'])) {
		$fl = $result['SubIFD']['FocalLength'];
		// some values have " mm" appended
		$fl = str_replace(' mm', '', $fl);
	} else {
		$fl = 0;
	}

	if (($width != 0) && !empty($units) && !empty($xres) && !empty($fl) && !empty($width)) {
		$ccdwidth = ($width * $unitfactor) / $xres;
		$equivfl = $fl / $ccdwidth*36+0.5;
		return $equivfl;
	}
	return null;
}

?>
