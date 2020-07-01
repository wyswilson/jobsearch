<?php
	include "./define.php";
?>
<html>
<head>
	<meta charset="utf-8" />
	  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	<title></title>
	<link rel='shortcut icon' href='script/favicon.ico' type='image/x-icon'/ >
	<script type="text/javascript" src="https://code.jquery.com/jquery-1.10.2.js"></script>
	<script type="text/javascript" src="./script/cryptography.js"></script>  
	<script type="text/javascript" src="./script/scripts.js"></script>
	<script type="text/javascript" src="./script/jquery.cookie.min.js"></script>

	<link rel="stylesheet" type="text/css" href="./script/semantic-ui/semantic.min.css">
	<script src="./script/semantic-ui/semantic.min.js"></script>

	<script>
		$(function() {
		    $('#toggle-sidebar').click(function() {
		      $('.menu.sidebar').sidebar('toggle');
		    });
		});
		</script>
</head>

<body>

<div class="ui inverted top attached demo menu">
  <a class="item" id="toggle-sidebar">
      <i class="sidebar icon"></i>
      SEARCH
  </a>
  <a class="item" id=jobcountpanel>
    <?php echo $alljobscount?> JOBS AVAILABLE
  </a>
</div>

<div class="ui bottom attached segment pushable">
	<div class="ui inverted left vertical menu sidebar">
	    <a class="item ui input">
			<input type="text" id="keywords" name="keywords" placeholder="role title, skills, etc">
	    </a>
	    <a class="item ui input">
			<input type="text" id="location" name="location" placeholder="location" VALUE="<?php echo $defaultsearchcountry?>">
		</a>
		<a class="item ui input">
	  		<div class="ui blue submit button" onclick="search('1','0')">SEARCH</div>
		</a>
		<div class="item" id=sortingpanel></div>
		<div class="item" id="companypanel"></div>
	</div>
	<div class="pusher">
		<div class="ui raised segment" id="results"></div>
		<div class="ui raised segment" id="paginationpanel"></div>
		<?php 
	    	if($debugmode == 'monkey'){
	   	?>
	    <div class="ui raised segment" id="tracking">
	    </div>
	    <div class="ui raised segment">
	    	<?php
	    		echo $sqlerrormsg;
	    	?>
	    </div>
	    <?php
	    	}
	    ?>
	</div>
</div>

	<input type="hidden" id="company" value="">
	<input type="hidden" id="searchid" value="">
	<input type="hidden" id="sessionid" value="">
	<input type="hidden" id="userid" value="">
	<input type="hidden" id="nhits" value="">
	<input type="hidden" id="sortmode" value="relevance">
</body>
</html>