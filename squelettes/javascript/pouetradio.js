var played_history = []
var affix_height = 0;
var max_nb_refresh_init = 72; // 6H * 12 (6H apres le derniere lecture, il suffit de relancer une lecture pour relancer le refresher)
var max_nb_refresh;
var player;
var refresher;
var timeoutBadSound;

/* Pagination infinie manuelle */
function pagination_more_loading(url,href,options) {
	jQuery(this)
		.find('.pagination.more')
		.append('<div class="bubblingG"><span id="bubblingG_1"></span><span id="bubblingG_2"></span><span id="bubblingG_3"></span></div>')
		.animateLoading();
}
/* Appellee aussi bien si pagination more que si refresh */
function pagination_more_loaded(c, href, history) {
	console.log('pagination_more_loaded');
	// refresh link ?
	if (c.indexOf('refresh-link')>0) {
		jQuery(this).find('.refresh-link').remove();
		jQuery(this).prepend(c);
		// rescroll on playing id
		if (jQuery('a.playing').length) {
			scroll_sound(jQuery('a.playing').eq(0));
		}
		jQuery(this).find('.pagination.more').endLoading(true).find('.bubblingG').remove();

	}
	// pagination more
	else if (c.indexOf('ajax_ancre')>0) {
		jQuery(this).find('.pagination.more').remove();
		jQuery(this).append(c);
	}
	else {
		jQuery(this).find('.pagination.more').endLoading(true).find('.bubblingG').remove();
	}
}

function refresh_pouets() {
	console.log('refresh_pouets');
	var refresh_link = jQuery('.pouetradio a.refresh-link');
	if (refresh_link.length) {
		var href = refresh_link.attr('href');
		refresh_link.ajaxReload({href:href,history:false});
	}
	if (max_nb_refresh--<=0) {
		clearInterval(refresher);
		refresher = null;
	}
}

// on refresh toutes les 6min
function start_refresher() {
	max_nb_refresh = max_nb_refresh_init;
	if (!refresher) {
		refresher = setInterval(refresh_pouets,6 * 60 * 1000);
	}
}
start_refresher();

function sound_links_selector() {
	return '.track a.playable';
}

function find_all_sound_links() {
	var soundlinks = jQuery(sound_links_selector());
	console.log(soundlinks);
	return soundlinks;
}

