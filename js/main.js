jQuery(function($) {
    $('table#files tbody tr').each(function() {
        var row = $(this),
            link = $(this).find('a');
        if (!link.length) return;
        
        row.css('cursor', 'pointer');
        row.click(function(event){
            event.preventDefault();
            window.location = link.attr('href');
        });
    });
});