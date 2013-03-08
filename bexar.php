
<?php
//add time limit to make sure it can cover the whole dta retrieval time period
set_time_limit(24*60*60);
require_once('libImacros.php');
require_once('lib.php');
require_once('libParameters.php');
$link = getMySQLLink();

$macro_BexarPropertyinfoNavigate = getMacro('Bexar_Propertyinfo_Navigate');
$macro_BexarPropertyinfoNextPage = getMacro('Bexar_Propertyinfo_NextPage');
$macro_BexarPropertyinfoLastPage = getMacro('Bexar_Propertyinfo_LastPage');

//$date = "09/12/2011";
$date = $BEXAR_DATE;

$iim1->iimSet("-var_fromDate", $date);
$iim1->iimSet("-var_toDate", $date);
$s = $iim1->iimPlay( "CODE:".$macro_BexarPropertyinfoNavigate);

//echo "iimplay=".$s; echo "extract=".$iim1->iimGetLastExtract;

$isLastPage = false;
$scriptPosVal = 1;
do{ //loop through the whole table until nothing to extract

	do{
		$recordInsertQuery = getGeneralRecord($iim1, $scriptPosVal);
		if($recordInsertQuery != NULL){
			if(mysql_query($recordInsertQuery))
			echo 'insert success at ';
			else
			echo 'insert fail at ';
			echo $recordInsertQuery ."<br><br>\n";

			$detailInsertQuery = getDetailedRecordInfo($iim1, $scriptPosVal);
			if($detailInsertQuery != NULL){
				if(mysql_query($detailInsertQuery))
				echo 'insert success at ';
				else
				echo 'insert fail at ';
				echo $detailInsertQuery ."<br><br>\n";
			}
		}
		$scriptPosVal++;
	}while( $recordInsertQuery != NULL);//strstr($extractedValue, $EANF) == false);
	//}while(false);
	$scriptPosVal--;
	$isLastPage = isLastPage($iim1);
	if(!$isLastPage){
		$iim1->iimSet("-var_newPageWaitTime","10" );
		$s = $iim1->iimPlay( "CODE:".$macro_BexarPropertyinfoNextPage);
	}else{
		$query = "INSERT INTO `done_dates` (`county`, `date`, `status`, `report_time`,`desc`) values (2,'" . $date . ", 1, now(),'');";
		if(mysql_query($query)){
			mylog(1, 'insert success at '. $query ."<br><br>\n");
		}
		else{
			mylog(1, 'insert fail at '. $query ."<br><br>\n");
		}
	}

}while($isLastPage == false);
//}while(false);

mysql_close($link);

$s = $iim1->iimExit();
set_time_limit(0);

//========================================================================================

function isLastPage($iMacrosHandle){
	global $macro_BexarPropertyinfoLastPage, $EANF;
	$s = $iMacrosHandle->iimPlay( "CODE:".$macro_BexarPropertyinfoLastPage);
	$extractedValue = $iMacrosHandle->iimGetLastExtract;
	if(strstr($extractedValue, $EANF) == false){
		return false;
	}
	return true;
}