function find_next_sound() {
	var soundlinks = find_all_sound_links();
	if (!soundlinks.length) {
		return soundlinks;
	}
	var playing = soundlinks.filter('.playing');
	if(!playing.length) {
		// on commence au 5e puis on remonte sur les plus recents, en comptant sur le refresh pour en avoir de nouveaux
		// sinon on ira piocher dans les suivants en descendant
		return soundlinks.eq(5);
	}
	var ignore_played = false;
	var to_play = soundlinks.not('.played');
	if (!to_play.length) {
		ignore_played = true;
	}
	// charger la suite si possible, pour le prochain morceau
	if (to_play.length<=1) {
		setTimeout(function(){
			to_play.eq(0).closest('.item').siblings('.pagination.more').eq(0).find('.lien_pagination').eq(0).click();
		}, 1000);
	}
	// trouver le lien en cours de lecture, puis prendre le precedent
	// si c'est le dernier dans l'ordre d'apparition dans le HTML, on reprend le premier not played
	var prev = null;
	for (var i=0;i<soundlinks.length;i++) {
		if (soundlinks.eq(i).is('.playing')){
			break;
		}
		if (ignore_played || !soundlinks.eq(i).is('.played')) {
			prev = soundlinks.eq(i);
		}
	}
	if (prev) {
		return prev;
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
		.focus()
		.closest('.item')
		.addClass('playing')
		.siblings('.playing')
		.removeClass('playing');
	played_history.push(link);
}

function play_sound(link) {
	var src, url_api;
	if (link.is('.playable.peertube')
		&& !link.is('.peertube-fetched')
	  && (url_api = link.attr('data-api'))) {
		if (!player.paused) {
			player.pause();
		}
		set_sound_playing(link);
		watch_if_playable_sound();
		jQuery.ajax({
			url: url_api,
			dataType: 'json'
		}).done(function(data) {
			link.addClass('peertube-fetched');
			if (data.files) {
				var file_lowres = data.files.pop();
				if (file_lowres && file_lowres.fileUrl) {
					src = file_lowres.fileUrl;
					src = src.replace('&amp;', '&');
					console.log('Peertube : url '+src);
					link.attr('data-src', src);
					player.setSrc(src);
					player.load();
					player_play();
					return;
				}
			}
			// it's a fail on fallback sur le data-src hypothetique
			if (src = link.attr('data-src')) {
				src = src.replace('&amp;', '&');
				player.setSrc(src);
				player.load();
			}
			else {
				skip_to_next_sound();
			}
		});
	}
	else {
		if (!(src = link.attr('data-src'))) {
			src = link.attr('href');
		}
		src = src.replace('&amp;', '&');
		player.setSrc(src);
		player.load();
		set_sound_playing(link);
		player_play();
	}
	link.focus();
	start_refresher();
}

function play_next_sound() {
	var soundlink = find_next_sound();
	play_sound(soundlink);
	scroll_sound(soundlink);
}
function skip_to_next_sound() {
	jQuery('a.playing')
		.addClass('played')
		.addClass('skiped');
	play_next_sound();
}

function play_prev_sound() {
	if (played_history.length>1) {
		played_history.pop().removeClass('played'); // current playing
		var soundlink = played_history.pop();
		while (soundlink.is('.unplayable')) {
			var soundlink = played_history.pop();
		}
		play_sound(soundlink);
		scroll_sound(soundlink);
	}
}

function scroll_sound(soundlink) {
	var anchor = soundlink.closest('.item').find('>.anchor');
	if (anchor.length) {
		anchor.css('top','-'+affix_height+'px');
		var id=anchor.attr('id');
		jQuery.scrollTo('#'+id,400,{onAfter:function(){window.location.hash = id;setTimeout(function(){soundlink.focus()},100);}});

	}
}

function check_sound_playing() {
	var playing = jQuery('a.playing');
	// si on est playing c'est que le son est bon on peut annuler le watching
	console.log('check_sound_playing, paused='+player.paused);
	if (timeoutBadSound && !player.paused) {
		setTimeout(function(){
			console.log('check_sound_playing +1s, paused='+player.paused);
			if (timeoutBadSound && playing.is('.playing') && !player.paused) {
				console.log('check_sound_playing, cancel watch_if_playable_sound');
				clearTimeout(timeoutBadSound);
				timeoutBadSound = null;
			}
		},1000);
	}
	// si le lien est encore en lecture au bout de 5s on le marque comme lu
	setTimeout(function(){if (playing.is('.playing') && !player.paused) playing.addClass('played');}, 5000);
}

function player_paused() {
	console.log('player_paused');
}
function player_canplay() {
	console.log('player_canplay');
}

function watch_if_playable_sound() {
	console.log('watch_if_playable_sound');
	if (timeoutBadSound) clearTimeout(timeoutBadSound);
	timeoutBadSound = setTimeout(skip_unplayable_sound, 7500);
}
function skip_unplayable_sound() {
	console.log('skip_unplayable_sound');
	jQuery('a.playing').addClass('unplayable');
	skip_to_next_sound();
}

function player_pause(){
	console.log('do player_pause');
	// il faut annuler le watching car c'est l'utilisateur qui a arrete le player ?
	if (timeoutBadSound) {
		clearTimeout(timeoutBadSound);
		timeoutBadSound = null;
	}
	player.pause();
}

function player_play(){
	console.log('do player_play');
	player.play();
	watch_if_playable_sound();
}

function on_sound_key_down(e) {
	console.log('on_sound_key_down');
	console.log(e.keyCode);
	var keyActions = [
			{
					keys: [
							32, // SPACE
							179 // GOOGLE play/pause button
						  ],
					action: function(player) {
						if (player.paused || player.ended) {
							player_play();
						} else {
							player_pause();
						}
					}
			},
			{
					keys: [
							34, // Page Down
						  78 // 'N'
						  ],
					action: function(player) {
						skip_to_next_sound();
					}
			},
			{
					keys: [
							33, // Page Up
						  80 // 'P'
						  ],
					action: function(player) {
						play_prev_sound();
					}
			}
	];

	// find a matching key
	for (var i = 0, il = keyActions.length; i < il; i++) {
		var keyAction = keyActions[i];

		for (var j = 0, jl = keyAction.keys.length; j < jl; j++) {
			if (e.keyCode == keyAction.keys[j]) {
				if (typeof(e.preventDefault) == "function") e.preventDefault();
				keyAction.action(player, e.keyCode);
				return false;
			}
		}
	}

	return true;
}

jQuery(function() {
	// affix player
	affix_height = jQuery('.affix-placeholder').innerHeight();
	jQuery('.affix-placeholder')
		.children()
		.on('affix.bs.affix',function(){
			var h = jQuery(this).parent().innerHeight();
			jQuery(this).parent().css('min-height',h+'px');
			affix_height = h;
		});


	jQuery('.pagination.more')
		.closest('div.ajaxbloc')
		.attr('data-loading-callback','pagination_more_loading')
		.attr('data-loaded-callback','pagination_more_loaded');


	// sur les pages qui ont un player uniquement
	if (jQuery('#player').length) {

		// initialiser le player
		// on y met le premier son de la page
		var soundlink = find_next_sound();
		if (soundlink.length) {
			var source = jQuery('<source src="'+soundlink.attr('href')+'">');
			jQuery('#player').append(source);
			set_sound_playing(soundlink);
			scroll_sound(soundlink);
			setTimeout(function(){scroll_sound(soundlink);},4000);
		}

		jQuery('#player').parent()
			.siblings('.next').on('click',skip_to_next_sound)
			.siblings('.prev').on('click',play_prev_sound);

		// on lance mediaplayer
		jQuery('#player').mediaelementplayer({
				pluginPath: 'squelette/mediaelement/build/',
				alwaysShowControls: true,
				stretching: 'responsive',
				success:function(p,node) {
					player = p;
					player.addEventListener('ended',play_next_sound);
					player.addEventListener('canplay',player_canplay);
					player.addEventListener('playing',check_sound_playing);
					player.addEventListener('pause',player_paused);
				}
			});

		// on rend tous les liens clicables
		jQuery('#content')
			.on('click',sound_links_selector(),function(){
				play_sound(jQuery(this));
	      return false;
	    })
			// et sensible a la frappe clavier
			.on('keydown',sound_links_selector(),on_sound_key_down);
	}

});

