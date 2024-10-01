/*
 * SMTP Mail
 */
jQuery(function($){

	$('#smtpmail_options_SMTPSecure').on('change', function(){
		var self = $(this),
			v = self.val();
		
		var port = 25;
		if( v == 'ssl' ) {
			port = 465; 
		} else if( v == 'tls' ) {
			port = 587;
		}
		
		$('#smtpmail_options_Port').each(function(){ 
			var p = $(this);
			
			if( p.val()!= port ) {
				p.val( port );
			}
		});
	});
	
	var tabs = $('.smtpmail_tabmenu li').each(function(i){
		$(this).click(function(e){
			e.preventDefault();
			
			tabs.removeClass('active').eq(i).addClass('active');
			$('.smtpmail_tabitem').removeClass('active').eq(i).addClass('active');
		});
	});

	// Since 1.3
	$('#smtpmail_options_isSMTP').on('change', function(){

		$('tr.smtp-setting').toggleClass('hidden', $(this).val()!= 1 );
		$('tr.sendgrid-setting').toggleClass('hidden', $(this).val()!= 2 );
		$('tr.unsendgrid-setting').toggleClass('hidden', $(this).val()== 2 );
		
	});

	// notice-dismiss
	$('.contact-form-7-preview-notice-new').each(function(){
		var notice = $(this),
			update = notice.data('update') || '',
			name = notice.data('name') || '';

		if( name!='' && typeof ajaxurl != 'undefined' ) {
			notice.on('click', '.notice-dismiss', function(){
				$.post( ajaxurl, {
					'action': name,
					'update': update
				}, function(response) {
					
				} );
			});
		}
	});

	$('#message.error').each(function(){
		var m = $(this), c = 0,
			n = $('p', m).each(function(){
				var p = $(this);
				if( p.text().search('cf7-review') > -1 && p.text().search('deactivated') > -1 ) {
					p.remove();
					c++;
				}
			}).length;
		
		if( c >= n ) {
			m.hide();
		}
	});

});