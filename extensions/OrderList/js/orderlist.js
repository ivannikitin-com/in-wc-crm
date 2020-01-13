/**
 * Список заказов
 */
jQuery(function ($){
    // Инициализация DataTable
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

});