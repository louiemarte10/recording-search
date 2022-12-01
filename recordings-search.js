
(($, doc, win) => {

	const toHMS = s => ({
		h: s / 3600 >> 0,
		H: (s / 3600 >> 0).toString().padStart(2,0),
		m: s % 3600 / 60 >> 0,
		M: (s % 3600 / 60 >> 0).toString().padStart(2,0),
		s: s % 60,
		S: (s % 60 >> 0).toString().padStart(2,0)
	});

	const bound = (value, min, max) => Math.max(min, Math.min(max, value));

	const audio = new Audio();
	let audioContext, track, gainNode = null, gainAdjust = false;

	$.each({

		'#b_search'(){
			const search_input = $('#search_number');
			   const dateFrom = $('#dateFrom').val();
				 const dateTo = $('#dateTo').val();	
				const status = $('#status').val();	
				const status2 = $('#status2').val(); 
			search_input.val(search_input.val().replace(/[^0-9]+/g, ''));
			const search = search_input.val();


			if (search != '' && !search_input[0].validity.valid){
				alert(search_input[0].dataset.validation);
				search_input.focus();
				return;
			}

			const aCtrl = new AbortController(), signal = aCtrl.signal;
			let loading_msg_timer;
			setTimeout(() => {
				aCtrl.abort();
			}, 1e4);

			loading_msg_timer = setTimeout(() => {
				$('#loading_bg').show();
			}, 600);

			const endLoading = () => {
				clearTimeout(loading_msg_timer);
				$('#loading_bg').hide();
			};

			const isSearch = search.match(/^[0-9]{4,}$/);
			// const q = isSearch ? `search=${search}` : 'recent=1';
				const q = isSearch ? `search=${search}&datefrom=${dateFrom}&dateto=${dateTo}&status=${status}` : 'recent=1';
		 

		
 
			fetch(`api.php?${q}`, { signal })
				.then(async res => {
					search_input.focus();
					if (res.ok === false){
						endLoading();
						return alert(`${res.status} ${res.statusText}`);
					}
					const data = await res.json();
					let results = '', count = 1;

					results += `<caption>${isSearch ? 'Answered Call Recordings for ' + search + ' ' + status2  : 'Recent Answered Calls'}</caption>`;
					results += '<tr><th>#</th><th>Phone</th><th>Called on  </th><th>Agent</th><th>Duration</th></tr>';

					if (data.length < 1){
						results += '<tr><td colspan="5" class="empty">No records found</td><tr>';
					}

					for (let [ cdr_id, phone, name, dt, duration, rec ] of data){
						dt = new Date(dt * 1000);
						let d = toHMS(duration);
						d = `${d.h > 0 ? d.h + ':' : ''}${d.M}:${d.S}`;

						let rec_file = `<span>${phone}</span><a class="listen i-headphones" title="Listen" data-rec="${rec}" data-id="${cdr_id}"></a>`;
						rec_file += `<a class="download i-download" title="Download" target="_new" href="api.php?download=${cdr_id}"></a>`;

						results += `<tr><td>${count++}</td><td class="r">${rec_file}</td><td>${Intl.DateTimeFormat('en-US', { dateStyle: 'medium', timeStyle: 'medium' }).format(dt)}</td>`;
						results += `<td class="a">${name}</td><td>${d}</td></tr>`;
					}
					$('#results table').html(results);
					endLoading();
				})
				.catch(e => {
					endLoading();
					search_input.focus();
					alert(signal.aborted ? 'Server currently busy. Please try again later.' : `Server replied with error: ${e}`);
				});
		},

		'#search_number': {
			keypress(e){
				if (e.key == 'Enter'){
					$('#b_search').click();
				}
			}
		},

		'a.listen'(){
			if (this.dataset.id){
				$(doc.body).addClass('player');
				$('#rec_player').removeClass('ready playing');
				$('#rec_player .rec-file').text(this.dataset.rec);
				audio.src = `api.php?listen=${this.dataset.id}`;
			}
		},

		'#rec_player .close'(){
			if (!audio.paused){
				audio.pause();
			}
			$(doc.body).removeClass('player');
		},

		'#rec_player .play'(){
			audio.paused ? audio.play() : audio.pause();
		},

		'#rec_player.playing .replay'(){
			audio.currentTime = Math.max(0, audio.currentTime - 10);
		},

		'#rec_player.playing .forward'(){
			audio.currentTime = Math.min(audio.duration, audio.currentTime + 10);
		},

		'#rec_player.ready .seeker': {
			'mousedown mouseenter mouseleave mousemove'({ button, offsetX, type, clientX, clientY }){
				if (type == 'mousedown' && button == 0){
					audio.currentTime = offsetX * audio.duration / this.offsetWidth;
				}

				if (type == 'mouseleave'){
					$('#seek_time_info').hide();
				} else if ('mouseenter mousemove'.includes(type)){
					let t = toHMS(offsetX * audio.duration / this.offsetWidth);
					$('#seek_time_info').text(`${t.h ? t.H + ':' : ''}${t.M}:${t.S}`).css({ top: `${clientY - 20}px`, left: `${clientX}px`}).show();
				}
			}
		},

		'#player_gain .ctrl': {
			'mousedown mouseenter mouseleave mousemove'({ button, offsetX, type, clientX, clientY }){
				if (button != 0){
					return;
				}

				if (type == 'mousedown' || (type == 'mousemove' && gainAdjust)){
					gainAdjust = true;
					let gain = Math.min(Math.round(++offsetX * 300 / this.offsetWidth), 300);
					gainNode.gain.value = gain / 100;
					$('#player_gain span').css('width', `${offsetX}px`);
					$('#player_gain span').css('background-color', `hsl(${bound(160 - (gain - 100) * 0.65, 0, 160)}, 100%, 40%)`);
					$('#player_gain').attr('data-gain', `${gain}%`);
				}

				if (type == 'mouseleave'){
					$('#volume_info').hide();
				} else if ('mouseenter mousemove'.includes(type)){
					let v = Math.min(Math.round(++offsetX * 300 / this.offsetWidth), 300);
					$('#volume_info').text(`${v}%`).css({ top: `${clientY - 20}px`, left: `${clientX}px`}).show();
				}
			}
		}

	}, (sel, fn) => {
		if (typeof fn == 'function'){
			$(doc).on('click', sel, fn);
		} else {
			$.each(fn, (e, f) => {
				$(doc).on(e, sel, f);
			});
		}
	});

//- Audio Events
	$.each({
		loadedmetadata(){
			let d = toHMS(audio.duration);

			$('#rec_player .duration').text(`${d.h ? d.H + ':' : ''}${d.M}:${d.S}`);

			if (!gainNode){
				audioContext = new AudioContext();
				track = audioContext.createMediaElementSource(audio);
				gainNode = audioContext.createGain();
				track.connect(gainNode).connect(audioContext.destination);
			}

			$('#player_gain span').css('width', `${gainNode.gain.value * 100 / 3}%`);
		},

		canplay(){
			$('#rec_player').addClass('ready');
			audio.play();
		},

		playing(){
			$('#rec_player').addClass('playing');
		},

		pause(){
			$('#rec_player').removeClass('playing');
		},

		durationchange(){
			if (audio.duration !== Infinity){
				let d = toHMS(audio.duration);
				$('#rec_player .duration').text(`${d.h ? d.H + ':' : ''}${d.M}:${d.S}`);
			}
		},

		timeupdate(){
			let d = toHMS(audio.currentTime), pct = (audio.currentTime * 100 / audio.duration).toFixed(2);
			$('#rec_player .cur-time').text(`${d.h ? d.H + ':' : ''}${d.M}:${(d.s < 10 ? '0' : '') + d.s.toFixed(2)}`);
			$('#rec_player .seeker span').css('width', pct + '%');
		},

		ended(){
			$('#rec_player').removeClass('playing');
		},

		error(){
			$(doc.body).removeClass('player');
			alert(`Error encountered while trying to load recording: ${audio.error.message}`);
		}

	}, (e, fn) => {
		audio.addEventListener(e, fn);
	});

	win.addEventListener('mouseup', e => {
		if (e.button == 0){
			gainAdjust = false;
		}
	});

//- On load
	$(() => {
		history.replaceState(null, '', `./${location.search}`);

		$('#b_search').click();
	});

})(jQuery, document, window);
