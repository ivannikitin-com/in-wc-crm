/**
 * Orders2Excel
 * Выгрузка заказов в Excel
 */
jQuery(function($){
    // Кнопка выгрузки
    $('#btnOrder2Excel').on('click', function(){
        $('#loadBanner').show('fast');
        
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
            action: 'orders2excel_prepare_orders',
            ids: selectedIds.join(',')
        };
		$.post(ajaxurl, ajaxRequest)
			.done(function(response){
                var result = JSON.parse(response);
                if (result){
                    if (result.status == 'success'){
                        window.location.assign(result.url);
                    }
                    else{
                        alert(result.message);
                    }
                }
                else{
                   alert( response ); 
                }
                $('#loadBanner').hide('fast'); 				
			})
			.fail(function(xhr, status, error){
                $('#loadBanner').hide('fast');
                alert( status + ': ' + error + ': ' + xhr.responseText );
			});             
    });
});