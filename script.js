// global variables
const timenow = new Date();
var forceRefresh = false;

$(function() {
	// keycodes
	$(document).keyup(function(e) {
		switch(e.key) {
			case "Escape":  // keycode `27`
				popup_close();
				break;
			case "ArrowRight":
			case "ArrowLeft":
				$('.image_nav .'+e.key).click();
				break;
		}
	});

	// refresh log table
	var refreshTableTime=get_setting('log_refresh');
	if($("table.logs").length)
		startRefreshTable(refreshTableTime);

	// log table page size
	var pageSize=get_setting('pagination_per_page');
	if(!pageSize)	pageSize=10;

	// log datetime
	$(".date").datetimepicker({
		step:5,
		format:'Y-m-d',
		timeFormat:"hh:mm",
		theme:'dark',
	});

	// log table
	if($("table.logs").length)
		$('.logs').pagination({
			dataSource: '?logs',
			locator: 'items',
			totalNumberLocator: function(response) {
				url=pagination_add_search('?logs_count');

				var count=0;
				$.ajax({
					type: "POST",
					url: url,
					async: false,
				}).done(function(ret, log) {
					count=ret;
				});
				return count;
			},
			showSizeChanger: true,
			pageSize: pageSize,
			showNavigator: true,
			formatNavigator: 'Showing <%= rangeStart %>-<%= rangeEnd %> of <%= totalNumber %>',
			ajax: {
				beforeSend: function() {
					$('.title_cog').addClass("spin");
					this.url=pagination_add_search(this.url);
				}
			},
			callback: function(data, pagination) {
				dataContainer=$('.logs tbody');
				var html = data;
				dataContainer.html(data);

				// log table per page
				$('.J-paginationjs-size-select').on( "change", function(event) {
					save_setting('pagination_per_page', $(this).val());
				});

				setTimeout(
					function() 
					{
						$('.title_cog').removeClass("spin");
						//do something special
					}, 2000);
			}
		});

	// log search
	$('.logs .search input, .logs .search select').on( "change", function(event){
		id=$(this).attr("data-name");
		val=$(this).val();

		// reload this page
		pagination_refresh(1);
	});

	// image uploadeder
	$('#fileInput').on( "change", function(event) {
		// check ID
		id=$('#fileInputID').val();
		if(Math.floor(id) != id || !$.isNumeric(id)) {
			console.log('ERROR: No ID');
			return false;
		}

		// grab file
		const file = event.target.files[0];
		if (!file) {
			console.log('ERROR: No file');
			return false;
		}

		// make sure is image
		var validImageTypes = ["image/gif", "image/jpeg", "image/png"];
		if ($.inArray(file["type"], validImageTypes) < 0) {
			console.log('ERROR: Not image');
			return false;
		}

		// load data URL into image
		const reader = new FileReader();
		reader.onload = function(e) {
			const imageDataURL = e.target.result;
			const img = new Image();

			img.onload = function() {
				const canvas = $('#fileInputCanvas')[0];
				const ctx = canvas.getContext('2d');

				// dimensions
				const max_width = Math.floor($('#fileInputMaxWidth').val());
				const max_height = Math.floor($('#fileInputMaxHeight').val());

				var scale=Math.min((max_width/img.width),(max_height/img.height));
				canvas.width = img.width*scale;
				canvas.height = img.height*scale;

				// draw and save image
				ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
				let imageData = canvas.toDataURL('image/jpeg');
				$.ajax({
					type: "POST",
					url: "?upload_image",
					data: {
						image: imageData,
						id: id
					}
				}).done(function(ret, log) {
					// reset inputs
					$('#fileInputID').val('');
					$('#fileInput').val('');
					ctx.clearRect(0, 0, canvas.width, canvas.height);

					// update row
					row=$('tr[data-id="'+id+'"]');
					edit_log($(row), false);
				});
			};

			img.src = imageDataURL;
		};

		// start
		reader.readAsDataURL(file);
	});

	// cog refresh
	$( ".title_cog" ).on( "click", function() {
		pagination_refresh();
		return false;		// stop href
	});

	// settings
	$( "#settings" ).on( "click", function() {
		open_settings();
	});

	// user
	$( "#user" ).on( "click", function() {
		$.ajax({
			url: "?user",
			type: 'GET',
		}).done(function(ret, log) {
			popup(ret);
		});
	});

	// logout
	/*$( "#user" ).on( "click", function() {
		$.ajax({
			url: "?logout",
			type: 'POST',
		}).done(function(ret, log) {
			window.location.href = "/statlog";
		});

	});*/

	// logs refresh
	/*$( ".refresh" ).on( "click", function() {
		icon=$(this).find('.icon').first();
		icon.addClass('spin');
		$('#logs_content').load("?logs");

		setTimeout(function() {
			icon.removeClass("spin");
		}, 2000);
	});*/

	// logs add
	$( ".logs .add" ).on( "click", function() {
		add_log();
	});
});

