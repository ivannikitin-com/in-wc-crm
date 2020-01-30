/**
 * Выгрузка данных в PickPoint
 */
jQuery(function($){
    // Кнопка выгрузки
    $('#btnPickPoint').on('click', function(){
        $('#loadBanner').show('fast');

        // Читаем список ID выделенных заказов
        var selectedIds = [];
        var selectedRows = $('#orderTable').DataTable().rows('.selected').data();
        for(var i=0; i < selectedRows.length; i++){
            selectedIds.push(selectedRows[i].id)
        }

		if (selectedIds.length == 0){
			$('#loadBanner').hide('fast');
			alert(IN_WC_CRM_Pickpoint.noRowsSelected);
			return;
        }

        // Передаем данные на сервер
        var ajaxRequest = {
            action: 'pickpoint_send_orders',
            ids: selectedIds.join(',')
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