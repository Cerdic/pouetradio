jQuery(function() {
	var affix_height = jQuery('.affix-placeholder').innerHeight();
	jQuery('.affix-placeholder')
		.children()
		.on('affix.bs.affix',function(){
			var h = jQuery(this).parent().innerHeight();
			jQuery(this).parent().css('min-height',h+'px');
			affix_height = h;
		});

	var played_history = []

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
		var playing = soundlinks.filter('.playing');
		if(!playing.length) {
			return soundlinks.eq(0);
		}
		// TODO : trouver le lien en cours de lecture, puis prendre le suivant
		// si c'est le dernier dans l'ordre d'apparition dans le HTML, on reprend le premier not played
		for (var i=0;i<playing.length;i++) {
			if (soundlinks.eq(i).is('.playing')){
				break;
			}
		}
		var ignore_played = false;
		if (!soundlinks.not('.played').length) {
			ignore_played = true;
		}
		i++;
		while(i<soundlinks.length) {
			if (ignore_played || !soundlinks.eq(i).is('.played')){
				return soundlinks.eq(i);
			}
			i++;
		}
		// on repart de 0
		i=0;
		while(i<soundlinks.length) {
			if (ignore_played || !soundlinks.eq(i).is('.played')){
				return soundlinks.eq(i);
			}
			i++;
		}
		// on repart sur le premier ?
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
		played_history.push(link);
		// si le lien est encore en lecture au bout de 5s on le marque comme lu
		setTimeout(function(){if (link.is('.playing')) link.addClass('played');}, 5000);
	}

	function play_sound(link) {
		var src = link.attr('href').replace('&amp;', '&');
		src.replace('//m.youtu','//www.youtu');
		player.setSrc(src);
		player.load();
		player.play();
		set_sound_playing(link);
	}

	function play_next_sound() {
		var soundlink = find_next_sound();
		play_sound(soundlink);
		scroll_sound(soundlink);
	}

	function play_prev_sound() {
		if (played_history.length>1) {
			played_history.pop().removeClass('played'); // current playing
			var soundlink = played_history.pop()
			play_sound(soundlink);
			scroll_sound(soundlink);
		}
	}

	function scroll_sound(soundlink) {
		var anchor = soundlink.closest('.item').find('>.anchor');
		if (anchor.length) {
			anchor.css('top','-'+affix_height+'px');
			var id=anchor.attr('id');
			jQuery.scrollTo('#'+id,400,{onAfter:function(){window.location.hash = id;}});

		}
	}

	// sur les pages qui ont un player uniquement
	if (jQuery('#player').length) {

		// initialiser le player
		var player;
		// on y met le premier son de la page
		var soundlink = find_next_sound();
		if (soundlink.length) {
			var source = jQuery('<source src="'+soundlink.attr('href')+'" type="video/youtube">');
			jQuery('#player').append(source);
			set_sound_playing(soundlink);
		}

		jQuery('#player')
			.siblings('.next').on('click',play_next_sound)
			.siblings('.prev').on('click',play_prev_sound);

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

