<?php



//2011078258 -> general info has address but details do not
//2011096838 -> relaetd documents
//TODO check for no results

/**
 * et im = CreateObject ("iMacros")
 CheckErr(im.iimInit(, False))

 ' Query the yellow pages site
 CheckErr(im.iimPlay(macroPath + "QryYP.iim"))
 Sub CheckErr(retCode)

 If retCode < 0 Then
 MsgBox im.iimGetLastError(), vbCritical, "Macro Error: " & retCode
 WScript.Quit()
 End If

 End Sub

 */
//TODO remove macro variables, remove nbsp, check for no results availabe for one detail 211124110
require('libImacros.php');
require('lib.php');
require('libParameters.php');
set_time_limit(24*60*60);
$link = getMySQLLink();

//------------
$gotoRecordMacro = 	"TAB T=1".$NEW_LINE.
			"URL GOTO=maps.google.com".$NEW_LINE;	
$s = $iim1->iimPlay("CODE:".$gotoRecordMacro);
//------------


$macro_TravisNavigate = getMacro('Travis_Navigate');
$macro_TravisNextPage = getMacro('Travis_NextPage');
$macro_TravisLastPage = getMacro('Travis_LastPage');

//$date = "09/15/2011";
$date = $TRAVIS_DATE;
$iim1->iimSet("-var_fromDate", $date);
$iim1->iimSet("-var_toDate", $date);
//$iim1->iimSet("-var_fromInstrument","2011121792" );
//$iim1->iimSet("-var_toInstrument","2011121797" );
$s = $iim1->iimPlay( "CODE:".$macro_TravisNavigate);

$isLastPage = false;

do{ //loop thrpugh the whole table until nothing to extract
	$scriptPosVal = 0;
	do{




		$currQueryValues = getGeneralRecord($iim1, $scriptPosVal);
		if($currQueryValues != NULL){
			$query = "INSERT INTO travis (instrument, date, document, r, grantor, e, grantee, legal_desc, img, fa_street, fa_city, fa_state, fa_zipcode) VALUES".$currQueryValues[1];
			if(mysql_query($query)){
				mylog(1, 'insert success at '. $query ."<br><br>\n");
			}
			else{
				mylog(1, 'insert fail at '. $query ."<br><br>\n");
			}

			$recordDetailURL = $currQueryValues[2];
			$detailInsertQuery = getDetailedRecordInfo($iim1, $recordDetailURL);
			if(mysql_query($detailInsertQuery)){
				mylog(1, 'insert success at' . $detailInsertQuery ."<br><br>\n");}
				else{
					mylog(1, 'insert fail at '. $detailInsertQuery ."<br><br>\n");
				}
				mylog(1, '--------------------------');
		}

		$scriptPosVal++;

	}while( $currQueryValues != NULL);//strstr($extractedValue, $EANF) == false);

	$isLastPage = isLastPage($iim1);
	if(!$isLastPage){
		$iim1->iimSet("-var_newPageWaitTime","10" );
		$s = $iim1->iimPlay( "CODE:".$macro_TravisNextPage);
	}else{
		$query = "INSERT INTO `done_dates` (`county`, `date`, `status`, `report_time`, `desc`) values (1,'" . $date . ", 1, now(), '');";
		if(mysql_query($query)){
			mylog(1, 'insert success at '. $query ."<br><br>\n");
		}
		else{
			mylog(1, 'insert fail at '. $query ."<br><br>\n");
		}
	}

}while($isLastPage == false);

mysql_close($link);

$s = $iim1->iimExit();
set_time_limit(0);

function isLastPage($iMacrosHandle){
	global $macro_TravisLastPage, $EANF;
	$s = $iMacrosHandle->iimPlay( "CODE:".$macro_TravisLastPage);
	$extractedValue = $iMacrosHandle->iimGetLastExtract;
	if(strstr($extractedValue, $EANF) == false){
		return true;
	}
	return false;
}

