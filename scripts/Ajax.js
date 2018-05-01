$(document).ready(function(e) {
	M.AutoInit();
	$('.modal').modal({
		dismissible: true,
		onCloseEnd: function() {
			$('#preview').attr("src", "");
			$('#datei').val("");
			$('#bildunterschrift').val("");
		}
	});
	$("#datei").change(function() {
		showPreview(this);
	});
});

function showPreview(input) {
	if (input.files && input.files[0]) {
		var reader = new FileReader();
		reader.onload = function(e) {
			$('#preview').attr('src', e.target.result);
		}
		reader.readAsDataURL(input.files[0]);
	}
}
