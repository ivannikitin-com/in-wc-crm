jQuery( function( $ ) {
    $(document.body).on('updated_checkout', function(){
        let cdekMethods = document.querySelectorAll('input[id*="official_cdek"]');
        cdekMethods.forEach( el => {
            el.parentElement.style.display='none';
        })

        let cdekAdditions = document.querySelectorAll('[class*="official_cdek"]');
        cdekAdditions.forEach( el => {
            el.style.display='none';
        })        

    } );
} );