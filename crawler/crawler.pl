#!c:/perl/bin/perl.exe
require "routine.pl";

use POSIX;
use CGI qw/:standard/;
use CGI::Ajax 0.49;
use DBI;
use Digest::MD5 qw(md5_hex md5);
use IO::LockedFile;
use IO::File;
use IO::Dir;
use LWP::UserAgent;
use WWW::Mechanize;
use URI;
use JSON;
use Text::Unidecode;

our $DB_DSN1	= "DBI:mysql:database=jobsearch;host=127.0.0.1;port=3307";#PROD
our $DB_UNAME	= 'root';
our $DB_PWD		= '';
our $ROOT_DIR	= "C:/jobsearch";
our $SRC_URL 	= "https://www.postjobfree.com";
our $CRAWLER_DIR = "$ROOT_DIR/crawler";

our $DBH 		= DBI->connect($DB_DSN1,$DB_UNAME,$DB_PWD,{RaiseError => 0,PrintError => 0, mysql_enable_utf8 => 1});
$DBH->{mysql_auto_reconnect} = 1;

for(my $i = 1; $i <= 50;  $i++){
	my $srcpage = "$SRC_URL/latest-jobs/$i";
	print "crawling [$srcpage]\n";

	my $rawhtml  = getpagebyget($srcpage);
	my @joburls = $rawhtml =~ /<h3 class=\"itemTitle\"><a href=\"(.+?)\"/smig;
	foreach $joburl(@joburls){
		
		my $jobhtml = getpagebyget("$SRC_URL/$joburl");
		my $jobid 	= md5_hex($jobhtml);
		print "\tstoring raw job [$joburl] to [$jobid]\n";
		storetofile("$CRAWLER_DIR/rawcontent/$jobid",$jobhtml,"overwrite");
	}
}

