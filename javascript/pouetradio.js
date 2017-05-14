jQuery(function() {

	var player;
	var videolinks = jQuery('a[href*="youtu"]');
	if (videolinks.length) {
		var first = videolinks.eq(0);
		var source = jQuery('<source src="'+first.attr('href')+'" type="video/youtube">');
		jQuery('#player').append(source);
		first.addClass('playing').closest('.item').addClass('playing');
	}

	jQuery('#player').mediaelementplayer({
		pluginPath: 'squelette/mediaelement/build/',
		"alwaysShowControls": "true"
	}).each(function(){player = jQuery(this).data('mediaelementplayer')});
	console.log(player);


        jQuery('#content').on('click','a[href*="youtu"]',function(){
		var src = jQuery(this).attr('href').replace('&amp;', '&');
		player.setSrc(src);
		player.load();
		player.play();

		jQuery('a.playing').removeClass('playing');
		jQuery(this).addClass('playing').closest('.item').addClass('playing').siblings('.item.playing').removeClass('playing');

        	return false;
        })

});

