/**
 * Подготовка PDF счетов
 */
jQuery(function($){
    // Кнопка выгрузки
    $('#btnPDFInvoices').on('click', function(){
        $('#loadBanner').show('fast');

    // Читаем список ID выделенных заказов
    var selectedIds = [];
    var selectedRows = $('#orderTable').DataTable().rows('.selected').data();
    for(var i=0; i < selectedRows.length; i++){
        selectedIds.push(selectedRows[i].id)
    }

    if (selectedIds.length == 0){
        $('#loadBanner').hide('fast');
        alert(IN_WC_CRM_PDFInvoices.noRowsSelected);
        return;
    }

    // Передаем данные на сервер
    var ajaxRequest = {
        action: 'pdfinvoices_get_invoices',
        ids: selectedIds.join(',')
    };
    $.post(ajaxurl, ajaxRequest)
        .done(function(response){
            if (response.lenght == 0) {
                alert(IN_WC_CRM_PDFInvoices.emptyResponse);
            }
            else{
                response = response.replace('&amp;', '&');
                // Хитрый способ открыть URL в новом окне вместо window.open(response, '_blank');
                Object.assign(document.createElement('a'), {
                    target: '_blank',
                    href: response
                }).click();
            }
            $('#loadBanner').hide('fast'); 				
        })
        .fail(function(xhr, status, error){
            alert( status + ': ' + error + ': ' + xhr.responseText );
            $('#loadBanner').hide('fast'); 				
        }); 
    });
});