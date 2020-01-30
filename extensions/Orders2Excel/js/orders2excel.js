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

        window.location.assign(IN_WC_CRM_Orders2Excel.downloadUrl + selectedIds.join(','));
        $('#loadBanner').hide('fast');             
    });
});