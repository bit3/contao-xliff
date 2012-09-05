$(document).addEvent('domready', function() {
	$(document.body).addEvent('click', function() {
		$$('.add-language.active').removeClass('active');
	});

	$$('.add-language').addEvent('click', function(event) {
		event.stopPropagation();
	});

	$$('.add-language button').addEvent('click', function(event) {
		var parent = $(this).getParent('.add-language');
		if (!parent.hasClass('active')) {
			parent.addClass('active');
			event.preventDefault();
		}
	});
});
