<?php
header('Content-Type: text/html; charset="utf-8"');
?><!DOCTYPE html>
<html>
<head>
	<title>Gussi.is bönn</title>
	<link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.9.0/build/reset/reset-min.css">
	<link href="/style.css" rel="stylesheet" type="text/css" />
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js" type="text/javascript"></script>
	<script src="/jquery.tmpl.min.js" type="text/javascript"></script>
	<script id="tmpl_bans" type="text/x-jquery-tmpl">
		<div id="banned">
			{{each bans}}
				<div class="ban">
					<div class="ban_info_name">${this.name}</div>
					<div class="ban_info_reason">${this.reason}</div>
					<div class="ban_info_time" data-time="${this.time_left}">000:00:00:00</div>
				</div>
			{{/each}}
		</div>
	</script>
	<script type="text/javascript">
		$(function() {
			$.ajax({
				url: "/bans.php",
				dataType: "json",
				success: function(data) {
					console.log(data);
					$("#tmpl_bans").tmpl(data).appendTo($("body"));
				}
			});

			window.setInterval(function() {
				$("div.ban_info_time").each(function() {
					var time = parseInt($(this).data('time'));
					var sec = time % 60;
					var min = Math.floor((time % 3600)/60);
					var hour = Math.floor((time % 86400)/3600);
					var day = Math.floor(time/86400);
					var time_left = zeropad(day, 3) + ":" + zeropad(hour) + ":" + zeropad(min) + ":" + zeropad(sec);
					$(this).html(time_left);
					$(this).data('time', time - 1);
				});
			}, 1000);
		});
		function zeropad(val, width) {
			if(width == undefined) {
				width = 2;
			}
			width -= val.toString().length;
			if ( width > 0 ) {
				return new Array( width + (/\./.test(val) ? 2 : 1) ).join( '0' ) + val;
			}
			return val;
		}
	</script>
</head>
<body>
<div id="title">
	Wall of shame
</div>
</body>
</html>