function edit_account(el) {
	// grab cell
	td=$(el).closest('td');
	
	// enable editing
	$(td).addClass('editing');
	$(td).find('input, select').attr("disabled", false);

	// reset password
	$(td).find('.hidden_fields').removeClass('hidden');
	$(td).find('.fake_password_change').removeClass('hidden');;
	$(td).find('.fake_password_saved').addClass('hidden');;
}

function done_account(el) {
	// grab cell
	td=$(el).closest('td');

	// disable editing
	$(td).removeClass('editing');
	$(td).find('input, select').attr("disabled", true);

	// reset password
	$(td).find('.fake_password_saved').addClass('hidden');;
}

function download_logs(el) {
	// open link
	url=pagination_add_search('?logs_download&file_type='+$(el).val());
	window.open(url);

	// reselect first
	el.selectedIndex = 0;
}

function datetime(input) { 
	//datepicker
	console.log('datetime');
	$(input).datetimepicker({
		step:5,
		dateFormat: "yy-mm-dd",
		timeFormat:  "hh:mm",
		theme:'dark',
		theme:'dark',
	});
}

function display_autoclaves_cycles(el) {
	tr = $(el).closest('tr');
	$.ajax({
		url: "?autoclaves_cycles",
		type: 'GET',
		data: {
			model: $(el).val(),
		}
	}).done(function(ret, log) {
		// grab the defaults
		var data = jQuery.parseJSON(ret);
		if($(data).length) {
			// empty cycles
			$(tr).find('.cycles').empty();

			// add in cycles
			$(data).each(function() {
				add_autoclaves_cycles(el);
				cycle = $(tr).find('.cycles .cycle').last();
				cycle.find('input[data-name="cycle_name"]').val(this["name"]);
				cycle.find('input[data-name="cycle_temp"]').val(this["temp"]);
				cycle.find('input[data-name="cycle_time"]').val(this["time"]);
			});

			// save it up!
			save_autoclaves($(tr).find('input').last());
		}
	});
}


function add_log() {
	// hide welcome
	$('.no_logs').fadeOut();

	// grab content
	$.ajax({
		url: "?add_log",
		type: 'GET',
		context: document.body,
		data: {
			time: time_now(),
		}
	}).done(function(ret, log) {
		// add in first row
		$('#logs_content').prepend(ret);
	});

}

function open_settings() {
	$('.nav .icon_settings').addClass('active');

	$.ajax({
		url: "?settings",
		type: 'GET',
	}).done(function(ret, log) {
		popup(ret, 'Settings');
		$('.nav .icon_settings').removeClass('active');
	});

}

function random_name(el) {
	input=$(el).closest('tr').find('input[data-name="nickname"]');
	$.ajax({
		type: "POST",
		url: "?random_name",
	}).done(function(ret, log) {
		input.val(ret);
		input.change();
	});
}

/* refresh log table */

