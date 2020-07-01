#!c:/perl/bin/perl.exe
return TRUE;

sub striphtml{
	my($input) = @_;
	#my $hs = HTML::Strip->new();
	#my $cleanText = $hs->parse( $input );
	#$hs->eof;
	$input =~ s/<P[^<>]*>//smig;
	$input =~ s/<\/P[^<>]*>/\n/smig;
	$input =~ s/<[^<>]+>//smig;
	$input = trim($input);
	return $input;	
}
sub trim{
	my($str) = @_;
	$str =~ s/^\s+//smig;
	$str =~ s/\s+$//smig;
	$str =~ s/\n+$//smig;
	
	$str =~ s/^\s+// ;
	$str =~ s/\s+$// ;
	
	return $str;
}
sub stripnonalphanumeric{
	my($data) = @_;
	$data =~ s/[\[\]"\}\{\@\:]//smig;
	$data =~ s/[^[:ascii:]]{2}(.*?[ ]{3})/$1 /g;
	return $data;
}
sub convertdate{
	my($datestr) = @_;

	my %months = ("January"=>"01","February"=>"02","March"=>"03","April"=>"04","May"=>"05","June"=>"06","July"=>"07","August"=>"08","September"=>"09","October"=>"10","November"=>"11","December"=>"12");

	my($monthstr,$day,$year) = $datestr =~ /^(.+?) (.+?), (.+?)$/smig;

	my $month = $months{$monthstr};

	my $newdate = "$year-$month-$day";

	return $newdate;
}
sub extractmetadata{
	my($jobid,$joburl,$jobhtml) = @_;

	my($title) = $jobhtml =~ /<h1 style=\"font-size:1.1em;display:inline;\">(.+?)<\/h1>/smig;
	my($company) = $jobhtml =~ /<span id=\"CompanyNameLabel\" class=\"colorCompany\">(.+?)<\/span>/smig;
	my @locations = $jobhtml =~ /<a href=\"\/jobs.+?\" class=\"colorLocation\">(.+?)<\/a>/smig;
	my($postdate) = $jobhtml =~ /<span id=\"PostedDate\" class=\"colorDate\">(.+?)<\/span>/smig;
	my($jobtext) = $jobhtml =~ /<div class=\"normalText\">(.+?)<\/div>/smig;

	my $jobtextcleansed = stripnonalphanumeric(striphtml($jobtext));
	my $companycleansed = striphtml($company);
	my $newdate = convertdate($postdate);

	$jobtextcleansed =~ s/&nbsp;/ /smig;
	$jobtextcleansed =~ s/&#39;/'/smig;

	my $location = join(", ", @locations);

	print "ID: $jobid\n";
	print "URL: $joburl\n";
	print "title: $title\n";
	print "company: $companycleansed\n";
	print "location: $location\n";
	print "postdate: $newdate\n";
	print "jobtext: $jobtextcleansed\n";
	print "\n\n";

	my $textesc = $DBH->quote($jobtextcleansed);
	my $statement 	= "INSERT INTO jobs (jobid,jobtitle,jobcompany,joblocation,joblatlong,jobdate,jobtext) VALUES ('$jobid','$title','$companycleansed','$location','','$newdate',$textesc)";
    my $sth 	= $DBH->prepare($statement);
    $sth->execute();
}

sub getpagebyget{
	my($url) = @_;
	
	my $ua 			= initialiseuseragent();
	my $response 	= $ua->get($url);
	my $rawhtml	= $response->content;
	$rawhtml	=~ s/\n//smig;
	$rawhtml	=~ s/\s+/ /smig;
	$rawhtml	=~ s/>\s+</></smig;
	#$rawhtml	= trim($rawhtml);
	return $rawhtml;
}
sub initialiseuseragent{
	my @methodsRedirectable = ("GET","HEAD","POST");
	my $ua = LWP::UserAgent->new('max_redirect' => 5, 'requests_redirectable'=>\@methodsRedirectable);
	$ua->timeout(3600);
	$ua->proxy(["http","https"], $PROXY) if($PROXY ne "");
	$ua->agent("Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.17) Gecko/20110420 Firefox/3.6.172011-05-12 01:28:09");
	#sleep(10);
	return $ua;
}
sub readfromfile{
	my($filename,$delineator) = @_;
	
	my $fh = new IO::LockedFile({block=>1},"< $filename");
	if(defined $fh){
		my @raw = <$fh>;
		$fh->close();
		undef $fh;
		my $output = join($delineator,@raw);
		return $output;
	}
	else{
		undef $fh;
		return "IO-ERROR";
	}
}
sub storetofile{
	my($filename,$verbose,$write_mode) = @_;
	
	my $fh = "";
	$fh = new IO::LockedFile({block=>1},">> $filename") if($write_mode =~ /append/);
	$fh = new IO::LockedFile({block=>1},"> $filename") 	if($write_mode =~ /overwrite/);
	binmode($fh) 			if($write_mode =~ /binary/);
	binmode($fh,":utf8") 	if($write_mode !~ /binary/);
	if(defined $fh){
		print $fh "$verbose";
		$fh->close;
		undef $fh;
		return "IO-SUCCESS";
	}
	else{
		undef $fh;
		return "IO-ERROR";
	}
}