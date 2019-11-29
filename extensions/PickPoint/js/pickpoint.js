/**
 * Скрипты расширения PickPoint
 */

jQuery(function ($) {
/* ------------------------------ Статусы заказов ------------------------------ */   
    var availableTags = Object.values( IN_WC_CRM_Pickpoint.orderStatuses );

        function split(val) {
            return val.split(/,\s*/);
        }

        function extractLast(term) {
        return split(term).pop();
    }

    $("#statuses")
        // don't navigate away from the field on tab when selecting an item
        .on("keydown", function (event) {
            if (event.keyCode === $.ui.keyCode.TAB &&
                $(this).autocomplete("instance").menu.active) {
                event.preventDefault();
            }
        })
        .autocomplete({
            minLength: 0,
            source: function (request, response) {
                // delegate back to autocomplete, but extract the last term
                response($.ui.autocomplete.filter(
                    availableTags, extractLast(request.term)));
            },
            focus: function () {
                // prevent value inserted on focus
                return false;
            },
            select: function (event, ui) {
                var terms = split(this.value);
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push(ui.item.value);
                // add placeholder to get the comma-and-space at the end
                terms.push("");
                this.value = terms.join(", ");
                return false;
            }
        });

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

        // Читаем требуемые статусы заказов
        var statuses = $('#statuses').val().split(',').filter(function(val, index){
            return ( val.trim() != '' );
        });

        statuses = statuses.map(function(val){
            return val.trim();
        });
 
        var selectedStatuses = [];
        if (statuses.length > 0){
            for (var key in IN_WC_CRM_Pickpoint.orderStatuses){
                if (statuses.includes(IN_WC_CRM_Pickpoint.orderStatuses[key])){
                    selectedStatuses.push(key);
                }
            }
        }

        if (selectedStatuses.length > 0){
            ajaxRequest['statuses'] = selectedStatuses.join(',');
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