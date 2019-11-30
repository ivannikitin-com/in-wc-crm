/**
 * Скрипты расширения PickPoint
 */

jQuery(function ($) {
    /* ------------------------------ Статусы заказов ------------------------------ */   
    var selStatus = $('#status');
    for (var key in IN_WC_CRM_Pickpoint.orderStatuses){
        var option = new Option();
        option.value = key;
        option.innerHTML = IN_WC_CRM_Pickpoint.orderStatuses[key];
        selStatus.append(option);
    }

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
                { "data":  null, defaultContent: '<input type="checkbox" />' },
                { "data": "id" },
                { "data": "date" },
                { "data": "customer" },
                { "data": "total" },
                { "data": "payment" },
                { "data": "shipping" },
                { "data": "stock" },
                { "data":  null, defaultContent: '<button class="btnViewOrder" title="' + IN_WC_CRM_Pickpoint.viewOrderTitle + '"><i class="fas fa-eye"></i></button>' }
            ]
        })
        .on( 'click', function (e) {

            if (e.srcElement.tagName == 'BUTTON' || e.srcElement.tagName == 'I'){
                var orderId = '';
                try{
                    orderId = $(e.srcElement).parents('tr')[0].cells[1].innerHTML;
                }
                catch (e) {}
    
                if (orderId){
                    location.assign('/wp-admin/post.php?action=edit&post=' + orderId);
                }
            }
        } ); 
    
    /* ----------------------------- Кнопка Найти ------------------------------ */    
    $('#btnLoadOrders').on('click', function(){
        loadOrders();
    });    


    /* ------------------------------ AJAX запрос ------------------------------ */
    loadOrders();
    function loadOrders()
    {
        $('#loadBanner').show('fast');

        var ajaxRequest = {
            action: 'get_orders',
        }; 

        // статус заказов
        var status = $('#status').val();


        if (status != '' ){
            ajaxRequest['status'] = status;
        }

        // Дата начала
        var dateFrom = $('#dateFrom').val().trim();
        if (dateFrom !== '' )
        {
            ajaxRequest['dateFrom'] = rusDateToTimeStamp(dateFrom);
        }

         // Дата конца
         var dateTo = $('#dateTo').val().trim();
         if (dateTo !== '' )
         {
             ajaxRequest['dateTo'] = rusDateToTimeStamp(dateTo);
         }

         // Метод доставки
         var shipping_method = $('#shipping_method').val().trim();
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
            return new Date( dateStr.replace( /(\d{2})\.(\d{2})\.(\d{4})/, "$2/$1/$3") ).getTime() / 1000;
        }


    }

});