function getDetailedRecordInfo($iMacrosHandle, $recordDetailURL){

	global $NEW_LINE, $NEW_PAGE_WAIT;
	global $QNS, $QSS;

	$gotoRecordMacro = 	"TAB T=3".$NEW_LINE.
	"ONDIALOG POS=1 BUTTON=OK CONTENT=".$NEW_LINE.
	"URL GOTO=".$recordDetailURL.$NEW_LINE.
	"WAIT SECONDS=".$NEW_PAGE_WAIT;
	$s = $iMacrosHandle->iimPlay("CODE:".$gotoRecordMacro);

	$extractRecordDetailMacro = getMacro("Travis_ExtractRowDetailed");
	$s = $iMacrosHandle->iimPlay("CODE:".$extractRecordDetailMacro);

	$query = "INSERT INTO `travis_details` (`url` ,`instrument` ,`multi_seq` ,`document_date` ,".
	"`date_filed` ,`document_type` ,`book` ,`page` ,`remarks` ,`image` ,`grantor` ,`grantee` ,".
	"`returnee_name` ,`address` ,`city` ,`state` ,`zip` ,`subdivision` ,`lot` ,`block` ,`section` ,".
	"`acreage` ,`abstract` ,`freeform_legal` ,`related_document_list`)VALUES (" . '"'. $recordDetailURL ."\", ";

	$eInstrument = $iMacrosHandle->iimGetLastExtract(1);
	$query = $query . getInt(sss($eInstrument)) .$QNS;
	$eMultiSeq =  $iMacrosHandle->iimGetLastExtract(2);
	$query = $query . getInt(sss($eMultiSeq)) .$QNS;
	$eDocumentDate =  $iMacrosHandle->iimGetLastExtract(3);
	$query = $query . '"'. travisDateTimeToMySQLDateTime(sss($eDocumentDate)) .$QSS;
	$eDateFiled =  $iMacrosHandle->iimGetLastExtract(4);
	$query = $query . '"'. travisDateTimeToMySQLDateTime(sss($eDateFiled)) .$QSS;
	$eDocumentType =  $iMacrosHandle->iimGetLastExtract(5);
	$query = $query . '"'. sss($eDocumentType) .$QSS;
	$eBook =  $iMacrosHandle->iimGetLastExtract(6);
	$query = $query . '"'. sss($eBook) .$QSS;
	$ePage =  $iMacrosHandle->iimGetLastExtract(7);
	$query = $query . '"'. sss($ePage) .$QSS;
	$eRemarks =  $iMacrosHandle->iimGetLastExtract(8);
	$query = $query . '"'. sss($eRemarks) .$QSS;
	$eImage =  $iMacrosHandle->iimGetLastExtract(9);
	$query = $query . '"'. sss($eImage) .$QSS;
	$eGrantor =  $iMacrosHandle->iimGetLastExtract(10);
	$query = $query . '"'.  sss($eGrantor) .$QSS;
	$eGrantee =  $iMacrosHandle->iimGetLastExtract(11);
	$query = $query . '"'.  sss($eGrantee) .$QSS;
	$eReturneeName =  $iMacrosHandle->iimGetLastExtract(12);
	$query = $query . '"'.  sss($eReturneeName) .$QSS;
	$eAddress =  $iMacrosHandle->iimGetLastExtract(13);
	$query = $query . '"'. sss($eAddress) .$QSS;
	$eCity =  $iMacrosHandle->iimGetLastExtract(14);
	$query = $query . '"'. sss($eCity) .$QSS;
	$eState =  $iMacrosHandle->iimGetLastExtract(15);
	$query = $query . '"'. sss($eState) .$QSS;
	$eZip =  $iMacrosHandle->iimGetLastExtract(16);
	$query = $query . getInt(sss($eZip)) .$QNS;

	$eSubdivision =  $iMacrosHandle->iimGetLastExtract(17);
	$query = $query . '"'. sss($eSubdivision) .$QSS;
	$eLot =  $iMacrosHandle->iimGetLastExtract(18);
	$query = $query . '"'. sss($eLot) .$QSS;
	$eBlock =  $iMacrosHandle->iimGetLastExtract(19);
	$query = $query . '"'. sss($eBlock) .$QSS;
	$eSection =  $iMacrosHandle->iimGetLastExtract(20);
	$query = $query . '"'. sss($eSection) .$QSS;
	$eAcreage =  $iMacrosHandle->iimGetLastExtract(21);
	$query = $query . '"'. sss($eAcreage) .$QSS;
	$eAbstract =  $iMacrosHandle->iimGetLastExtract(22);
	$query = $query . '"'. sss($eAbstract) .$QSS;
	$eFreeformLegal =  $iMacrosHandle->iimGetLastExtract(23);
	$query = $query . '"'. sss($eFreeformLegal) .$QSS;
	$eRelatedDocumentList =  $iMacrosHandle->iimGetLastExtract(24);
	$query = $query . '"'.  sss($eRelatedDocumentList).'"';

	$query = $query . ")";
	return $query;
}


