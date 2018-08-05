import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';

import './app.scss';

import { __ } from "@wordpress/i18n/build/index";

import Button from './components/button';
import Description from './components/description';
import Files from './components/files';

/**
 * Wooya React component
 */
class Wooya extends React.Component {
	/**
	 * Wooya constructor
	 *
	 * @param props
	 */
	constructor( props ) {
		super( props );

		this.state = {
			loading: false,
			options: []
		};
	}

	handleAddField() {
		alert( 'Add first field' );
	}

	handleGenerateFile() {
		alert( 'Generate YML' );
	}

	/**
	 * Render component
	 *
	 * @returns {*}
	 */
	render() {
		if ( this.state.loading ) {
			return __( 'Loading...', 'wooya' );
		}

		return (
			<div className="me-main-content">
				<Description />

				{ ! this.state.options &&
				<Button
					buttonText={ __( 'Add first field', 'wooya' ) }
					className='button button-primary me-button-callout'
					onClick={ this.handleAddField }
				/> }

				{ this.state.options &&
				<Button
					buttonText={ __( 'Generate YML', 'wooya' ) }
					className='wooya-btn wooya-btn-red'
					onClick={ this.handleGenerateFile }
				/> }

				<h2 className="wooya-files-title">{ __( 'Available YML files', 'wooya' ) }</h2>

				<Files />

				<h2 className="wooya-settings-title">{ __( 'Settings', 'wooya' ) }</h2>


			</div>
		);
	}
}

Wooya.propTypes = {
	wpObject: PropTypes.object
};

document.addEventListener('DOMContentLoaded', function() {
	ReactDOM.render(
		/** @var {object} window.ajax_strings */
		<Wooya wpObject={ window.ajax_strings }/>,
		document.getElementById( 'wooya_components' )
	);
});