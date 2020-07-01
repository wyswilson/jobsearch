<?php

    include "define.php";

    $originalkeywords 	= $_GET['q'];
    $location           = $_GET['l'];
    $company            = $_GET['c'];
    $page 		        = $_GET['p'];
    $timestamp          = $_GET['t'];
    $sortmode           = $_GET['s'];
    $autocorrect        = $_GET['ac'];

    $keywords = $originalkeywords;

    $pagefrom = ($page*$pagesize)-$pagesize;

    list($elasticquery,$sortorder) = constructelasticquery($pagefrom,$pagesize,$sortmode,$keywords,$location,$company);

    $userid     = generateuserid();
    $sessionid  = generatesessionid();
    $searchid   = generatesearchid(array($keywords,$location,$company,$sortorder));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"$docsindex/_search");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $elasticquery);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')
    );
    $output=curl_exec($ch);
    curl_close($ch);
    $jsonresponse = json_decode($output);

    $countjobs  = $jsonresponse->{'hits'}->{'total'}->{'value'};
    $facetlocs  = array();
    $results    = "";

    $logstatus  = "";
    $position = (($pagesize*$page)-$pagesize)+1;
    foreach($jsonresponse->{'hits'}->{'hits'} as $docinresult){
    	$score = $docinresult->{'_score'};
        if($score == ''){
            $score = "noscore";
        }
        $docid      = $docinresult->{'_id'};
        $doctext    = $docinresult->{'_source'}->{'doctext'};
        $docdate    = $docinresult->{'_source'}->{'docdate'};
        $doctitle 	= $docinresult->{'_source'}->{'doctitle'};
        $doclocation= $docinresult->{'_source'}->{'doclocation'};
        $doccompany = $docinresult->{'_source'}->{'doccompany'};

        list($locsforfacets,$granularloc) = getlocationparts($facetlocs,$doclocation);
        $facetlocs = $locsforfacets;

        $results = $results."<div class=\"item\">\n";
        $results = $results."   <div class=\"content\">\n";
        $results = $results."       <div onclick=\"track('$docid','$position')\" CLASS=\"ui small header\" STYLE=\"width:42%; cursor:pointer;\">$position. $doctitle <span style='float:right'>$docdate</span>\n";
        $results = $results."       </div>\n";
        $results = $results."       <div>\n";
        $results = $results."       <i>$doclocation</i>\n";
        $results = $results."       </div>\n";
        $results = $results."       <div>";
        $doccompanyesc = preg_replace('/<.+?>/', '', $doccompany);
        $results = $results."<A HREF=\"javascript:filter('$page','company','$doccompanyesc')\" class=\"ui blue label\">$doccompanyesc</A>\n";
        $results = $results."       </div>\n";
        $results = $results."   </div>\n";
        $results = $results."</div>\n";


        $logstr = "[$timestamp][$userid][$sessionid][$searchid][$countjobs][$docid][$position][1][0][$sortorder][$keywords][$location][$company]";
        $logstatus = logevent($conn,$stoplogging,$logstr);

        $position++;
    }



    $i = 0;
    $facetcompsdisp = "";
    $facetcompsdisp = $facetcompsdisp."<div class=\"menu\">";
    $facetcompsdisp = $facetcompsdisp."<a class=\"header item\">COMPANIES</a>";
    $facetcompsdisp = $facetcompsdisp."<A class=\"item\" HREF=\"javascript:filter('$page','company','')\">clear company filter</a>";
    foreach($jsonresponse->{'aggregations'}->{'doccompanies'}->{'buckets'} as $facet1){
        if($i < 5){
            $value      = $facet1->{'key'};
            $doccount   = $facet1->{'doc_count'};
            $activateoption = "";
            if($company == $value){
                $activateoption = " active";
            }
            $compname = substr($value,0,20);

            $facetcompsdisp = $facetcompsdisp."<a class=\"teal item$activateoption\" HREF=\"javascript:filter('$page','company','$value')\">";
            $facetcompsdisp = $facetcompsdisp."$compname";
            $facetcompsdisp = $facetcompsdisp."<div class=\"ui blue left pointing label\">$doccount</div>\n";
            $facetcompsdisp = $facetcompsdisp."</a>";
            $i++;
        }
    }
    $facetcompsdisp = $facetcompsdisp."</div>";

    $pagination = "<div class=\"ui pagination menu\">";
    $pagination = $pagination.getpagination($countjobs,$page,$pagesize);
    $pagination = $pagination."</DIV>";

    $results = "<div class=\"ui items\">$results</div>";


    $sorting = "<div class=\"menu\">";
    $sorting = $sorting."<a class=\"item header\">SORT BY</a>";
    if($sortorder == 'relevance'){
        $sorting = $sorting."<a class=\"item\" HREF=\"javascript:resort('$page','relevance')\">most relevant first</A>";
        $sorting = $sorting."<A class=\"item\" HREF=\"javascript:resort('$page','date')\">most recent first</A>";
    }
    else{
        $sorting = $sorting."<A class=\"item\" HREF=\"javascript:resort('$page','relevance')\">most relevant first</A>";
        $sorting = $sorting."<A class=\"item\" HREF=\"javascript:resort('$page','date')\">most recent first</A>";
    }
    $sorting = $sorting."</div>";


    $showjobscount = number_format($countjobs)." JOBS FOUND";

    $usermessage = "";
    if("$originalkeywords" != "$keywords"){
        $usermessage = "Your keywords are corrected to <I><B>$keywords</B></I>. To search using your original keywords, click <a class=\"ui blue label\" onClick=\"disableautocorrect('$originalkeywords')\"><B>$originalkeywords</B></a>.";
    }
    elseif($countjobs == 0){
        $usermessage = "I can't find any results for <I>$keywords</I>";
        if($location != ''){
            $usermessage = $usermessage." in <i>$location</i>.";
        }
        else{
            $usermessage = $usermessage.".";
        }
    }

    header('Content-type: application/json');
    header('Access-Control-Allow-Origin: *');

    $responseobj = array ('usermessage'=>$usermessage,'results'=>$results, 'companypanel'=>$facetcompsdisp, 'nhits'=>$showjobscount,'pagination'=>$pagination,'sorting'=>$sorting,'sortmode'=>$sortorder,'userid'=>$userid,'sessionid'=>$sessionid,'searchid'=>$searchid, 'logstatus'=>$logstatus);
    echo json_encode($responseobj);

?>