var setRefreshTableFlag;
startRefreshTable = (time) => {
	console.log("Refresh every: "+time);
	if(setRefreshTableFlag){
		clearInterval(setRefreshTableFlag);
	}
	if(time==0)	return false;
	setRefreshTableFlag = setInterval(function(){
		// check if popup is open
		if($('.popup').css('display') == "block")
			return false;

		// check if editing a log
		if($('.logs tr.editing').length)
			return false;

		pagination_refresh();
	}, time);
}


/* pageination */
function pagination_refresh(currentPage=null) {
	// check logged in
	if(!is_logged_in())
		window.location.href = '/';

	// if specific settings have been changed
	if(forceRefresh)
		location.reload();

	// get current page
	if(!currentPage)
		currentPage=$('.paginationjs-pages li.active').data('num');
	if(!currentPage)
		currentPage=1;

	// check for editing
	if($('.logs .editing').length > 0)
		return false;

	// now refresh
	console.log('Refreshed');
	$('.logs').pagination(currentPage);
}

function pagination_add_search(url) {
	// append search criteria
	$('.logs .search input, .logs .search select').each(function() {
		val=$(this).val();
		id=$(this).attr('data-name');

		// append to url
		if(val)
			url+='&search['+id+']='+val;
	});

	return url;
}

/* load setting */
function get_setting(key) {
	value=null;
	$.ajax({
		type: "POST",
		url: "?get_setting",
		async: false,
		data: {
			key: key,
		}
	}).done(function(ret, log) {
		value=ret;
	});
	return value;
}

/* save setting */
function save_setting(key, value) {
	$.ajax({
		type: "POST",
		url: "?save_setting",
		data: {
			key: key,
			value: value,
		}
	});
}

/* user */
function is_logged_in() {
	logged_in=false;
	$.ajax({
		url: "?is_logged_in",
		type: 'POST',
		async: false,
		cache: false,
	}).done(function(ret, log) {
		if(ret=="1")
			logged_in=true;
	});
	return logged_in;
}

function change_user(input, id) {
	val=$(input).val();

	// save value
	$.ajax({
		url: "?change_"+id,
		type: 'POST',
		async: false,
		cache: false,
		data: {
			'value': val,
		},
	}).done(function(ret) {
		console.log(ret);
		// blink
		if(ret=="saved")
			$(input).addClass('saved');
		else if(ret=="error")
			$(input).addClass('notsaved');
	}).error(function(ret) {
		$(input).addClass('notsaved');
	});

	// reset blink
	setTimeout(function() {
		$(input).removeClass("saved");
		$(input).removeClass("notsaved");
	}, 2000);

}

function change_username(input) {
	change_user(input, 'username');
}

function change_language(input) {
	change_user(input, 'language');
}

function change_password(el) {
	error = false;

	// grab values
	fields = $(el).closest('.hidden_fields');
	password_current=$(fields).find('input[data-name="password_current"]');
	password_change=$(fields).find('input[data-name="password_change"]');
	password_again=$(fields).find('input[data-name="password_again"]');

	// check are filled out
	if(!password_current.val().length)
		error = password_current.addClass('notsaved');
	if(!password_change.val().length)
		error = password_change.addClass('notsaved');
	if(!password_again.val().length)
		error = password_again.addClass('notsaved');

	// and pw gotta match
	if(!error && password_change.val() != password_again.val()) {
		password_change.addClass('notsaved');
		password_again.addClass('notsaved');
		error = true;
	}

	// save
	if(!error) {
		$.ajax({
			url: "?change_password",
			type: 'POST',
			async: false,
			cache: false,
			data: {
				'current': password_current.val(),
				'change': password_change.val(),
			},
		}).done(function(ret) {
			console.log(ret);
			console.log('changing pw');
			// blink
			if(ret=="saved") {
				// show saved
				$(fields).addClass('hidden');
				$('.fake_password_change').addClass('hidden');
				$('.fake_password_saved').removeClass('hidden');

				// blank fields
				password_current.val('');
				password_change.val('');
				password_again.val('');
			}
			else
				password_current.addClass('notsaved');

		}).error(function(ret) {
			password_current.addClass('notsaved');
		});
	}

	// reset blink
	setTimeout(function() {
		$(password_current).removeClass('notsaved');
		$(password_change).removeClass('notsaved');
		$(password_again).removeClass('notsaved');
	}, 2000);
}


