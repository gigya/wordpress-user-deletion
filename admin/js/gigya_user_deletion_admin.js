jQuery(function ($) {
	$(document).ready(function () {
		var aws_region_obj = $("#gigya_aws_region");
		if (aws_region_obj.find("option:selected").val() !== 'other') {
			$('#gigya_aws_region_text').hide();
		}
		aws_region_obj.change(function () {
			var aws_region_text_obj = $('#gigya_aws_region_text');
			var aws_region_select_obj = $('#gigya_aws_region');

			aws_region_text_obj.attr('value', aws_region_select_obj.val());
			if (aws_region_select_obj.find("option:selected").val() === 'other') {
				aws_region_text_obj.show();
				aws_region_text_obj.prop('readonly', false);
				aws_region_text_obj.val('');
				aws_region_text_obj.focus();
			} else {
				aws_region_text_obj.prop('readonly', true);
				aws_region_text_obj.hide();
			}
		});
	});
});