function getDetailedRecordInfo($iMacrosHandle, $recordIndex){

	global $NEW_LINE, $NEW_PAGE_WAIT, $QNS, $QSS;

	//$gotoRecordMacro = 'TAG XPATH="//td[@id=\'' . $recordIndex . '-1\']/a/img"'.$NEW_LINE.
	//'DS CMD=LDBLCLK X={{!TAGX}} Y={{!TAGY}} CONTENT=' . $NEW_LINE.
	//"TAB T=2";
	$gotoRecordMacro = 'TAG POS=1 TYPE=IMG ATTR=ID:img_icon_index_'.$recordIndex.$NEW_LINE.
	'FRAME F=6';//

	$s = $iMacrosHandle->iimPlay("CODE:".$gotoRecordMacro);

	$extractRecordDetailMacro = getMacro("Bexar_Propertyinfo_ExtractRowDetailed");
	$s = $iMacrosHandle->iimPlay("CODE:".$extractRecordDetailMacro);

	$backFromRecordMacro = '//TAG POS=1 TYPE=IMG ATTR=ID:img_button_close_index';
	$s = $iMacrosHandle->iimPlay("CODE:".$backFromRecordMacro);//closes popup of the record detail


	$query = "INSERT INTO `bexar_propertyinfo_details` (`document_number` ,`book_number` ,`filed_date` ,".
	"`instrument_type` ,`consideration_amt` ,`comment` ,`no_of_pages` ,`book_type` ,`page_number` ,".
	"`filing_time` ,`instrument_date` ,`market_source` ,`grantor` ,`grantee` ,`subdivision` ,`county_block` ,".
	"`land_description` ,`plat_book` ,`water_permit` ,`addition` ,`ncb` ,`abstract` ,`plat_page` ,`lot` ,".
	"`block`,`related_doc` ,`property_address` ,`return_address`)VALUES (";

	$r_document_number = $iMacrosHandle->iimGetLastExtract(1);
	if(hasNoData($r_document_number)){
		return NULL;
	}
	$query = $query  . '"'.  getInt(sss($r_document_number)) .$QSS;
	$r_book_number =  $iMacrosHandle->iimGetLastExtract(2);
	$query = $query  . '"'.  getInt(sss($r_book_number)) .$QSS;
	$r_filed_date =  $iMacrosHandle->iimGetLastExtract(3);
	$query = $query . '"'. travisDateToMySQLDate(sss($r_filed_date)) .$QSS;
	$r_instrument_type =  $iMacrosHandle->iimGetLastExtract(4);
	$query = $query . '"'. sss($r_instrument_type) .$QSS;
	$r_consideration_amt =  $iMacrosHandle->iimGetLastExtract(5);
	$query = $query . '"'. sss($r_consideration_amt) .$QSS;
	$r_comment =  $iMacrosHandle->iimGetLastExtract(6);
	$query = $query . '"'. sss($r_comment) .$QSS;
	$r_no_of_pages =  $iMacrosHandle->iimGetLastExtract(7);
	$query = $query . '"'. sss($r_no_of_pages) .$QSS;
	$r_book_type =  $iMacrosHandle->iimGetLastExtract(8);
	$query = $query . '"'. sss($r_book_type) .$QSS;
	$r_page_number =  $iMacrosHandle->iimGetLastExtract(9);
	$query = $query . '"'. sss($r_page_number) .$QSS;
	$r_filing_time =  $iMacrosHandle->iimGetLastExtract(10);
	$query = $query . '"'.  sss($r_filing_time) .$QSS;
	$r_instrument_date =  $iMacrosHandle->iimGetLastExtract(11);
	$query = $query . '"'.  travisDateToMySQLDate(sss($r_instrument_date)) .$QSS;
	$r_market_source =  $iMacrosHandle->iimGetLastExtract(12);
	$query = $query . '"'.  sss($r_market_source) .$QSS;
	$r_grantor =  $iMacrosHandle->iimGetLastExtract(13);
	$query = $query . '"'. sss($r_grantor) .$QSS;
	$r_grantee =  $iMacrosHandle->iimGetLastExtract(14);
	$query = $query . '"'. sss($r_grantee) .$QSS;
	$r_subdivision =  $iMacrosHandle->iimGetLastExtract(15);
	$query = $query . '"'. sss($r_subdivision) .$QSS;
	$r_county_block =  $iMacrosHandle->iimGetLastExtract(16);
	$query = $query . '"'. getInt(sss($r_county_block)) .$QSS;

	$r_land_description =  $iMacrosHandle->iimGetLastExtract(17);
	$query = $query . '"'. sss($r_land_description) .$QSS;
	$r_plat_book =  $iMacrosHandle->iimGetLastExtract(18);
	$query = $query . '"'. sss($r_plat_book) .$QSS;
	$r_water_permit =  $iMacrosHandle->iimGetLastExtract(19);
	$query = $query . '"'. sss($r_water_permit) .$QSS;
	$r_addition =  $iMacrosHandle->iimGetLastExtract(20);
	$query = $query . '"'. sss($r_addition) .$QSS;
	$r_ncb =  $iMacrosHandle->iimGetLastExtract(21);
	$query = $query . '"'. sss($r_ncb) .$QSS;
	$r_abstract =  $iMacrosHandle->iimGetLastExtract(22);
	$query = $query . '"'. sss($r_abstract) .$QSS;
	$r_plat_page =  $iMacrosHandle->iimGetLastExtract(23);
	$query = $query . '"'. sss($r_plat_page) .$QSS;

	$r_lot =  $iMacrosHandle->iimGetLastExtract(24);
	$query = $query . '"'.  sss($r_lot) .$QSS;
	$r_block =  $iMacrosHandle->iimGetLastExtract(25);
	$query = $query . '"'. sss($r_block) .$QSS;

	//------------------------------------------------------------------------
	//distinguishing related docs, property address and return address
	//if title 1 equals related then the next extracted is related  doc

	$title1 = $iMacrosHandle->iimGetLastExtract(26);
	$data1 = $iMacrosHandle->iimGetLastExtract(27);
	
	$title2 = $iMacrosHandle->iimGetLastExtract(28);
	$data2 = $iMacrosHandle->iimGetLastExtract(29);
	
	$title3 = $iMacrosHandle->iimGetLastExtract(30);
	$data3 = $iMacrosHandle->iimGetLastExtract(31);

	//any permutation to make sure we can handle any order of these three

	$pos = strpos($title1, "Related Document Information");
	if ($pos !== false) {
		$r_related_doc = $data1;
	}
	$pos = strpos($title1, "Property Address");
	if ($pos !== false) {
		$r_property_address =  $data1;
	}
	$pos = strpos($title1, "Return Address");
	if ($pos !== false) {
		$r_return_address =  $data1;
	}

	$pos = strpos($title2, "Related Document Information");
	if ($pos !== false) {
		$r_related_doc = $data2;
	}
	$pos = strpos($title2, "Property Address");
	if ($pos !== false) {
		$r_property_address =  $data2;
	}
	$pos = strpos($title2, "Return Address");
	if ($pos !== false) {
		$r_return_address =  $data2;
	}
	
$pos = strpos($title2, "Related Document Information");
	if ($pos !== false) {
		$r_related_doc = $data3;
	}
	$pos = strpos($title2, "Property Address");
	if ($pos !== false) {
		$r_property_address =  $data3;
	}
	$pos = strpos($title2, "Return Address");
	if ($pos !== false) {
		$r_return_address =  $data3;
	}
	
	//------------------------------------------------------------------------

	$query = $query . '"'. sss($r_related_doc) .$QSS;
	$query = $query . '"'. sss($r_property_address) .$QSS;
	$query = $query . '"'. sss($r_return_address) . '"';
	//$r_property_address =  $iMacrosHandle->iimGetLastExtract(27);
	//$r_return_address =  $iMacrosHandle->iimGetLastExtract(28);


	$query = $query . ")";
	return $query;
}

