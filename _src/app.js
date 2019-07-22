/* global ajaxurl */
/* global wooyaI18n */
/* global ajax_strings */

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
import Files from './components/files';
import Notice from './components/notice';
import ProgressModal from './components/progress-modal';
import YmlListControl from './components/yml-list-control';

/**
 * Wooya React component
 */
class Wooya extends React.Component {
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
			fields: [], // List of available fields.
			unusedItems: [], // Not used fields (available to add via modal).
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

		const notice = document.getElementById( 'rate-notice' );
		if ( notice ) {
			notice.addEventListener( 'click', () => {
				const xhr = new XMLHttpRequest();
				xhr.open(
					'POST',
					ajaxurl + '?action=dismiss_rate_notice&_ajax_nonce=' + this.props.wpObject.api_nonce
				);
				xhr.send();
			} );
		}
	}

	/**
	 * Init component states
	 */
	componentDidMount() {
		this.fetchWP.get( 'settings' ).then(
			( json ) => this.getElements( json ),
			( err ) => this.showError( 0, err.message )
		);
	}

	/**
	 * Get all the elements and sort out the ones that are in use to a
	 * separate variable.
	 *
	 * @param {json} options
	 */
	getElements( options ) {
		this.fetchWP.get( 'elements' ).then(
			( json ) => {
				const unusedItems = {
					shop: [],
					offer: [],
				};

				Object.entries( json ).forEach( ( type ) => {
					Object.keys( type[ 1 ] ).filter( ( element ) => {
						if ( ! ( type[ 0 ] in unusedItems ) ) {
							return true;
						}

						const unused = 'undefined' === typeof options[ type[ 0 ] ];
						if ( unused || ! ( element in options[ type[ 0 ] ] ) ) {
							unusedItems[ type[ 0 ] ].push( element );
							return false;
						}

						return true;
					} );
				} );

				this.setState( {
					loading: false,
					options,
					fields: json,
					unusedItems,
				} );
			},
			( err ) => this.showError( 0, err.message )
		);
	}

	/**
	 * Display error notice.
	 *
	 * @param {number} code     Error message code.
	 * @param {string} message  Error message text.
	 * @param {string} link     Link for more info button in error message.
	 */
	showError( code, message = '', link = '' ) {
		if ( 'undefined' === typeof code ) {
			return;
		}

		// Try to look for error message by code.
		if ( '' === message ) {
			message = ajax_strings.errors[ 'error_' + code ];
		}

		if ( '' === link ) {
			link = ajax_strings.errors[ 'link_' + code ];
		}

		this.setState( {
			loading: false,
			error: {
				show: true,
				message,
				link,
			},
			showProgressModal: false,
		} );
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
	 * Handle update in the UI settings form.
	 *
	 * @param {Array} options
	 * @param {Array} unusedItems
	 */
	handleUpdate( options, unusedItems ) {
		this.setState( {
			loading: false, // TODO: is this needed?
			options,
			unusedItems,
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
					buttonText={ __( 'Generate YML', 'market-exporter' ) }
					className="wooya-btn wooya-btn-red wooya-btn-center"
					onClick={ () => this.setState( {
						showProgressModal: ! this.state.showProgressModal,
					} ) }
				/> }

				<Files
					fetchWP={ this.fetchWP }
					onError={ ( error ) => this.showError( 0, error ) }
				/>

				<YmlListControl
					options={ this.state.options }
					fields={ this.state.fields }
					unusedItems={ this.state.unusedItems }
					onError={ ( error ) => this.showError( 0, error ) }
					handleUpdate={ ( options, unusedItems ) => {
						this.handleUpdate( options, unusedItems );
					} }
					fetchWP={ this.fetchWP }
				/>

				{ this.state.showProgressModal &&
				<ProgressModal
					onFinish={ () => {
						Files.updateFileList();
						this.setState( { showProgressModal: false } );
					} }
					onError={ ( errorCode ) => {
						this.setState( { showAddDiv: false } );
						this.showError( errorCode );
					} }
					fetchWP={ this.fetchWP }
				/> }
			</div>
		);
	}
}

Wooya.propTypes = {
	wpObject: PropTypes.object,
};

document.addEventListener( 'DOMContentLoaded', function() {
	const wooyaDiv = document.getElementById( 'wooya_components' );
	if ( wooyaDiv ) {
		ReactDOM.render(
			/** @var {object} window.ajax_strings */
			<Wooya wpObject={ window.ajax_strings } />,
			wooyaDiv
		);
	}
} );
