jQuery(function() {

	function sound_links_selector() {
		return 'a[href*="youtu"]';
	}

	function find_all_sound_links() {
		var soundlinks = jQuery(sound_links_selector());
		return soundlinks;
	}

	function find_next_sound() {
		var soundlinks = find_all_sound_links();
		if (!soundlinks.length) {
			return soundlinks;
		}
		var playing = soundlinks.find('.playing');
		if(!playing.length) {
			return soundlinks.eq(0);
		}
		// TODO : trouver le lien en cours de lecture, puis prendre le suivant
		// si c'est le dernier dans l'ordre dapparition dans le HTML, on reprend le premier not played
		return soundlinks.eq(0);
	}

	function set_sound_playing(link) {
		jQuery('a.playing').removeClass('playing');
		link
			.addClass('playing')
			.closest('.item')
			.addClass('playing')
			.siblings('.playing')
			.removeClass('playing');
		// si le lien est encore en lecture au bout de 5s on le marque comme lu
		setTimeout(function(){if (link.is('.playing')) link.addClass('played');}, 5000);
	}

	function play_sound(link) {
		var src = link.attr('href').replace('&amp;', '&');
		player.setSrc(src);
		player.load();
		player.play();
		set_sound_playing(link);
	}

	// sur les pages qui ont un player uniquement
	if (jQuery('#player').length) {

		// initialiser le player
		var player;
		// on y met le premier son de la page
		var soundlinks = find_next_sound();
		if (soundlinks.length) {
			var source = jQuery('<source src="'+soundlinks.attr('href')+'" type="video/youtube">');
			jQuery('#player').append(source);
			set_sound_playing(soundlinks);
		}

		// on lance mediaplayer
		jQuery('#player').mediaelementplayer({
				pluginPath: 'squelette/mediaelement/build/',
				"alwaysShowControls": "true"
			})
			.each(function(){
				player = jQuery(this).data('mediaelementplayer')
			});

		// on rend tous les liens clicables
		jQuery('#content').on('click',sound_links_selector(),function(){
			play_sound(jQuery(this));
      return false;
    });

	}

});

