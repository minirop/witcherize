function check_for_illegal_input(id)
{
	var elem = document.getElementById(id);
	if(elem.value == '')
	{
		elem.focus();
		return false;
	}
	else
		if(elem.value.match(/[%\/]/))
		{
			alert('% and / are illegal characters');
			elem.focus();
			return false;
		}
	
	return true;
}

$(document).ready(function() {
	$('#edit-link').click(function(ev) {
		$('#image-data').toggle(function(ev) {
			if($(this).is(':visible'))
			{
				$('html, body').animate({
					scrollTop: $(document).height()
				}, 1000);
			}
		});
		ev.preventDefault();
	});
});