jQuery(function() {
	var offset = 0, current = 0;

	// check vars
	if ( typeof parseInt(umL10n.ppp) != 'number') {
		throw new Error(umL10n.ppp_valid_message);
	}
	
	jQuery('input[name="submit-flush"]').click(function(event) {
		if ( !confirm(umL10n.confirm) ) {
			event.preventDefault();
		}
	});

	jQuery('#populateUrl').submit(function(e) {
		// Stop propagation
		e.preventDefault();

		var form = jQuery(this), nonce = form.find('#_wpnonce').val(), messagediv = form.find('.message'), pg = jQuery('.ulPg');

		pg.progressbar({
			value : 0
		});

		// Check ajaxing or not
		if (form.hasClass('ajaxing')) {
			return false;
		}

		// Add the class
		form.addClass('ajaxing');

		addMessage(umL10n.start_processus_message);

		// Launch the ajax
		umL10nulate(0);

		function umL10nulate(off) {
			jQuery.ajax({
				url : ajaxurl,
				data : {
					offset : off,
					'nonce' : nonce,
					'action' : 'urlmPopulate'
				},
				beforeSend : function() {
					addMessage(umL10n.processing_message + ' ' + off + '/' + umL10n.total_objects);
				},
				success : function(response) {
					pg.progressbar({
						value : (off / umL10n.total_objects ) * 100
					});
					if (response != '1') {
						form.removeClass('ajaxing');
						addMessage(umL10n.end_processus_message);
						return false;
					} else {
						current++;
						umL10nulate(current * umL10n.ppp);
					}
				}
			});
		};

		function addMessage(message) {
			if (messagediv.is(':hidden'))
				messagediv.slideDown('fast');

			jQuery('<li>').html(message).prependTo(messagediv);
		}
	});
}); 