/* logs */
function display_log_cycle_info(el) {
	tr = $(el).closest('tr');
	temp = $(el).find(':selected').data('temp');
	time = $(el).find(':selected').data('time');

	temp_input = $(tr).find('input[data-name="cycle_temp"]');
	time_input = $(tr).find('input[data-name="cycle_duration"]');

	if(!$(temp_input).val()) {
		temp_input.val(temp);
		temp_input.change();
	}

	if(!$(time_input).val()) {
		time_input.val(time);
		time_input.change();
	}
}

function display_log_cycle(el) {
	tr = $(el).closest('tr');
	cycle_type = $(tr).find('.cycle_type select');
	prev_value=$(cycle_type).val();

	if($(el).val()=="") {
		// disable inputs
		$(tr).find(".cycle :input").attr("disabled", true);
	} else {
		// empty cycle dropdown
		$(cycle_type).find('.cycles').empty();

		// empty custom dropdown
		$(cycle_type).find('.custom').empty();
		var option = $('<option value="enter_other">Enter other</option>');
		$(cycle_type).find('.custom').append(option);

		// enable inputs
		$(tr).find(".cycle :input").attr("disabled", false);

		// allow automatic and load cycle types
		$.get("?cycle_types="+$(el).val(), function(ret) {
			var data = jQuery.parseJSON(ret);
			$(data).each(function() {
				var option = $('<option></option>')
					.attr("value", this["cycle_name"])
					.attr("data-name", this["cycle_name"])
					.attr("data-temp", this["cycle_temp"])
					.attr("data-time", this["cycle_time"])
					.attr("data-pressure", this["cycle_pressure"])
					.text(this["cycle_name"]);
				$(cycle_type).find('.cycles').append(option);
			});
		});

		// is it missing from list?
		if(prev_value && !$(cycle_type).find('option[value="'+prev_value+'"]').length) {
			var option = $('<option></option>')
				.attr("value", prev_value)
				.attr("data-name", prev_value)
				.text(prev_value);
			$(cycle_type).find('.custom').append(option);
			$(cycle_type).val(prev_value);
		}

	}
}

function show_image_by_id(img_id) {
	$('.logs_image[data-id="'+img_id+'"] img').click();	
}

function show_image(img,text='',el) {
	// this image
	id = $(el).closest('.logs_image').data("id");

	// all images
	images = $(el).closest('.photos');
	image_count = $(images).find('.logs_image').length;

	// each image
	prev_image=null;
	next_image=null;
	found=false
	$(images).find('.logs_image').each(function() {
		// this image
		if($(this).data("id")==id)
			found=true;
		else {
			if(!found && prev_image==null)
				prev_image=$(this).data("id");

			if(found && next_image==null)
				next_image=$(this).data("id");
		}
	});

	// image carasol
	if(image_count > 1) {
		// prev+next
		text+='<span class="image_nav">';
		if(prev_image)
			text+='<a class="ArrowLeft" onclick="show_image_by_id(\''+prev_image+'\')">&lt;</a>';
		else
			text+='<span>&lt;</span>'

		if(next_image)
			text+='<a class="ArrowRight" onclick="show_image_by_id(\''+next_image+'\')">&gt;</a>';
		else
			text+='<span>&gt;</span>'
		text+='</span>';
	}

	// show
	popup('<img class="log_image" src="'+img+'" />', text);
}

function remove_logs_image(el) {
	// row
	id = $(el).closest('tr').data("id");

	// image
	image = $(el).closest('div.logs_image').data("id");

	// remove
	$.ajax({
		url: "?remove_image",
		type: 'POST',
		data: {
			'id': id,
			'image': image,
		},
	}).done(function(ret, log) {
		row=$('tr[data-id="'+id+'"]');
		edit_log(row);
	});
}

