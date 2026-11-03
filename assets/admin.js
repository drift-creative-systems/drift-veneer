/*
 * Drift: Veneer: Settings → Drift: Veneer.
 * Media Library picker (.drift-veneer-media) and colour pickers
 * (.drift-veneer-colour). Markup: includes/class-drift-veneer-admin.php.
 */
/* global DRIFT_VENEER, jQuery, wp */
(function ($) {
	'use strict';

	var i18n = (window.DRIFT_VENEER && DRIFT_VENEER.i18n) || {};

	// Media picker: stores the chosen image's full URL in a hidden input.
	$(document).on('click.drift_veneer', '.drift-veneer-media__select', function (e) {
		e.preventDefault();

		if (!window.wp || !wp.media) {
			return;
		}

		var $field   = $(this).closest('.drift-veneer-media');
		var $input   = $field.find('input[type="hidden"]');
		var $preview = $field.find('.drift-veneer-media__preview');
		var $remove  = $field.find('.drift-veneer-media__remove');
		var $select  = $(this);

		var frame = wp.media({
			title: i18n.selectImage || 'Select image',
			button: { text: i18n.useImage || 'Use this image' },
			multiple: false,
			library: { type: 'image' }
		});

		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			var previewUrl = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;

			$input.val(attachment.url).trigger('change');
			$preview.empty().append($('<img>').attr({ src: previewUrl, alt: '' })).prop('hidden', false);
			$remove.prop('hidden', false);
			$select.text(i18n.change || 'Change image');
		});

		frame.open();
	});

	$(document).on('click.drift_veneer', '.drift-veneer-media__remove', function (e) {
		e.preventDefault();

		var $field = $(this).closest('.drift-veneer-media');
		$field.find('input[type="hidden"]').val('').trigger('change');
		$field.find('.drift-veneer-media__preview').empty().prop('hidden', true);
		$field.find('.drift-veneer-media__select').text(i18n.selectImage || 'Select image').trigger('focus');
		$(this).prop('hidden', true);
	});

	$(function () {
		if ($.fn.wpColorPicker) {
			$('.drift-veneer-colour').wpColorPicker();
		}
	});
}(jQuery));
