/**
 * Скрипты расширения PickPoint
 */

jQuery(function ($) {
    /* ------------------------------ Методы доставки ------------------------------ */   
    var selStatus = $('#shipping_method');
    for (var key in IN_WC_CRM_Pickpoint.shippingMethods){
        var option = new Option();
        option.value = key;
        option.innerHTML = IN_WC_CRM_Pickpoint.shippingMethods[key];
        selStatus.append(option);
    }

    /* ------------------------------ Выбор дат ------------------------------ */
    $('.datePickers').datepicker();
    
    /* ------------------------------ DataTable ------------------------------ */
    $('#orderTable')
        .DataTable({
            "language": {
                'url': '//cdn.datatables.net/plug-ins/1.10.20/i18n/Russian.json'
            },
            "columns": [
                { "data": "id" },
                { "data": "date" },
                { "data": "customer" },
                { "data": "total" },
                { "data": "payment_method" },
                { "data": "shipping_method" },
                { "data": "stock" },
                { "data":  null, defaultContent: '<button class="btnViewOrder" title="' + IN_WC_CRM_Pickpoint.viewOrderTitle + '"><i class="fas fa-eye"></i></button>' }
            ]
        });

        $('#orderTable tbody').on( 'click', 'tr', function (e) {
            if (e.srcElement.tagName == 'BUTTON' || e.srcElement.tagName == 'I'){
                orderId = $(e.srcElement).parents('tr')[0].cells[1].innerHTML;
                location.assign('/wp-admin/post.php?action=edit&post=' + orderId);
            }
            else{
                $(this).toggleClass('selected');
            }
            
        } );
    
    /* --------------------------------- Кнопки  ------------------------------- */    
    $('#btnLoadOrders').on('click', function(){
        loadOrders();
    });    
    $('#btnSendOrders').on('click', function(){
        sendOrders();
    }); 

    /* --------------------- AJAX запрос списка заказов ------------------------ */
    loadOrders();
    function loadOrders()
    {
        $('#loadBanner').show('fast');

        var ajaxRequest = {
            action: 'get_orders'
        }; 

        // статус заказов
        var status = $('#status').val();


        if (status != '' ){
            ajaxRequest['status'] = status;
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
             ajaxRequest['dateTo'] = rusDateToTimeStamp(dateTo);
         }

         // Метод доставки
         var shipping_method = $('#shipping_method').val();
         if (shipping_method !== '' )
         {
             ajaxRequest['shipping_method'] = shipping_method;
         }


		$.post( ajaxurl, ajaxRequest, function(response) {
            var dataSet = JSON.parse( response );
            $('#orderTable').dataTable().fnClearTable();
            if (dataSet.length > 0 ) $('#orderTable').dataTable().fnAddData( dataSet );
           $('#loadBanner').hide('fast');   
        });
        
        function rusDateToTimeStamp( dateStr ){
			dateStr += ''; // Явное преобразование к строке
            return new Date( dateStr.replace( /(\d{2})\.(\d{2})\.(\d{4})/, "$2/$1/$3") ).getTime() / 1000;
        }
    }

    /* --------------------- AJAX запрос отправки заказов ------------------------ */
    function sendOrders()
    {
        $('#loadBanner').show('fast');

        // Читаем список ID выделенных заказов
        var selectedIds = [];
        var selectedRows = $('#orderTable').DataTable().rows('.selected').data();
        for(var i=0; i < selectedRows.length; i++){
            selectedIds.push(selectedRows[i].id)
        }

        // Передаем данные на сервер
        var ajaxRequest = {
            action: 'send_orders',
            ids: selectedIds.join(',')
        };

		$.post( ajaxurl, ajaxRequest, function(response) {
            
            alert( response );

           $('#loadBanner').hide('fast');   
        });
   
    }

});