function automatic_log_cycle(el) {
	// row
	row = $(el).closest('tr');

	// autoclave
	val = $(row).find('[data-name="autoclave"]').val();
	if(val=="") return false;

	// get last cycle
	$.ajax({
		url: "?automatic_log",
		type: 'POST',
		data: {
			'autoclave': val,
		},
	}).done(function(ret, log) {
		var obj = jQuery.parseJSON(ret);

		if(obj)
			$.each(obj, function(key,value) {
				// input
				input = $(row).find('[data-name="'+key+'"]');

				// make sure its in the list
				if(key=="cycle_type" && $(input).is("select") && value) {
					if(!$(input).find('[data-name="'+value+'"]').length) {
						markup='<option data-name="'+value+'">'+value+'</option>';
						$(input).find('.custom').prepend(markup);
					}
				}

				// set the value
				if($(input).length) {
					$(input).val(value);
					$(input).change();
				}
			}); 
	});
}

function numbersOnly() {
	jQuery('.numbersOnly').keyup(function () { 
		this.value = this.value.replace(/[^0-9\.]/g,'');
	});
}

function view_log(log, fade=true) {
	tr=$(log).closest("tr");
	id=tr.data("id");

	$.get("?view_log="+id, function(data) {
		console.log(id);
		tr.removeClass('editing');

		if(fade)
			$(tr).fadeOut('fast', function() {
				$(this).replaceWith($(data))
				$(tr).fadeIn('fast');
			});
		else
			$(tr).replaceWith($(data));
	});
}

function edit_log(el, close_others=true) {
	// stop editing others
	if(close_others)
		$('.logs tbody .editing').each(function() {
			console.log("test");
			view_log($(this), false);
		});

	// edit this row
	row=$(el).closest('tr');
	$(row).load("?edit_log="+$(row).data("id"), function() {
		row.addClass('editing');

		// enable datetimes
		$('input.datetime').each(function() {
			$(this).datetimepicker({
				step:5,
				dateFormat: "yy-mm-dd",
				timeFormat:  "hh:mm",
				theme:'dark',
			});
		});

		// enable numbers
		numbersOnly();
	}).hide('fast').fadeIn('fast');
}

function confirmation_close(el) {
	$(el).closest('.confirmation').find('.confirm').fadeOut('fast');
}

function confirmation(el) {
	$(el).closest('.confirmation').find('.confirm').fadeIn('fast');
}

function delete_log(log) {
	// data
	row=$(log).closest('tr');
	id=row.data("id");

	$(row).fadeOut('fast');

	// save value
	$.ajax({
		url: "?delete",
		type: 'POST',
		data: {
			'id': id,
		},
	}).done(function(ret, log) {
		pagination_refresh();
	}).error(function(ret, log) {
		alert('no del');
	});
}

function save_log_add_photo(el) {
	// get and set id
	id=$(el).closest('tr').data("id");
	$('#fileInputID').val(id);

	// select input
	$('#fileInput').trigger('click');
}

function save_log(el) {
	// data
	input=$(el);
	id=$(el).closest('tr').data("id");
	key=$(el).data("name");
	value=$(el).val();

	// pop-up for other
	if(value == "enter_other") {
		name=prompt();
		if (name === "null") {
			// cancel
			$(el).prop("selectedIndex", 0);
		} else {
			// entered
			markup='<option selected="selected" value="'+name+'">'+name+'</option>';
			$(el).find('.custom').append(markup);
			value=name;
		}
	}

	// save value
	$.ajax({
		url: "?save",
		type: 'POST',
		async: false,
		cache: false,
		data: {
			'id': id,
			'key': key,
			'value': value,
		},
	}).done(function(ret) {
		// blink
		if(ret=="saved")
			input.addClass('saved');
		else if(ret=="error")
			input.addClass('notsaved');

	}).error(function(ret) {
		input.addClass('notsaved');
	});

	// reset blink
	setTimeout(function() {
		input.removeClass("saved");
		input.removeClass("notsaved");
	}, 2000);
}

