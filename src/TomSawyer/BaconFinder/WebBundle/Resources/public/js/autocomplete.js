$(document).ready(function(){
    function extractLast( term ) {
        return split( term ).pop();
    }

    function split( val ) {
        return val.split( /,\s*/ );
    }

    $('#searchConnection').autocomplete({
            source: function( request, response ) {
                $.post( "/account/friend/search", {
                    term: extractLast( request.term )
                }, response );
            },
            search: function() {
                // custom minLength
                var term = extractLast( this.value );
                if ( term.length < 2 ) {
                    return false;
                }
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var handle = ui.item.label;
                var source = ui.item.value;
                $('#searchConnection').val(handle);
                $.get("/account/profile-info/" + source, function(user){
                    $('#suggestBox').html(user);
                });


                return false;
            }
        }

    )
});