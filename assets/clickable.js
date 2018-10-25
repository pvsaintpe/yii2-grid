$(function() {
    $('body').on('click', '.clickable-row>td', function(e) {
        if ($(this).get(0).dataset.columnClickable != 0) {
            $('.clickable-row').removeClass('active');
            $(this).parent().addClass('active');
        }
    });
});