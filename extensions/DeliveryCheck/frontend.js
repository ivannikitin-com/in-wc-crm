/**
 * Выгрузка данных в PickPoint
 */
jQuery(function($){
    // Кнопка выгрузки
    $('#btnDeliveryCheck').on('click', function(){
        $('#loadBanner').show('fast');

        // Передаем данные на сервер
        var ajaxRequest = {
            action: 'inwccrm_delivery_check'
        };
		$.post(ajaxurl, ajaxRequest)
			.done(function(response){
            	alert( response );
           		$('#loadBanner').hide('fast'); 				
			})
			.fail(function(xhr, status, error){
            	alert( status + ': ' + error + ': ' + xhr.responseText );
           		$('#loadBanner').hide('fast'); 				
			});             
    });
});