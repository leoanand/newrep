<?
/* This cron file should run every night to save the billable leads in DB */
$LOCK = "savebillableleads.lock";
$lockfp = fopen($LOCK,"a");
if(!$lockfp || !flock($lockfp, LOCK_EX | LOCK_NB)){
    //if couldn't get lock, exit
    echo "Already processing savebillableleads. Exiting.";
    exit;
}
set_time_limit(0);
include("../config/configdetails.php");
include("../include/systemfunctions.php");
include("../include/sharedfunctions.php");
$fromtime=mktime(0,0,0,date("m"),date("d"),date("Y"));
$totime=mktime(23,59,59,date("m"),date("d"),date("Y"));
$resLeads=myQuery("select * from leads where status='1' AND instime between $fromtime AND $totime");
while($obj=mysql_fetch_array($resLeads)){
	$resLeadData=myQuery("select state,phone from leaddata where leid=$obj[leid] AND upgraded>0 ");
	$objLeadData=mysql_fetch_array($resLeadData);
	if(trim($objLeadData[state])==""){
		$phone=$objLeadData["phone"];
		list($city,$newstate,$zip)=getLeadZipCityStateFromPhone($phone);
		$state=$newstate;
	}else $state=$objLeadData[state];
	$state=trim($state);
	$newval=$narr[$state];
	$newval++;
	$ltid=$obj[ltid];
	$paid=$obj[paid];
	$newval=$narr[$ltid][$paid][$state];
	$newval++;
	$narr[$ltid][$paid][$state]=$newval;
	//$statearrnew["$state"]=$statearr[$state]+1;
}


foreach ($narr as $ltidkey => $ltarr) {
	# code...
	foreach($ltarr as $paidkey=>$paidarr){

		foreach($paidarr as $stkey=>$stateval){
			myQuery("insert into savebillableleads set ltid=$ltidkey,paid=$paidkey,state='$stkey',cntleads='$stateval',timerange='$totime'");
		}
	}
}
?>