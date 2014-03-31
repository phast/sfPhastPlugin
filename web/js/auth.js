		
	$.ajaxSetup({
		cache: false,
		dataType: 'json'
	});	
		
	$(function(){
	
		$('#signin > form').on('submit', function(){
			var form = $(this);
			if(form.data('locked')) return false;
			form.data('locked', true);
			form.find('dl.error').slideUp(300);
			
			$.post('/admin/?signin', form.serialize(), function(data){
				
				if(data.success){
					document.location.reload();
				}else{
					form.find('dl.error').queue(function(){
						$(this).dequeue().text(data.error || 'Системная ошибка').slideDown(300);
						form.data('locked', false);
					});
				}
				
			});
			
			return false;
		});
		
	});