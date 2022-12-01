<?php
require 'config.php';
Login::check('Recordings Search');

?>
<!doctype html>
<!-- ⓒ ᴍᴍxxɪɪ ʙʏ ꜱᴛᴏɪᴄ ʟɪᴛᴛʟᴇ -->
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="/favicon.ico">
<title>Recordings Search</title>
<script src="jquery.slim.min.js"></script>
<script src="recordings-search.js?<?=filemtime('recordings-search.js')?>"></script>
<link rel="stylesheet" href="styles.css?<?=filemtime('styles.css')?>">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/css/bootstrap-datepicker.min.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gijgo@1.9.10/js/gijgo.min.js" type="text/javascript"></script>
    <link href="https://cdn.jsdelivr.net/npm/gijgo@1.9.10/css/gijgo.min.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="search">
	<?php
	  if (FULL_SEARCH){ 
		$dateNow = date('Y-m-d');
		?>
 	 
		Phone/Agent ID: <input type="tel" id="search_number" pattern="^[0-9\.\-\(\) ]{4,}$" data-validation="Please enter a number of at least 4 digits."  > &nbsp;&nbsp;
		Filter by Date:<input type="checkbox" id="filterDate">
	 
		<span id="spanDate" >
	  	from : <input type="date" id="dateFrom" value="<?php echo $dateNow; ?>" onchange="checkDate()" > &nbsp;&nbsp;   
		to: <input type="date" id="dateTo" value="<?php echo $dateNow; ?>" onchange="checkDate()" >  
		<input type="text" id="status" value="uncheck">
		<input type="text" id="status2" value="">
 		</span>
 
		<button id="b_search" class="i-search" type="button">Search</button>    
 
	<?php  } else { ?>
		Phone: <input type="tel" id="search_number" pattern="^[0-9\.\-\(\) ]{6,}$" data-validation="Please enter a number of at least 6 digits."> &nbsp;&nbsp;
		Filter by Date:<input type="checkbox" id="filterDate">
		<span id="spanDate" >
	  	from : <input type="date" id="dateFrom" value="<?php echo $dateNow; ?>" onchange="checkDate()" > &nbsp;&nbsp;   
		to: <input type="date" id="dateTo" value="<?php echo $dateNow; ?>" onchange="checkDate()"   >  
		<input type="text" id="status" value="uncheck">
		<input type="text" id="status2" value="">
 		</span>
		 <button id="b_search" class="i-search" type="button">Search</button>  
	<?php } ?>
</div>
<div id="results">
	<table></table>
</div>
<div id="loading_bg" data-loading-msg="Searching…"></div>

<div id="rec_player">
	<a class="close" title="Close Player">&times;</a>
	<a class="replay i-replay_10" title="Go back 10 seconds"></a>
	<a class="play"></a>
	<a class="forward i-forward_10" title="Forward 10 seconds"></a>
	<div class="rec-file i-file-music"></div>
	<div id="player_gain" data-gain="100%">
		<div class="ctrl"><span></span></div>
	</div>
	<div class="info">
		<div class="ready cur-time"></div>
		<div class="ready seeker"><span></span></div>
		<div class="ready duration"></div>
		<div class="loading">Loading…</div>
	</div>
</div>
<div id="seek_time_info"></div>
<div id="volume_info"></div>
</body>
</html>
 
 

 <script src="filter-date.js?<?=filemtime('filter-date.js')?>"></script>