function getGeneralRecord($iMacrosHandle, $recordIndexInPage){
	global $EANF, $QNS, $QSS;
	$macro_ExtractRow = getMacro('Bexar_Propertyinfo_ExtractRow');
	$macro_ExtractRow = str_replace("{{macroPosVal}}", $recordIndexInPage, $macro_ExtractRow);
	//$iMacrosHandle->iimSet("-var_macroPosVal",$recordIndexInPage );
	$s = $iMacrosHandle->iimPlay( "CODE:".$macro_ExtractRow);

	$extractedValue = $iMacrosHandle->iimGetLastExtract(1);
	if(hasNoData($extractedValue)){
		return NULL;
	}

	$extractedValue = str_replace( "[EXTRACT]","",$extractedValue);

	echo $extractedValue;

	$row = $extractedValue;

	$a = stripHTMLTR($row);

	//var_dump($a);

	$r_index_detail = $a[0];
	$r_view_image = travisDateToMySQLDate($a[1]);
	$r_instrument_type = $a[2];
	$r_filed_date = travisDateToMySQLDate($a[3]);
	$r_doc_no = $a[4];
	$r_book_type = $a[5];
	$r_book = $a[6];
	//7 page already included in book
	$r_grantor = fixNameOrder($a[7]);
	$r_grantee = fixNameOrder($a[8]);
	$r_lot = $a[9];
	$r_block = $a[10];
	$r_ncb = $a[11];
	$r_county_block = $a[12];
	$r_subdivision = $a[13];

	$query = '("' . $r_index_detail . $QSS . '"' . $r_view_image . $QSS . '"' . $r_instrument_type . $QSS . '"' .  $r_filed_date . $QSS .
	'"' . $r_doc_no . $QSS . '"' .  $r_book_type . $QSS . '"' .  $r_book . $QSS . '"' .  $r_grantor . $QSS .
	'"' . $r_grantee . $QSS . '"' .  $r_lot . $QSS . '"' .  $r_block . $QSS . '"' .  $r_ncb . $QSS . '"' .  $r_county_block . $QSS .
	'"' .  $r_subdivision . '")';

	/*
	 $query = "(";
	 $r_index_detail = $iMacrosHandle->iimGetLastExtract(1);
	 $query = $query . '"'. sss($r_index_detail) .$QSS;
	 $r_view_image = $iMacrosHandle->iimGetLastExtract(2);//
	 $query = $query . '"'. sss($r_view_image) .$QSS;
	 $r_instrument_type = $iMacrosHandle->iimGetLastExtract(3);
	 $query = $query . '"'. sss($r_instrument_type) .$QSS;
	 $r_filed_date = $iMacrosHandle->iimGetLastExtract(4);
	 $query = $query . '"'. travisDateToMySQLDate(sss($r_filed_date)) .$QSS;
	 $r_doc_no = $iMacrosHandle->iimGetLastExtract(5);
	 $query = $query . '"'. sss($r_doc_no) .$QSS;
	 $r_book_type = $iMacrosHandle->iimGetLastExtract(6);
	 $query = $query . '"'. sss($r_book_type) .$QSS;
	 $r_book = $iMacrosHandle->iimGetLastExtract(7);
	 $query = $query . '"'. sss($r_book) .$QSS;
	 $r_page = $iMacrosHandle->iimGetLastExtract(7);
	 $query = $query . '"'. sss($r_page) .$QSS;
	 $r_grantor = $iMacrosHandle->iimGetLastExtract(8);
	 $query = $query . '"'. fixNameOrder(sss($r_grantor)) .$QSS;
	 $r_grantee = $iMacrosHandle->iimGetLastExtract(9);
	 $query = $query . '"'. fixNameOrder(sss($r_grantee)) .$QSS;
	 $r_lot = $iMacrosHandle->iimGetLastExtract(10);
	 $query = $query . '"'. sss($r_lot) .$QSS;
	 $r_block = $iMacrosHandle->iimGetLastExtract(11);
	 $query = $query . '"'. sss($r_block) .$QSS;
	 $r_ncb = $iMacrosHandle->iimGetLastExtract(12);
	 $query = $query . '"'. sss($r_ncb) .$QSS;
	 $r_county_block = $iMacrosHandle->iimGetLastExtract(13);
	 $query = $query . '"'. sss($r_county_block) .$QSS;
	 $r_subdivision = $iMacrosHandle->iimGetLastExtract(14);
	 $query = $query . '"'. sss($r_subdivision) .'"';
	 $query = $query . ")";
	 */

	$query = "INSERT INTO  bexar_propertyinfo (index_detail, view_image, instrument_type, ".
				"filed_date, doc_no, book_type, book, grantor, grantee, lot, block, ncb, ".
				"county_block, subdivision) VALUES" . $query . ";";

	return $query;
}

?>