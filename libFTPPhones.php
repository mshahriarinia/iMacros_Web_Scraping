<?php
require_once('getPhonesFTP.php');

//@TODO Gary's phone: fix multi part names (first middle or company)

function getPhoneList($garyInputStr){
	return getPhoneListFromFTP($garyInputStr);
}

/**
 "0503`12B0002","Leuck                    ","Kevin          ","9422 Owl Hollow                    ","Helotes             ","TX","78023"
 @param $recordArray = array();
 $recordArray[0] = $index;
 $recordArray[1] = $r_lastName;
 $recordArray[2] = $r_firstName;
 $recordArray[3] = $r_address;
 $recordArray[4] = $r_city;
 $recordArray[5] = "TX";
 $recordArray[6] = $r_zipcode;
 */
function toGaryStringFormat($recordArray, $index){
	//if there is no last name ( in a multi word name of a person or name of company)
	//then print first name as last name as it has more room to hold more words
	$result = "";
	$result = $result . '"' . padOrTrim($recordArray[0], 12) . '","';
	if(strlen($recordArray[1]) == 0){
		$result = $result . padOrTrim($recordArray[2], 25) . '","' . padOrTrim($recordArray[1], 15) . '","';
	}else{
		$result = $result . padOrTrim($recordArray[1], 25) . '","' . padOrTrim($recordArray[2], 15) . '","';
	}
	$result = $result . padOrTrim($recordArray[3], 35) . '","';
	$result = $result . padOrTrim($recordArray[4], 20) . '","';
	$result = $result . padOrTrim($recordArray[5], 2) . '","';
	$result = $result . padOrTrim($recordArray[6], 5) . '"';
	return $result;
}

function padOrTrim($s, $maxLength){
	$lenDifference = $maxLength - strlen($s);
	$newS = $s;
	if($lenDifference > 0){
		for($i = 0; $i < $lenDifference; $i++){
			$newS .= " ";
		}
	}else if($lenDifference < 0){
		$newS = substr($newS, 0, $maxLength);
	}
	return $newS;
}

function padOrTrimNumber4Zeros($num){
	$s = "".(string)$num;
	if(strlen($s) == 1){
		$s = "000".$s;
	}else if(strlen($s) == 2){
		$s = "00".$s;
	}else if(strlen($s) == 3){
		$s = "0".$s;
	}else if(strlen($s) == 4){
		$s = $s;
	}
	return $s;
}

/**
 * Strips the gary format id and gets the last four characters for the number and removes
 * initial zeros.
 * @param unknown_type $garyId
 */
function toBasicNumeralId($garyId){
	$s = substr($garyId, strlen($garyId) - 4, 4);
	$s = preg_replace("/^0+/", "", $s);
	return $s;
}
