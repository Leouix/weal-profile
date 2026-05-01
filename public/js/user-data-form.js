/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-user-account
 */

/**
 * Handles editable user fields.
 */
class UserDataForm {
	user          = {};
	changedFields = [];

	checkIsUserChanged() {
		return this.changedFields.length > 0;
	}

	getNameField( el ) {
		return el.getAttribute( 'name' );
	}

	editingUserData( elInput ) {

		const fieldName = this.getNameField( elInput );

		if ( this.isFieldChanged( elInput ) ) {
			if ( ! this.changedFields.includes( fieldName ) ) {
				this.changedFields.push( fieldName );
			}
		} else {
			this.removeFromChangedFields( fieldName );
		}

		if ( this.checkIsUserChanged() ) {
			toggleBtn( true );
		} else {
			toggleBtn( false );
		}
	}

	isFieldChanged( el ) {
		const dataOrig = el.getAttribute( 'data-orig' );
		const value    = el.value;
		return dataOrig !== value;
	}

	removeFromChangedFields( fieldName ) {
		const index = this.changedFields.indexOf( fieldName );
		if ( index > -1 ) {
			this.changedFields.splice( index, 1 );
		}
	}
}
