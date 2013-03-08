<?php

$SYSTEM_LOG_LEVEL = 0;


/**
 * no value extracted
 * @var unknown_type
 */
$EANF = "#EANF#";

/**
 *
 * @var unknown_type
 */
$NODATA = "NODATA";

/**
 * SECONDS TO WAIT FOR NEW PAGE
 * @var unknown_type
 */
$NEW_PAGE_WAIT = 4;

/**
 * $NEW_LINE = "\n";  //$NEW_LINE = "\n\r";
 * @var unknown_type
 */
$NEW_LINE = "\n";

/**
 * Query numeral separator (no quotation mark)
 * @var unknown_type
 */
$QSS = '", ';

/**
 * Query string value separator
 * @var String
 */
$QNS = ', ';

function getMacro($macro){
	$query = "SELECT `macro` FROM `imacros` WHERE `name` like '".$macro."'";
	$result = mysql_query($query);
	$row = mysql_fetch_array($result);
	return $row['macro'];
}

//clean html tags for storafe and retrieval at mysql
function sss($s){
	global $EANF, $NODATA;
	$s1 = $s;
	//$s1 = mysql_real_escape_string(addslashes($s));
	//$s1 = mysql_real_escape_string($s);
	//remove html tag attributes
	//	$sa = new StripAttributes();
	//	$sa->allow = array( 'id', 'class' );
	//	$sa->exceptions = array();
	//
	//// $sa->ignore = array();
	//	$s1 = $sa->strip( $s );
	//	$sa->ignore = array();

	//remove html attributes
	$s1 = preg_replace("$</?(\w+)\s+[^>]*>$", "<\${1}>", $s1, -1);
	//replace &nbsp; with SPACE, it could be between entries, hence space does the job
	//if  it is the only thing in the table, the next trim will do the job to remove it.
	$s1 = preg_replace("/&nbsp;/", " ", $s1, -1);
	$s1 = preg_replace("/&amp;/", "&", $s1, -1);
	$s1 = preg_replace("/".$EANF."/", "", $s1, -1);
	$s1 = preg_replace("/".$NODATA."/", "", $s1, -1);

	$s1 = trim($s1);
	$s1 = addslashes($s1);
	return $s1;
}

function getInt($s){
	global $NODATA;
	if(strlen($s) ==0 || strstr($s, $NODATA) != false)
	return 0;
	else
	return $s;
}

/**
 * Transform 06/23/2011 to 2011-06-23
 * @param $date
 */
function travisDateToMySQLDate($date){
	global $EANF;
	if(trim($date) === "" || strstr($date, $EANF) == true){
		return "";
	}
	$dateSplitted = preg_split('/\//' , $date);
	$r_dateFiled = $dateSplitted[2] . '-' . $dateSplitted[0] . '-' . $dateSplitted[1];
	return $r_dateFiled;
}

/**
 * 06/01/2011 07:59:26 PM   -> 2011-06-01 19:59:26
 * @param unknown_type $dateTime
 */
function travisDateTimeToMySQLDateTime($dateTime){
	global $EANF;
	if(trim($dateTime) === "" || strstr($dateTime, $EANF) == true){
		return "";
	}
	$dateTimeSplitted = preg_split('/ /' , $dateTime);
	$param = $dateTimeSplitted[1] .	 " " . $dateTimeSplitted[2];
	$t = date("H:i:s", strtotime($param));
	$result = travisDateToMySQLDate($dateTimeSplitted[0]) . " " . $t;
	return $result;
}

function hasNoData($value){
	global $EANF, $NODATA;
	if(strstr($value, $EANF) == true || strstr($value, $NODATA) == true){
		return true;
	}
}

function getMySQLLink(){
	$host = "localhost";
	$username = "root";
	$password = "";
	$database = "lb";

	$link = mysql_connect($host,$username,$password);
	if (!$link) {
		die('Could not connect to MySQL: ' . mysql_error());
	}
	@mysql_select_db($database) or die( "Unable to select database");
	return $link;
}

function mylog($logLevel, $message){
	global $SYSTEM_LOG_LEVEL;
	if($SYSTEM_LOG_LEVEL >= $logLevel){
		echo $message;
	}else{
		return; //run - no logs
	}
}