/* autoclaves */
function edit_autoclaves(el) {
	$(el).closest('.autoclaves').find('tr.editing').removeClass('editing');
	edit_row(el);
}

function add_autoclaves_cycles(el) {
	// row
	row = $(el).closest('tr').find('.cycles');

	// grab content
	$.ajax({
		url: "?add_autoclave_cycle",
		async: false,
		cache: false,
		type: 'GET',
	}).done(function(ret) {
		$(row).append(ret);
	});
}

function remove_autoclaves_cycles(el) {
	cycle_row=$(el).closest('tr');

	// remove
	$(el).closest('.cycle').fadeOut( function() {
		$(this).remove();
		save_autoclaves($(cycle_row).find('input[data-name="cycle_name"]:last'));
	});
}

function done_autoclaves(el) {
	row=$(el).closest('tr');

	// disable inputs
	$(row).find('input, select').prop('disabled', true);
	$(row).removeClass('editing');

	// remove empty cycles
	/*	$(row).find('.cycles .cycle').each(function() {
		if($(this).find('input').val() == "")
			$(this).remove();
	});*/
}

function save_autoclaves_order(el) {
	var count=0;
	$(el).closest('table').find('tr').each(function() {
		id=$(this).attr("data-id");
		if(id) {
			$.ajax({
				url: "?save_autoclaves",
				type: 'POST',
				cache: false,
				data: {
					'id': id,
					'key': 'order',
					'value': count,
				},
			})

			count++;
		}
	});
}

function save_autoclaves(el) {
	// set up refreh on popup close
	forceRefresh = true;

	// data
	input=$(el);
	id=$(el).closest('tr.editing').data("id");
	key=$(el).data("name");
	value=$(el).val();

	// auto fill name, if empty
	nickname = $(el).closest('tr').find('input[data-name="nickname"]');
	if($(el).data('name')=="model" && $(nickname).val()=="")
		$(name).val($(el).val());

	// if Scican, then enable IP address
	if($(el).val().indexOf('Statim ') == 0)
		$(el).closest('tr').find('.ip_address').removeClass('hidden');
	else
		$(el).closest('tr').find('.ip_address').addClass('hidden');

	// pop-up for other
	if($(el).val() == "enter_other") {
		name=prompt();
		if (name === "null") {
			// cancel
			$(el).prop("selectedIndex", 0);
		} else {
			// entered
			markup='<option selected="selected" value="'+name+'">'+name+'</option>';
			$(el).find('.custom').append(markup);
			value=name;
		}
	}

	// cycles
	if(key.startsWith("cycle_")) {
		var cycles = new Array();
		var count=0;
		$(el).closest('tr.editing').find('.cycle').each(function() {
			cycles[count]={};
			$(this).find('input').each(function() {
				value = $(this).val();
				key = $(this).data('name');

				cycles[count][key] = value;
			});
			count++;
		});
		key = 'cycles';
		value = JSON.stringify($(cycles));
	}

	// save value
	$.ajax({
		url: "?save_autoclaves",
		type: 'POST',
		async: false,
		cache: false,
		data: {
			'id': id,
			'key': key,
			'value': value,
		},
	}).done(function(ret, log) {
		// blink
		if(ret=="saved")
			input.addClass('saved');
		else if(ret=="error")
			input.addClass('notsaved');

	}).error(function(ret, log) {
		input.addClass('notsaved');
	});

	// reset blink
	setTimeout(function() {
		input.removeClass("saved");
		input.removeClass("notsaved");
	}, 2000);
}

function delete_autoclave(el) {
	row=$(el).closest('tr');

	$.ajax({
		type: "POST",
		url: '?remove_autoclave',
		async: false,
		data: {
			id: $(row).data('id'),
		},
	}).done(function(ret, log) {
		$(row).fadeOut();
	});
}

