<?php

    include "define.php";

    ini_set('memory_limit', '256M');

    $batchsize = 8000;

    list($docsalreadyinindex,$indexsize) = getexistingdocs($docsindex,$EQgetallids);

    if($indexsize > 0){
        $docsalreadyinindex = "WHERE jobid NOT IN ($docsalreadyinindex)";
    }

    print "<FONT CLASS=small><FONT CLASS=boldwords>$indexsize</FONT> docs are already in index...</FONT><BR>";

    $status = true;
    $sql = "SELECT jobid,jobtitle,jobcompany,joblocation,jobdate,jobtext FROM jobs $docsalreadyinindex LIMIT $batchsize";
    $result = $conn->query($sql);


    if ($result->num_rows > 0) {
        print "<FONT CLASS=small>indexing the next $batchsize...</FONT><BR>";

        while($status && list($docid,$doctitle,$doccompany,$doclocation,$docdate,$doctext) = mysqli_fetch_row($result)) {
            $newDate = date("Y-m-d", strtotime($docdate));

            $jsonobj = array();
        	$jsonobj["docdate"]        = $newDate;
        	$jsonobj["doctext"]        = utf8_encode($doctext);
            $jsonobj["doclocation"]    = utf8_encode($doclocation);
            $jsonobj["doccompany"]     = utf8_encode($doccompany);
            $jsonobj["doctitle"]       = utf8_encode($doctitle);
            $jsonobj["type"] = "job";

            $status = adddoctoindex($docsindex,$docid,$jsonobj);
            
            if($status){
                print "<FONT CLASS=small><B>$docid</B> has been <B>sucessfully</B> indexed...</FONT><BR>";
            }
            else{
                print "<FONT CLASS=small><B>$docid</B> has error with indexing ($status)...</FONT><BR>";
            }
        }
    }
    else{
        print "<FONT CLASS=small><B>no more</B> un-indexed docs...</FONT><BR>";
    }


    function getexistingdocs($docsindex,$EQgetallids){
        $existingdocs = "";

        $countdoc = 0;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,"$docsindex/_search");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $EQgetallids);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: 0')
        );    
        $output=curl_exec($ch);
        curl_close($ch);
        $jsonresponse = json_decode($output);
        foreach($jsonresponse->{'hits'}->{'hits'} as $jobinindex){
            $docid = $jobinindex->{'_id'};
            $existingdocs = $existingdocs."'$docid', ";
            $countdoc++;
        }

        $existingdocs = preg_replace("/, $/", "", $existingdocs);

        return array($existingdocs,$countdoc);
    }
    function adddoctoindex($docsindex,$docid,$docobj){

        $docstr = json_encode($docobj);
        file_put_contents("_elasticfeeder.json", $docstr);

        $ch = curl_init("$docsindex/_doc/$docid");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $docstr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($docstr))
        );

        $result = curl_exec($ch);
        return $result;
    }

?>