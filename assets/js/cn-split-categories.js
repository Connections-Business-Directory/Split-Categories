(function( $ ) {

	// If the number of selected items is already 7 or more, disable the checkboxes.
	if ( 7 <= $( '#cn-metabox-id-_category_id_2072 :checkbox:checked' ).length ) {

		$( '#cn-metabox-id-_category_id_2072 :checkbox:not(:checked)' ).prop( 'disabled', true );
	}

	// Add a click event to the "Specializations" category checklist group.
	$( '#cn-metabox-id-_category_id_2072 :checkbox' ).click( function() {

		// If the number of checked items are equal to or greater than 7, disable the checkboxes.
		if ( 7 <= $( '#cn-metabox-id-_category_id_2072 :checkbox:checked' ).length ) {

			$( '#cn-metabox-id-_category_id_2072 :checkbox:not(:checked)' ).prop( 'disabled', true );

			// If the number of checked items is less than 7, enable the checkboxes.
		} else {

			$( '#cn-metabox-id-_category_id_2072 :checkbox' ).prop( 'disabled', false );
		}
	});
})( jQuery );
