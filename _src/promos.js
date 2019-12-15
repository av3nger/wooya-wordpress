/* global wooyaI18n */

/**
 * External dependencies
 */
import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
const { __ } = wooyaI18n;

/**
 * Internal dependencies
 */
import './app.scss';

import FetchWP from './utils/fetchWP';

import Button from './components/button';
import Notice from './components/notice';

/**
 * Promos React component
 */
class Promos extends React.Component {
	/**
	 * Wooya constructor
	 *
	 * @param {Object} props
	 */
	constructor( props ) {
		super( props );

		this.state = {
			loading: true,
			options: [], // Plugin options.
			error: {
				show: false,
				message: '',
				link: '',
			},
			showProgressModal: false,
		};

		/**
		 * @type {FetchWP}
		 * @param {string} this.props.wpObject.api_url
		 * @param {string} this.props.wpObject.api_nonce
		 * @param {boolean} this.props.wpObject.rest_api
		 */
		this.fetchWP = new FetchWP( {
			restURL: this.props.wpObject.api_url,
			restNonce: this.props.wpObject.api_nonce,
			restActive: this.props.wpObject.rest_api,
		} );
	}

	/**
	 * Init component states
	 */
	componentDidMount() {
		this.fetchWP.get( 'settings' ).then(
			( json ) => this.getElements( json ),
			( err ) => window.console.log( err.message ),
		);
	}

	/**
	 * Hide error message.
	 */
	hideError() {
		this.setState( {
			error: {
				show: false,
				message: '',
				link: '',
			},
		} );
	}

	/**
	 * Render component
	 *
	 * @return {*}  App.
	 */
	render() {
		return (
			<div className="me-main-content">
				{ this.state.error.show &&
				<Notice type="error"
					message={ this.state.error.message }
					link={ this.state.error.link }
					onHide={ this.hideError }
				/> }

				{ this.state.options &&
				<Button
					buttonText={ __( 'Add promotion', 'market-exporter' ) }
					className="wooya-btn wooya-btn-red wooya-btn-center"
					onClick={ () => this.setState( {
						showProgressModal: ! this.state.showProgressModal,
					} ) }
				/> }
			</div>
		);
	}
}

Promos.propTypes = {
	wpObject: PropTypes.object,
};

document.addEventListener( 'DOMContentLoaded', function() {
	const wooyaDiv = document.getElementById( 'wooya_components' );
	if ( wooyaDiv ) {
		ReactDOM.render(
			<Promos wpObject={ window.ajax_strings } />,
			wooyaDiv,
		);
	}
} );
