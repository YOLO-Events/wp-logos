/* global jQuery, wpLogosAdmin, wp */
( function ( $ ) {
	'use strict';

	/**
	 * Initialise the media uploader for a single image field wrapper.
	 *
	 * @param {jQuery} $wrapper The .wp-logos-image-uploader element.
	 */
	function initUploader( $wrapper ) {
		var $input   = $wrapper.find( 'input[type="hidden"]' );
		var $preview = $wrapper.find( '.wp-logos-preview' );
		var $previewImg = $preview.find( 'img' );
		var $uploadBtn  = $wrapper.find( '.wp-logos-upload-btn' );
		var $removeBtn  = $wrapper.find( '.wp-logos-remove-btn' );
		var mediaFrame;

		$uploadBtn.on( 'click', function ( e ) {
			e.preventDefault();

			if ( mediaFrame ) {
				mediaFrame.open();
				return;
			}

			mediaFrame = wp.media( {
				title:    wpLogosAdmin.uploadTitle,
				button:   { text: wpLogosAdmin.uploadButton },
				multiple: false,
				library:  { type: 'image' },
			} );

			mediaFrame.on( 'select', function () {
				var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();

				$input.val( attachment.id );

				var thumbUrl = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;

				if ( $previewImg.length ) {
					$previewImg.attr( 'src', thumbUrl );
				} else {
					$preview.append( $( '<img>', { src: thumbUrl, style: 'max-width:200px;max-height:120px;' } ) );
				}

				$preview.show();
				$removeBtn.show();
				$uploadBtn.text( wpLogosAdmin.changeImage || 'Change Image' );
			} );

			mediaFrame.open();
		} );

		$removeBtn.on( 'click', function ( e ) {
			e.preventDefault();
			$input.val( '' );
			$preview.hide();
			$removeBtn.hide();
			$uploadBtn.text( wpLogosAdmin.uploadImage || 'Upload / Select Image' );
		} );
	}

	$( function () {
		$( '.wp-logos-image-uploader' ).each( function () {
			initUploader( $( this ) );
		} );
	} );
} )( jQuery );