function add_autoclaves() {
	// set up refreh on popup close
	forceRefresh = true;

	// grab empty new line and add to bottom
	$.get("?add_autoclaves", function( markup ) {
		$('table.autoclaves .tbody').append(markup);
	});
}


/* ui */
function edit_row(el) {
	$(el).closest('tr').addClass('editing');

	// enable input
	$(el).closest('tr').find('input, select').prop('disabled', false);

}

function popup(text, title='', yn=false) {
	$('.popup_content').html(text);
	$('.popup_title').html(title);

	// confirm buttons
	if(yn) $('.popup_confirm').css('display', 'block');

	$('.popup').fadeIn(200);
}

function popup_close() {
	// refresh logs
	pagination_refresh();

	// close popup
	$('.popup_content').html('');
	$('.popup').fadeOut(200);
}

function blink_green(el) {
	color='#030';
	blink(el,color);
}

function blink(el, color) {
	// original color
	orig=$(el).css('background-color');

	// change color
	$(el).css({ 'background-color': color });

	// change back
	setTimeout(function() {
		$(el).css({ 'background-color': orig });
	}, 500);
}


/* operators */
function view_operators(e) {
	$(e).parent().parent().load("?view_operators="+$(e).parent().parent().data("id"));
}

function delete_operators(el) {
	// remove
	$(el).closest('tr').fadeOut( function() {
		$(this).remove();
		save_operators(el);
	});
}

function done_operators(el) {
	$(el).closest('tr').find('input').prop('disabled', true);
	$(el).closest('tr').removeClass('editing');
	save_operators();
}

function save_operators() {
	// set up refreh on popup close
	forceRefresh = true;

	// create operators string
	var operators='';
	var count=0;
	$('.operators').find('input').each(function() {
		if(count) operators+="\n";
		operators+=$(this).val();
		count++;
	});

	// save
	$.ajax({
		url: "?save_operators",
		type: 'POST',
		data: {
			'operators': operators,
		},
	});
}

function edit_operators(el) {
	$(el).closest('.operators').find('tr.editing').removeClass('editing');
	edit_row(el);
}

function add_operators() {
	// grab empty new line and add to bottom
	$.get("?add_operators", function( markup ) {
		$('table.operators tbody').append('<tr class="editing">'+markup+'</tr>');
		save_operators();
	});
}

function time_now() {
	var dt = new Date();
	var year = dt.getFullYear();
	var month = dt.getMonth()+1;
	var day = dt.getDate();

	// what the fuck js... first +1 month now this?
	if(month<10)	month="0"+month;
	if(day<10)	day="0"+day;

	var time = dt.getHours() + ":" + dt.getMinutes(); + ":" + dt.getSeconds();

	return year+"-"+month+"-"+day+" "+time;
}

function timeago(timeago) {
	var diff =  timenow.getTime() - new Date(timeago.attr("data-time")).getTime();

	const seconds = Math.floor(diff / 1000);
	const minutes = Math.floor(seconds / 60);
	const hours = Math.floor(minutes / 60);
	const days = Math.floor(hours / 24);

	const remainingHours = hours % 24;
	const remainingMinutes = minutes % 60;
	const remainingSeconds = seconds % 60;

	var string="Just now";

	if(seconds > 60) {
		string = '';
		if(days>0) string+=days+" day"+(remainingMinutes>1?'s':'')+", ";

		if(remainingHours>0)
			string+=remainingHours+" hour"+(remainingHours>1?'s':'');

		if(remainingMinutes>0 && days==0){
			if(remainingHours>0)
				string+=", ";
			string+=remainingMinutes+" minute"+(remainingMinutes>1?'s':'');
		}

		if(string=='')
			string=remainingSeconds+" second"+(remainingSeconds>1?'s':'');

		string+=' ago';
	}

	timeago.html(string);
}

function notification_close(el) {
	note=$(el).closest('.notification');
	save_setting('notification_hide_'+note.attr("data-name"), true);

	$(note).fadeOut();
}
