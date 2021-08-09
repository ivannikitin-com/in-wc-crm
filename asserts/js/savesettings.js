jQuery( function( $ ) {
		
		$("#extensionList input[type='checkbox']").on("click", function(e){
			var extensionName = $(this).data('extension');
			var enabled;
			if ($(this).is(':checked')) {
				enabled = '1'
			} else 
			{
				enabled = 'false'
			}
			$.ajax({
				type:'POST',
				url:ajaxurl,
				data:'action=saveSettings&extensionName=' + extensionName + '&enabled='+enabled,
				success:function(results){
					alert (results);
				}
			});
		});
});