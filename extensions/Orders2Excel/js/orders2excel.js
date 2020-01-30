/**
 * Orders2Excel
 * Выгрузка заказов в Excel
 */
jQuery(function($){
    // Кнопка выгрузки
    $('#btnOrder2Excel').on('click', function(){
        // Читаем список ID выделенных заказов
        var selectedIds = [];
        var selectedRows = $('#orderTable').DataTable().rows('.selected').data();
        for(var i=0; i < selectedRows.length; i++){
            selectedIds.push(selectedRows[i].id)
        }

		if (selectedIds.length == 0){
			$('#loadBanner').hide('fast');
			alert(IN_WC_CRM_Orders2Excel.noRowsSelected);
			return;
        }

        // Передаем данные на сервер
        var ajaxRequest = {
            action: 'orders2excel_send_orders',
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