function getGeneralRecord($iMacrosHandle, $recordIndexInPage){
	global $EANF;
	$currQueryValues = NULL;
	$macro_ExtractRow = getMacro('Travis_ExtractRow');
	$iMacrosHandle->iimSet("-var_macroPosVal",$recordIndexInPage );
	$s = $iMacrosHandle->iimPlay( "CODE:".$macro_ExtractRow);

	//--

	$extractedValue = $iMacrosHandle->iimGetLastExtract(1);
	if(hasNoData($extractedValue)){
		return NULL;
	}

	$extractedDetailLink = $iMacrosHandle->iimGetLastExtract(2);

	if(!hasNoData($extractedValue)){

		$extractedValue = str_replace( "[EXTRACT]","",$extractedValue);

		$row = $extractedValue;

		$res  = preg_replace('/<\/?tr[^>]*>/i', '', $row);//remove tr
		$res = preg_replace('/<td[^>]*>/i', '', $res);//specify td
		$res = preg_replace('/<\/td[^>]*>/i', '1__2', $res);//remove closing td
		$res = preg_replace('/<\/?[^>]*>/i', '', $res);//remove other tags

		$res = sss($res);
		$a = preg_split('/1__2/', $res);

		//var_dump($a);

		$r_instrument = trim($a[1]);
		if($r_instrument == '0')
		{
			return NULL;
		}
		$r_dateFiled = travisDateToMySQLDate(trim($a[4]));
		$r_document = trim($a[5]);
		$r_r = trim($a[6]);
		$r_grantor = adjustPlusSign(trim($a[7]));
		$r_e = trim($a[8]);
		$r_grantee = adjustPlusSign(trim($a[9]));
		$r_legalDesc = trim($a[10]);
		$r_status = trim($a[11]);

		$address = removeAddressPiecesTravis(array($r_legalDesc));//legal_desc
		$r_fixedAddress = array("", "", "", "");
		if(count($address) >= 1 && strlen($address[0]) > 0){
			$r_fixedAddress = getGoogleAddress($iMacrosHandle, $address);
		}

		$currQueryValues = '("' . $r_instrument . '", "' . $r_dateFiled . '", "' . $r_document . '", "' . $r_r . '", "' .
		$r_grantor . '", "' . $r_e . '", "' . $r_grantee . '", "' . $r_legalDesc . '", "' . $r_status . '", "' .
		$r_fixedAddress[0] . '", "' . $r_fixedAddress[1] . '", "' . $r_fixedAddress[2] . '", "' . $r_fixedAddress[3] . '")';
	}

	return array("1" => $currQueryValues, "2" => $extractedDetailLink);
}

function getGoogleAddress($iMacrosHandle, $address){
	//@TODO did you mean

	global $NEW_LINE, $NEW_PAGE_WAIT;

	$addressStr = "";
	for($i = 0; $i < count($address); $i++){
		$addressStr = $addressStr . " " . $address[$i];
	}
	$addressStr = $addressStr . " Travis TX";

	$addressStr = preg_replace("/\s+/", "<SP>", $addressStr);

	$gotoRecordMacro = 	"TAB T=1".$NEW_LINE.
	"TAG POS=1 TYPE=INPUT:TEXT FORM=ID:q_form ATTR=ID:q_d CONTENT=" . $addressStr .$NEW_LINE.
	"TAG POS=1 TYPE=BUTTON:SUBMIT FORM=ID:q_form ATTR=ID:q-sub".$NEW_LINE.
	//"WAIT SECONDS=".$NEW_PAGE_WAIT.$NEW_LINE.
	"TAG XPATH=\"//div[@id='panel']\" EXTRACT=TXT";

	$s = $iMacrosHandle->iimPlay("CODE:".$gotoRecordMacro);

	$extractedValue = $iMacrosHandle->iimGetLastExtract(1);

	//eliminate extra stuff from googl website
	$extractedValue = preg_replace("/<[^>]+>/", "", $extractedValue);


	$arr = preg_split("/\\n/", $extractedValue);
	$addrArr = array("", "", "", "");
	$txIndex = -1;
	for($i = count($arr) - 1; $i >= 0 && $txIndex < 0; $i--){
		if(hasGoogleRandomWords($arr[$i]) ||
		(($i>=1) && (hasGoogleRandomWords($arr[$i - 1]))) ||
		(($i<=(count($arr) - 2)) && (hasGoogleRandomWords($arr[$i + 1])))
		)
		{
			;//do nothing
		}else{
			if(strpos($arr[$i], " TX ") !== false){
				$txIndex = $i;
			}
		}
	}
	if($txIndex > 0){
		$addrArr[0] = trim($arr[$txIndex - 1]);//street
		$commaIndex = strpos($arr[$txIndex], ",", 0);
		$addrArr[1] = substr($arr[$txIndex], 0, $commaIndex);//city
		$addrArr[2] = substr($arr[$txIndex], $commaIndex + 2, 2);//state
		$addrArr[3] = substr($arr[$txIndex], $commaIndex + 5, 5);//zip
		return $addrArr;
	}else{
		return $address;
	}

	//return $addrArr;
}

