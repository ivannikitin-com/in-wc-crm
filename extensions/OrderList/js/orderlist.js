/**
 * Список заказов
 */
jQuery(function ($){
    // ------------------------------ Выбор дат ----------------------------
    $('.datePicker').datepicker();

    // ------------------------------ Select2 ----------------------------
    if( $( 'select' ).length > 0 ) {
        $( 'select' ).select2();
        $( document.body ).on( "click", function() {
             $( 'select' ).select2();
          });
    }

    // ---------------------- Инициализация DataTable ----------------------
    var columns = [];
    for (var colId in IN_WC_CRM_OrderList.columns ){
        columns.push({'data':colId});
    }
    columns.push({'data':null, 'render':function(data,type,row) { 
        return '<a href="/wp-admin/post.php?action=edit&post=' + data["id"] + 
            '" class="btnViewOrder" title="' + IN_WC_CRM_OrderList.viewOrderTitle + 
            '" target="_blank"><i class="fas fa-eye"></i></a>';
        }});
    $('#orderTable').DataTable({
        "language": {
            'url': '//cdn.datatables.net/plug-ins/1.10.20/i18n/Russian.json'
        },
        "pageLength": IN_WC_CRM_OrderList.pageLength,
        "columns": columns
    });

	// ------------------- Выбор и отметка рядов таблицы -------------------
	$('#orderTable tbody').on( 'click', 'tr', function () {
        $(this).toggleClass('selected');
        // Выбранные ряды
        var table = $('#orderTable').DataTable();
        var selectedRowsCount = table.rows('.selected').data().length;
        //console.log('Выбрано рядов', selectedRowsCount);
        if (selectedRowsCount > 0){
            $('#orderListDataTableSelectedRowsCount span').text(selectedRowsCount);
            $('#orderListDataTableSelectedRowsCount').show();
        }
        else {
            $('#orderListDataTableSelectedRowsCount').hide();
        }
    });

    // -------------------------- Загрузка данных --------------------------
    $('#btnLoadOrders').on('click', function(){
        loadOrders();
    });     
    function loadOrders(){
        $('#loadBanner').show('fast');

        var ajaxRequest = {
            action: 'inwccrm_get_order_list'
        }; 
        
        // статус заказов
        var status = $('#order_status').val();
        if (status != '_all' ){
            ajaxRequest['order_status'] = status;
        }

        // Метод доставки
        var shipping_method = $('#shipping_method').val();
        if (shipping_method !== '_all' )
        {
            ajaxRequest['shipping_method'] = shipping_method;
        }

        // Метод оплаты
        var payment_method = $('#payment_method').val();
        if (payment_method !== '_all' )
        {
            ajaxRequest['payment_method'] = payment_method;
        }

        // Дата начала
        var dateFrom = $('#dateFrom').val();
        if (dateFrom !== '' )
        {
            ajaxRequest['dateFrom'] = rusDateToTimeStamp(dateFrom);
        }

         // Дата конца
         var dateTo = $('#dateTo').val();
         if (dateTo !== '' )
         {
             ajaxRequest['dateTo'] = rusDateToTimeStamp(dateTo) + 86400 - 1; // Секунд в сутках минус 1
         }

         // Добавим все дополнительные параметры, обозначенные классом customFilter
         $('#orderListControls .customFilter').each(function(index,element){
            ajaxRequest[element.id] = element.value;
         });

         $.post( ajaxurl, ajaxRequest, function(response) {
            var dataSet = JSON.parse( response );
            $('#orderTable').dataTable().fnClearTable();
            if (dataSet.length > 0 ) $('#orderTable').dataTable().fnAddData( dataSet );
           $('#loadBanner').hide('fast');   
        });         

         function rusDateToTimeStamp( dateStr ){
            dateStr += ''; // Явное преобразование к строке
            var date = new Date( dateStr.replace( /(\d{2})\.(\d{2})\.(\d{4})/, "$2/$1/$3") );
            var timestamp = date.getTime() / 1000;
            return timestamp;
        }  

    }

  

});