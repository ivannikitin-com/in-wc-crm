/**
 * Скрипты расширения PickPoint
 */

jQuery(function ($) {
/* ------------------------------ Статусы заказов ------------------------------ */   
    var availableTags = Object.values( IN_WC_CRM_Pickpoint.orderStatuses );
    console.log(availableTags)

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
    $('#orderTable').DataTable({
        "language": {
            'url': '//cdn.datatables.net/plug-ins/1.10.20/i18n/Russian.json'
        }
    });    
});