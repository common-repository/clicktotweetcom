(function () {
	tinymce.create('tinymce.plugins.ctt', {
		init: function (ed, url) {
			ed.addButton('ctt', {
				title: 'ClickToTweet.com',
				image: url.replace("/js", "") + '/twitter-button.png',
				onclick: function () {
					jQuery(document).ready(function ($) {
						// Open URL based window
						tinymce.activeEditor.windowManager.open({
							title: "ClickToTweet.com",
							width: 600,
							height: 450,
							url: ajaxurl + '?action=ctt_show_dialog',
						});
					});
				}
			});
		},
		createControl: function (n, cm) {
			return null;
		},
		getInfo: function () {
			return {
				longname: "Click To Tweet WordPress Plugin",
				author: 'ClickToTweet.com',
				authorurl: 'http://ctt.ec/',
				infourl: 'http://ctt.ec/',
				version: "1.0.5"
			};
		}
	});
	tinymce.PluginManager.add('ctt', tinymce.plugins.ctt);
})();
