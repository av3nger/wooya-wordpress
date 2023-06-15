/* global ajaxurl */
/* global wooyaI18n */

/**
 * External dependencies
 */
import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
const { __ } = wooyaI18n;

/**
 * Internal dependencies
 */
import { Root, SidebarNavigation } from '@yoast/ui-library';
import FetchWP from './utils/fetchWP';
import './app.scss';

/*
import Button from './components/button';
import Files from './components/files';
import Notice from './components/notice';
import ProgressModal from './components/progress-modal';
import YmlListControl from './components/yml-list-control';
*/

/**
 * Wooya functional React component
 *
 * @param {Object} props
 * @return {JSX.Element} Wooya component
 */
export const Wooya = ( props ) => {
	const [ loading, setLoading ] = useState( true );
	const [ options, setOptions ] = useState( [] ); // Plugin options.
	const [ fields, setFields ] = useState( [] ); // List of available fields.
	const [ unusedItems, setUnusedItems ] = useState( {} ); // Not used fields (available to add via modal).
	const [ error, setError ] = useState( {
		show: false,
		message: '',
		link: '',
	} );
	const [ showProgressModal, setShowProgressModal ] = useState( false );

	/**
	 * @type {FetchWP}
	 * @param {string}  props.wpObject.api_url
	 * @param {string}  props.wpObject.api_nonce
	 * @param {boolean} props.wpObject.rest_api
	 */
	const fetchWP = new FetchWP( {
		restURL: props.wpObject.api_url,
		restNonce: props.wpObject.api_nonce,
		restActive: props.wpObject.rest_api,
	} );

	const notice = document.getElementById( 'rate-notice' );
	if ( notice ) {
		notice.addEventListener( 'click', () => {
			const xhr = new XMLHttpRequest();
			xhr.open(
				'POST',
				ajaxurl + '?action=dismiss_rate_notice&_ajax_nonce=' + props.wpObject.api_nonce
			);
			xhr.send();
		} );
	}

	useEffect( () => {
		fetchWP.get( 'settings' ).then(
			( json ) => getElements( json ),
			( err ) => showError( 0, err.message )
		);
	} );

	/**
	 * Get all the elements and sort out the ones that are in use to a
	 * separate variable.
	 *
	 * @param {Object} pluginOptions
	 */
	const getElements = ( pluginOptions ) => {
		fetchWP.get( 'elements' ).then(
			( json ) => {
				const unusedOptionItems = {
					shop: [],
					offer: [],
				};

				Object.entries( json ).forEach( ( type ) => {
					Object.keys( type[ 1 ] ).filter( ( element ) => {
						if ( ! ( type[ 0 ] in unusedOptionItems ) ) {
							return true;
						}

						const unused = 'undefined' === typeof pluginOptions[ type[ 0 ] ];
						if ( unused || ! ( element in pluginOptions[ type[ 0 ] ] ) ) {
							unusedOptionItems[ type[ 0 ] ].push( element );
							return false;
						}

						return true;
					} );
				} );

				setLoading( false );
				setOptions( options );
				setFields( json );
				setUnusedItems( unusedOptionItems );
			},
			( err ) => showError( 0, err.message )
		);
	};

	/**
	 * Display error notice.
	 *
	 * @param {number} code    Error message code.
	 * @param {string} message Error message text.
	 * @param {string} link    Link for more info button in error message.
	 */
	const showError = ( code, message = '', link = '' ) => {
		if ( 'undefined' === typeof code ) {
			return;
		}

		// Try to look for error message by code.
		if ( '' === message ) {
			message = props.wpObject.errors[ 'error_' + code ];
		}

		if ( '' === link ) {
			link = props.wpObject.errors[ 'link_' + code ];
		}

		setLoading( false );
		setError( {
			show: true,
			message,
			link,
		} );
		setShowProgressModal( false );
	};

	/**
	 * Hide error message.
	 */
	const hideError = () => {
		setError( {
			show: false,
			message: '',
			link: '',
		} );
	};

	/**
	 * Handle update in the UI settings form.
	 *
	 * @param {Array} options
	 * @param {Array} unusedItems
	 */
	const handleUpdate = ( options, unusedItems ) => {
		setLoading( false ); // TODO: is this needed?
		setOptions( options );
		setUnusedItems( unusedItems );
	};

	return (
		<Root context={ { isRtl: false } }>
			<SidebarNavigation>
				<SidebarNavigation.Sidebar className="yst-w-1/3">
					<SidebarNavigation.MenuItem
						defaultOpen
						id="menuitem"
						label="MenuItem label"
					/>
				</SidebarNavigation.Sidebar>
			</SidebarNavigation>
			{ __( 'Test', 'market-exporter' ) }
		</Root>
	);
};

Wooya.propTypes = {
	wpObject: PropTypes.object,
};

document.addEventListener( 'DOMContentLoaded', function() {
	const wooyaDiv = document.getElementById( 'wooya_components' );
	if ( wooyaDiv ) {
		const root = createRoot( wooyaDiv );
		root.render(
			/** @member {Object} window.ajax_strings */
			<Wooya wpObject={ window.ajax_strings } />,
		);
	}
} );