function hasGoogleRandomWords($text){
	return (strpos($text, "Google") !== false) || (strpos($text, "Ad") !== false) ||
	(strpos($text, "Google") !== false) || (strpos($text, "more") !== false) ||
	(strpos($text, "Directions") !== false) || (strpos($text, "Explore") !== false) ||
	(strpos($text, "remove") !== false) || (strpos($text, "default location") !== false) ||
	(strpos($text, "Public transit") !== false) || (strpos($text, "Did you mean") !== false) ||
	(strpos($text, "Show options") !== false) || (strpos($text, "Hide options") !== false);
}

function adjustPlusSign($name){
	if( strpos($name, "(+)" ))
	{
		$name = substr($name, 0, strlen($name)-3);
		$name = fixNameOrder(trim($name));
		$name = $name . " (+)";
	}else{
		$name = fixNameOrder($name);
	}
	return $name;
}

// lo?t\s+[^\s]+    blk\s+[^\s]+   sec\s+[^\s]+
function removeAddressPiecesTravis($addresses){

	$cityRegex = getTravisCitiesRegex();
	echo '<br>';
	for($i = 0; $i < count($addresses); $i++){
		echo '<div style="color:red">'. $addresses[$i].'</div>';
		$addresses[$i] = preg_replace('/lo?t\s+[^\s]+\s/i', '', $addresses[$i]);
		$addresses[$i] = preg_replace('/blk\s+[^\s]+\s/i', '', $addresses[$i]);
		//$addresses[$i] = preg_replace('/lts\s+[^\s]+\s/i', '', $addresses[$i]);
		$addresses[$i] = preg_replace('/sec\s+[^\s]+\s/i', '', $addresses[$i]);
		$addresses[$i] = preg_replace('/(\s(tx|texas)\s\d{5})\b.*/i', '$1', $addresses[$i]);
		$addresses[$i] = preg_replace('/^[^\d]+(\d)/i', '\1', $addresses[$i]);
		$addresses[$i] = preg_replace('/^(\d+\s+)+(\d)/i', '\2', $addresses[$i]);
		$addresses[$i] = preg_replace('/^\d+$/i', '', $addresses[$i]);
		$regex = "/(\s+$cityRegex)/i";
		$addresses[$i] = preg_replace($regex, ',$1,', $addresses[$i]);
		$addresses[$i] = preg_replace('/,\s*/i', "\t", $addresses[$i]);
		$addresses[$i] = preg_replace('/\b(tx|texas)\b/i', "TX\t", $addresses[$i]);

		//if there is a space between two numbers and either side of the string contains
		//texas then that part is the address
		$tempArr = preg_split('/\d \d/', $addresses[$i]);
		for($j = 0; $j < count($tempArr); $j++){
			if(preg_match('/(tx|texas)/i', $tempArr[$j])){
				$addresses[$i] = $tempArr[$j];
			}
		}
		$addresses[$i] = preg_replace('/^[^\d]+(\d)/i', "$1", $addresses[$i]);

		//if an address doesn't contain a number then it is not an address.
		if(!preg_match("/\d/", $addresses[$i])) {
			$addresses[$i] = "";
		}

		//if size is less than 8 chars then its not an address
		if(strlen($addresses[$i])<8){
			$addresses[$i] = "";
		}
		echo $addresses[$i];

		echo '<br>';echo '<br>';
		return preg_split("/\t/", $addresses[$i]);
	}
}

/**
 * remove lot blk:    lo?t\s+[^\s]+    blk\s+[^\s]+   sec\s+[^\s]+
 * @param $a
 */
function getTravisCitiesRegex(){
	$traviscities = array(
    "AUSTIN",       "BUDA",         "CEDAR CREEK",      "CEDAR PARK",
    "COUPLAND",     "DEL VALLE",    "DRIPPING SPRINGS", "ELGIN",
    "HUTTO",        "LEANDER",      "MC NEIL",          "MANCHACA",
    "MANOR",        "MARBLE FALLS", "ROUND ROCK",       "SPICEWOOD",
    "PFLUGERVILLE", "LAGO VISTA",   "LAKEWAY",          "GEORGETOWN",
    "AUSITN",       "SPICEWOOD",    "EL VALLE",		"JONESTOWN",
    "THE HILLS",    "DEL VALLEY",   "PLUGERVILE"
    );

    $cityList = "(Austin";
    for($i=0;$i<count($traviscities); $i++){
    	$traviscities[$i] = preg_replace('/\s+/', '\s+', $traviscities[$i]);
    	$cityList = $cityList . "|" . $traviscities[$i];
    }
    $cityList = $cityList . ")(\s*CITY)?";
    return $cityList;
}




?>