function fixNameOrder($name){
	$parts = preg_split('/\s+/', $name, -1);
	$separatorAdded = false;
	if(count($parts) == 2){
		$name = $parts[1] . "#" . $parts[0];
		$separatorAdded = true;
	}
	if(count($parts) == 3){
		if(count($parts) == 3 ){
			if(strlen($parts[count($parts) - 1]) <= 2 ) //middle initial
			{
				$parts[count($parts) - 2] = $parts[count($parts) - 2] . ' ' . $parts[count($parts) - 1];
				unset($parts[count($parts) - 1]);
				$name = $parts[1] . "#" . $parts[0];
				$separatorAdded = true;
			}
		}


	}
	if(!$separatorAdded)	{
		if(strlen(trim($name))>1){
			$name = $name . " #";
		}
	}
	return $name;
}

function stripHTMLTR($tr){
	//remove script tag and all inside it
	$tr = preg_replace('/<script[^>]*>([^<]|<[^\/]|<\/[^s]|<\/s[^c]|<\/sc[^r]|<\/scr[^i]|<\/scri[^p]|<\/scrip[^t]|<\/script[^>])*<\/script>/i', '', $tr);

	$tr  = preg_replace('/<\/?tr[^>]*>/i', '', $tr);//remove tr opening and tag
	$tr = preg_replace('/<td[^>]*>/i', '', $tr);//remove td tag
	$tr = preg_replace('/<\/td[^>]*>/i', '1__2', $tr);//specify closing td
	$tr = preg_replace('/<\/?[^>]*>/i', '', $tr);//remove all other tags

	$tr = sss($tr);
	$a = preg_split('/1__2/', $tr); //split based on marked td closing tags
	for($i = 0; $i < count($a); $i++)
		$a[$i] =  trim($a[$i]);
	return $a;
}

/**
 * between each opening and closing tr will return as an array element
 * assumes there is no tag attribute
 * @param unknown_type $s
 */
function getHTMLTRs($s){
	$tr = preg_replace('/<\/tr[^>]*>/i', '1__2', $s);//specify closing tr tags
	$tr = preg_replace('/<\/?[^>]*>/i', '', $tr);//remove all other tags
	$a = preg_split('/1__2/', $tr); //split based on marked td closing tags
	unset($a[count($a) - 1]);
	return $a;
}

/**
 * array of participants
 * @param $arr
 */
function getMarriedStatus($arr){
	if(count($arr) == 1){
		return "Single";
	}
	//if any two have more than 5 char common strings they are married
	for($i = 0; $i < count($arr) - 1; $i++){
		for($j = $i + 1; $j < count($arr); $j++){
			$intersection = mb_string_intersect($arr[$i], $arr[$j]);
			if(strlen($intersection) > 5){
				return "Verifd";
			}
		}
	}
	return "Multi";
}

//
/**
 * get the intersection of two strings; may not have optimal performance
 * Source:   http://php.net/manual/en/ref.strings.php
 * @param $string1
 * @param $string2
 * @param $minChars
 */
function mb_string_intersect($string1, $string2, $minChars = 5)
{
	assert('$minChars > 1');

	$string1 = trim($string1);
	$string2 = trim($string2);

	$length1 = mb_strlen($string1);
	$length2 = mb_strlen($string2);

	if ($length1 > $length2) {
		// swap variables, shortest first

		$string3 = $string1;
		$string1 = $string2;
		$string2 = $string3;

		$length3 = $length1;
		$length1 = $length2;
		$length2 = $length3;

		unset($string3, $length3);
	}

	if ($length2 > 255) {
		return null; // to much calculation required
	}

	for ($l = $length1; $l >= $minChars; --$l) { // length
		for ($i = 0, $ix = $length1 - $l; $i <= $ix; ++$i) { // index
			$substring1 = mb_substr($string1, $i, $l);
			$found = mb_strpos($string2, $substring1);
			if ($found !== false) {
				return trim(mb_substr($string2, $found, mb_strlen($substring1)));
			}
		}
	}

	return null;
}