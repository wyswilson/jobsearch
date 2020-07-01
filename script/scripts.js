var requestobj1;					
var requestobj2;
var requestobj3;
var Crypt = new Crypt();

function queryconstructor(){
    var keywords    = $("#keywords").val();
    var location  	= $("#location").val();
    var company  	= $("#company").val();
    var sortmode  	= $("#sortmode").val();

    var qstring1 =  "&s=" + sortmode + "&q=" + keywords + "&l=" + location + "&c=" + company;
    var qstring2 = "[" + sortmode + "][" + keywords + "][" + location + "][" + company + "]";

    return new Array(qstring1,qstring2);
}
function timenow(){
	var dateobj = new Date();
	var secstr 	= dateobj.getSeconds();
	var hourstr = dateobj.getHours();
	var minstr 	= dateobj.getMinutes();
	var datestr = dateobj.getDate();
	var monthstr= dateobj.getMonth();
	var yearstr = dateobj.getFullYear();
	if(datestr.toString().length == 1){
		datestr = "0" + datestr;
	}
	if(monthstr.toString().length == 1){
		monthstr = "0" + monthstr;
	}
	if(yearstr.toString().length == 1){
		yearstr = "0" + yearstr;
	}
	if(hourstr.toString().length == 1){
		hourstr = "0" + hourstr;
	}
	if(minstr.toString().length == 1){
		minstr = "0" + minstr;
	}
	if(secstr.toString().length == 1){
		secstr = "0" + secstr;
	}
	var fullstr = yearstr + "-" + monthstr + "-" + datestr + " " + hourstr + ":" + minstr + ":" + secstr;
	return fullstr;
}
function printstatus(userid,sessionid,searchid,status){
	$("#tracking").html("<FONT CLASS=boldwords>SESSIONID</FONT>: "+ sessionid + "<BR><FONT CLASS=boldwords>USERID</FONT>: "+ userid + "<BR><FONT CLASS=boldwords>SEARCHID</FONT>: " + searchid + "<BR><FONT CLASS=boldwords>LOGGING</FONT>: " + status);		
}
function track(jobid,position){
	var userid 		= $("#userid").val();
	var sessionid 	= $("#sessionid").val();
	var searchid 	= $("#searchid").val();
	var nhits		= $("#nhits").val();
	var parameters	= queryconstructor();
	var querystr 	= parameters[1];

	var logstr = "[" + timenow() + "][" + userid + "][" + sessionid + "][" + searchid + "][" + nhits + "][" + jobid + "][" + position + "][1][1]" + querystr;

	var url = "logger.php";

	requestobj3 = $.ajax({
		type: 'GET',
	    url: url + '?s=' + logstr,
	    async: true,
	    cache: false
	});
	
	requestobj3.done(function(obj){
		printstatus(userid,sessionid,searchid,obj.status + ' [' + jobid + ']');
	});
	
	requestobj3.fail(function(jqXHR, textStatus) {
		alert( "request failed: " + textStatus );
	});

}
function readcookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
function filter(page,type,id){
	
	if(type == 'company'){
		$("#company").val(id);
	}
	search(page,'0');
}
function resort(page,sortmode){
	$("#sortmode").val(sortmode);
	search(page,'0');
}
function disableautocorrect(keywords){
	$("#keywords").val(keywords);
	search('1','0');
}
function search(page,autocorrect){
	var url1		= "search.php";
	var timestamp	= timenow();
	var querystr 	= queryconstructor()[0];
	querystr 		= '?p=' + page + "&t=" + timestamp + "&ac=" + autocorrect + querystr

	var requestobj1 = $.ajax({
		type: 'GET',
	    url: url1 + querystr,
	    async: true,
	    cache: false
	});
	
	requestobj1.done(function(obj){
		$("#results").html(obj.usermessage + obj.results);
		$("#jobcountpanel").html(obj.nhits);
		$("#paginationpanel").html(obj.pagination);
		$("#sortingpanel").html(obj.sorting);
		$("#companypanel").html(obj.companypanel);

		printstatus(obj.userid,obj.sessionid,obj.searchid,obj.logstatus);
		
		$("#sessionid").val(obj.sessionid);
		$("#searchid").val(obj.searchid);
		$("#userid").val(obj.userid);
		$("#sortmode").val(obj.sortmode);
		$("#nhits").val(obj.nhits);
	});
	
	requestobj1.fail(function(jqXHR, textStatus) {
		alert( "request failed: " + textStatus );
	});
}
