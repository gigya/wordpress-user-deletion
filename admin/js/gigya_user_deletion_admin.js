jQuery(function ($) {
	$(document).ready(function () {
		$('#gigya_aws_region').change(function () {
			var aws_region_text_obj = $('#gigya_aws_region_text');
			var aws_region_select_obj = $('#gigya_aws_region');

			aws_region_text_obj.attr('value', aws_region_select_obj.val());
			if (aws_region_select_obj.find("option:selected").text() === 'Other') {
				aws_region_text_obj.prop('readonly', false);
				aws_region_text_obj.val('');
				aws_region_text_obj.focus();
			} else {
				aws_region_text_obj.prop('readonly', true);
			}
		});
	});
});