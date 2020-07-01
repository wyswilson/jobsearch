<?php

    error_reporting(E_ERROR | E_PARSE);

    $docsindex      = "http://127.0.0.1:9200/jobs";
    $pagesize       = 5;
    $stoplogging    = "false";
    $sqlerrormsg    = "";
    $defaultsearchcountry = '';
    $docsrcurl      = "https://www.postjobfree.com";

    $conn = new mysqli("127.0.0.1:3307", "root", "", "jobsearch");
    if ($conn->connect_error) { $sqlerrormsg = $conn->connect_error; }

    $EQgetallids = <<<EOD
    { 
        "query" : { 
            "match_all" : {}
        },
        "size": 10000,
        "_source": ["_id"]
    }
    EOD;

    $alljobscount = getindexsize($docsindex,$EQgetallids);

    $debugmode = $_GET['2178daj1438fh123fk'];

    function getindexsize($docsindex,$EQgetallids){
        $ch2 = curl_init();
        curl_setopt($ch2,CURLOPT_URL,"$docsindex/_search");
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $EQgetallids);
        curl_setopt($ch2,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array("Content-Type: application/json")
        );    
        $output=curl_exec($ch2);
        curl_close($ch2);
        $jsonresponse2 = json_decode($output);

        $alljobscount = number_format($jsonresponse2->{'hits'}->{'total'}->{'value'});

        return $alljobscount;
    }

    function constructelasticquery($pagefrom,$pagesize,$sortmode,$keywords,$location,$company){

        $equerypage     = "\"from\" : $pagefrom, \"size\" : $pagesize";
        $equerykeywords = "";
        $equerylocation = "";
        $equeryfilter   = "";
        $equerysort     = "\"sort\": { \"_score\": { \"order\": \"desc\" }}";
        $equeryfacets   = "";

        $equeryfacets   = 
    <<<EOD
       "aggs": {
            "doccompanies": {
                 "terms": {"field": "doccompany"}
            }
       }
    EOD;

        if($location != ""){
            $equerylocation =
    <<<EOD
        {
            "match": {
                "doclocation": {
                  "query": "$location"
                }
            }
        }
    EOD;
        }            

        if($keywords != ""){
            $equerykeywords =
    <<<EOD
        {
          "multi_match": {
            "query": "$keywords",
            "fields": [
              "doctitle",
              "doctext"
            ],
            "type" : "phrase",
            "slop": 1
          }
        }
    EOD;
        }

        if($keywords == "" || $sortmode == 'date'){
            $equerysort = "\"sort\": { \"docdate\": { \"order\": \"desc\" }}";
            $sortmode = "date";
        }
        
        if($company != ''){
            $equeryfilter = ",\"filter\":[ { \"term\" : { \"doccompany\": \"$company\"} } ]";
        }

        $combinedkeywordlocquery = "";
        if($equerykeywords != '' && $equerylocation != ''){
            $combinedkeywordlocquery = "$equerykeywords,$equerylocation";
        }
        elseif ($equerykeywords != '') {
            $combinedkeywordlocquery = "$equerykeywords";
        }
        elseif ($equerylocation != '') {
            $combinedkeywordlocquery = "$equerylocation";
        }

        $searchquery    = 
    <<<EOD
        {
            $equerypage,
            "query" : {
                "bool": {
                    "must": [
                        $combinedkeywordlocquery
                    ]
                    $equeryfilter
                }
            },
            $equeryfacets,
            $equerysort
        }
    EOD;
        file_put_contents("_elasticquery.json", $searchquery);

        return array($searchquery,$sortmode);
    }

    function logevent($conn,$stoplogging,$logstr){
    	preg_match_all('/\[(.*?)\]/',$logstr,$matches);

    	$timestamp 	= $matches[1][0];
    	$userid 	= $matches[1][1];
    	$sessionid 	= $matches[1][2];
    	$searchid 	= $matches[1][3];
        $nhits      = $matches[1][4];
    	$jobid 	    = $matches[1][5];
    	$position 	= $matches[1][6];
    	$impression	= $matches[1][7];
    	$click		= $matches[1][8];
        $sortmode   = $matches[1][9];
        $keywords   = $matches[1][10];
        $location   = $matches[1][11];
        $company    = $matches[1][12];
        $useripaddr = $_SERVER['REMOTE_ADDR'];
        $useragent  = $_SERVER['HTTP_USER_AGENT'];

        $uniqueid = md5($userid.$sessionid.$searchid.$jobid.$position);

        $status = "";
        if($impression == 1 && $click == 0 && $stoplogging == "false"){
            $sql = "INSERT INTO logs (id,timestamp, userip, useragent, userid, sessionid, searchid, querykeywords, querylocation, querycompany, sortmode, retrievalsize, jobid, rank, impression, click) VALUES ('$uniqueid','$timestamp', '$useripaddr', '$useragent', '$userid', '$sessionid', '$searchid', '$keywords', '$location', '$company', '$sortmode', $nhits, '$jobid', $position, 1, 0)";
            if ($conn->query($sql)) {
                $status = "impressions logged at $timestamp";
            }
            else {
                $status = "err [".$conn->error."] with query [$sql]";
            }
        }
        elseif($impression == 1 && $click  == 1 && $stoplogging == "false"){
            $sql = "UPDATE logs SET click = $click, timestamp = '$timestamp' WHERE id = '$uniqueid'";
            if ($conn->query($sql)) {
                $status = "click registered at [$position] at $timestamp";
            }
            else {
                $status = "err [".$conn->error."] with query [$sql]";
            }
        }
        else{
            $status = "err logging click";
        }
    	

    	return $status;
    }

    function generatesearchid($searchparams){
        $str = "";
        foreach($searchparams as $param){
            $str = $str.$param;
        }
        $searchid = md5($str);

        return $searchid;
    }

    function generatesessionid(){
        $userid = $_COOKIE["jobsearchuser"];
        #if(!isset($_COOKIE["jobsearchsession"])) {
            $currenttime = date("YmdHi");
            $sessionid = md5($userid.$currenttime);
            
            setcookie("jobsearchsession", $sessionid, time() + (1800), "/"); // 1800 = 1/2 hr
            return $sessionid;
        #}
    }

    function generateuserid(){
        $userid = $_COOKIE["jobsearchuser"];
        if(!isset($userid)) {
            $uagent     = $_SERVER['HTTP_USER_AGENT'];
            $ipaddr     = $_SERVER['REMOTE_ADDR'];
            $userid     = md5($uagent.$ipaddr);
            
            setcookie("jobsearchuser", $userid, time() + (86400 * 5000), "/"); // 86400 = 1 day
        }
        return $userid;
    }

    function getpagination($countjobs,$currentpage,$pagesize){
    	$pages = "";
        $paginationwindow = 1;
        $lastpage = ceil($countjobs/$pagesize);
        $rightlimit = $currentpage + $paginationwindow;
        $leftlimit = $currentpage - $paginationwindow;
        
        if($leftlimit < 1){
            $leftlimit = 1;
        }

        if($rightlimit >= $lastpage){
            $rightlimit = $lastpage;
        }
        elseif($currentpage < $paginationwindow){
            $rightlimit = $paginationwindow;
        }

        $pages = " <A HREF=\"javascript:search('$currentpage','1')\" CLASS=\"item active\">$currentpage</A> ";

        for($i = $currentpage-1; $i >= $leftlimit; $i--){
            $pages = "<A HREF=\"javascript:search('$i','1')\" CLASS=item>$i</A> ".$pages;
        }
        
        if($currentpage > 1){
            $pages = "<A HREF=\"javascript:search('".($currentpage-1)."','1')\" CLASS=\"icon item\"><i class=\"angle left icon\"></i></A>".$pages;
        }

        for($i = $currentpage+1; $i <= $rightlimit; $i++){
    		$pages = $pages." <A HREF=\"javascript:search('$i','1')\" CLASS=item>$i</A>";
    	}

        if($currentpage < $lastpage-1){
            $pages = $pages."<A HREF=\"javascript:search('".($currentpage+1)."','1')\" CLASS=\"icon item\"><i class=\"angle right icon\"></i></A>";
        }

    	return $pages;
    }

    function getlocationparts($locs,$location){
        $loc = $location;    
        $loc = trim($loc);
        if($loc != '' && !is_numeric($loc)){
            $locs[$loc]++;
        }
        
        return array($locs,$location);
    }
?>