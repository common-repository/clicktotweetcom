<?php

// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
	die;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title>ClickToTweet.com</title>

	<style type="text/css">

		.ctt_dialog {
			padding: 15px;
		}

		.list__ctt {
			margin: 0;
			padding: 0;
		}

		.list__ctt li {
			background-color: #efefef;
			border-bottom: 1px solid #ccc;
			margin: 0 0 1px 0;
			padding: 10px;
			font-family: Helvetica, Arial, sans-serif;
			list-style-type: none;
		}

		.list__ctt li .actions {
			float: right;
			padding: 5px;
		}

		.list {
			height: 400px;
			overflow-x: hidden;
			overflow-y: auto;
			padding: 0;
			margin: 0 !important;
		}

		.clear {
			clear: right;
		}

		h3 {
			padding: 5px 10px;
			margin-bottom: 0;
			font-weight: bold;
			font-size: 18px !important;
		}

		.inside .left {
			width: 50%;
			float: left;
		}

		.inside .right {
			width: 50%;
			float: right;
		}

		.inside .buttons {
			clear: left;
			text-align: center;
		}

		.inside input[type='text'] {
			width: 100%;
			padding: 10px;
			font-size: 12px;
		}
		
		.thetweet {

		}
		
		label {
			font-weight: bold;
		}

		.button {
			background: #2ea2cc;
			border-color: #0074a2;
			font-size: 14px;
			-webkit-box-shadow: inset 0 1px 0 rgba(120,200,230,.5),0 1px 0 rgba(0,0,0,.15);
			box-shadow: inset 0 1px 0 rgba(120,200,230,.5),0 1px 0 rgba(0,0,0,.15);
			color: #fff !important;
			font-weight: bold;
			text-decoration: none;		
			padding: 10px;
			border-radius: 8px;	
		}		
		
		.ctt_new, .ctt_insert {
			background-color: #efefef;
			padding: 15px;
			border-radius: 15px;			
		}
		
		.ctt_insert {
			margin-top: 15px;
		}

	</style>

	<script language="javascript" type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option( 'siteurl' ) ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option( 'siteurl' ) ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option( 'siteurl' ) ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript">
		function ctt_submit(e) {
			var coverup = e.id.replace('insert_', '');
			var tweetEl = document.getElementById('tweet_' + coverup);
			var tweet = tweetEl.innerHTML;
			var tag = '[ctt tweet="' + tweet + '" coverup="' + coverup + '"]';
			if (window.tinyMCE) {
				window.tinyMCE.execCommand('mceInsertContent', false, tag);
				tinyMCEPopup.editor.execCommand('mceRepaint');
				tinyMCEPopup.close();
			}
		}
		jQuery(function ($) {
			$('#ctt-insert-button').on('click', function (e) {
				e.preventDefault();
				var data = {
					action: 'ctt_api_post',
					security: '<?php echo $ajax_nonce; ?>',
					data: $("#ctt_new").serialize()
				};
				$.post('/wp-admin/admin-ajax.php', data, function (response) {
					ctt = jQuery.parseJSON(response);
					res = '[ctt title="'+ ctt.title.replace(/"/g, '&#39;') +'" tweet="' + ctt.tweet.replace(/"/g, '&#39;') + '" coverup="' + ctt.coverup + '"]';
					if (window.tinyMCE) {
						window.tinyMCE.execCommand('mceInsertContent', false, res);
						tinyMCEPopup.editor.execCommand('mceRepaint');
						tinyMCEPopup.close();
					}
				});
			});
		});
	</script>
</head>
<body>

<div class="ctt_dialog">
	<div class="ctt_new postbox" style="display: block">
		<h3>Create a new CTT</h3>
		<div class="inside">
			<form name="ctt_new" id="ctt_new" method="post">
				<input type="hidden" name="token" value="<?php echo $token; ?>">

				<p>
					<label for="title">Title</label> <em>(displayed as the quote in the tweet)</em><br />
					<input name="title" type="text">
				</p>

				<p>
					<label for="tweet">Tweet</label> <em>(the message that will be tweeted)</em><br />
					<input name="tweet" type="text" class="thetweet">
				</p>

				<div class="buttons">
					<input id="ctt-insert-button" type="submit" value="Insert New CTT" name="submit" class="button button-primary button-large">
				</div>
			</form>
		</div>
	</div>

	<div class="ctt_insert postbox">
		<h3>Insert an existing CTT</h3>

		<div class="inside list">
			<?php echo $content; ?>
		</div>
	</div>

</div>

</body>
</html>
