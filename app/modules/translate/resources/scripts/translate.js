
$(document).ready(function(){

    $('.hasTooltip').tooltip({html: true});
    $('.hasPopover').popover();

    /* Toggle translations */
    $('.toggle > i').click(function(){
    	var data = $(this).data('id');
    	$('#'+data).children('.translations-wrapper').slideToggle(200);

    	if($(this).hasClass('glyphicon-chevron-down')){
    		$(this).removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
    	} else {
    		$(this).removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
    	}
    });

    /* Toggle original text edit */
    $('.original-text span').click(function(){
		$(this).hide();
		$(this).siblings('textarea').show();
    });

    /* Select language */
    var select = function() {
        $('.select').selectric({
            optionsItemBuilder: function (itemData, element, index) {
                var img = element.val();
                img = (typeof img != 'undefined' && img.length) ? '<img src="' + modURL + img + '.gif" /> ' : '';
                return img + itemData.text;
            },
            maxHeight: 200,
            onChange: function (element) {
                $(element).change();
                var textarea = $(element).parents('li').find('textarea');
                textarea.prop('name', $(element).data('file') + '[' + $(element).data('id')+ ']' + '[translations][' + $(element).val() + ']');

                if(!$(element).val().length){
                    textarea.attr('disabled', true);
                } else {
                    textarea.removeAttr('disabled');
                }
            },
            expandToItemText: true
        });
    }
    select();

	/* Delete translation */
	$('.close').click(function(){
		if(confirm('Are you sure you want to delete this translation?')){
			var item = $(this).parents('li');
			item.slideUp(200, function(){
				item.remove();
			});
		}
	});

	/* Add new language */
	$('.add-translation').click(function(e){
		e.preventDefault();

        var id = $(this).data('id');
        var file = $(this).data('file');
        var lang = [];

        lang.push('<li class="list-group-item">');
            lang.push('<div class="language">');
                lang.push('<select class="select" data-id="'+id+'" data-file="'+file+'">');
                    lang.push('<option value="">-</option>');
                    languages.forEach(function(language){
                        lang.push('<option value="'+language+'">'+language+'</option>');
                    });
                lang.push('</select>');
            lang.push('</div>');
            lang.push('<div class="translation">');
                lang.push('<textarea class="form-control input-sm" rows="1" name="" disabled></textarea>');
            lang.push('</div>');
            lang.push('<div class="delete">');
                lang.push('<button type="button" class="close hasTooltip" data-original-title="Delete translation" data-placement="right">');
                    lang.push('<span>&times;</span>');
                lang.push('</button>');
            lang.push('</div>');
            lang.push('<div class="clearfix"></div>');
        lang.push('</li>');

        $('#'+id).find('ul.list-group').append(lang.join(''));
        select();
	});
});