import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';

import './app.scss';

import { __ } from "@wordpress/i18n/build/index";
import fetchWP from './utils/fetchWP';

import Button from './components/button';
import Description from './components/description';
import Files from './components/files';
import YmlListControl from './components/yml-list-control';

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
			loading: true,
			options: [],           // Plugin options
			headerFields: [],      // List of all available header fields
			headerItems: [],       // Currently used header fields
			unusedHeaderItems: [], // Not used header fields (available to add)
			updateError: false,
			updateMessage: ''
		};

		// Bind the this context to the handler function.
		this.handleItemMove = this.handleItemMove.bind(this);

		/**
		 * @type {fetchWP}
		 * @param {string} this.props.wpObject.api_url
		 * @param {string} this.props.wpObject.api_nonce
		 */
		this.fetchWP = new fetchWP( {
			restURL: this.props.wpObject.api_url,
			restNonce: this.props.wpObject.api_nonce,
		} );
	}

	/**
	 * Init component states
	 */
	componentDidMount() {
		this.fetchWP.get( 'settings' ).then(
			( json ) => this.getHeaderElements( json ),
			( err )  => console.log( 'error', err )
		);
	}

	/**
	 * Get header elements
	 *
	 * @param options
	 */
	getHeaderElements( options ) {
		this.fetchWP.get( 'elements/header' ).then(
			( json ) => {
				let unusedItems = [];

				// Build the current items list.
				const items = Object.keys( json ).filter( item => {
					if ( 'undefined' === typeof options[item] ) {
						unusedItems.push( item );
						return false;
					}

					return true;
				} );

				this.setState( {
					loading: false,
					options: options,
					headerFields: json,
					headerItems: items,
					unusedHeaderItems: unusedItems
				} );
			},
			( err ) => console.log( 'error', err )
		);
	}

	handleAddField() {
		alert( 'Add first field' );
	}

	handleGenerateFile() {
		alert( 'Generate YML' );
	}

	/**
	 * Handle item move (add/remnove from YML list)
	 *
	 * @param {array}  items
	 * @param {string} action  Accepts: 'add', 'remove'.
	 */
	handleItemMove( items, action = 'add' ) {
		this.setState({
			loading: true
		});

		this.fetchWP.post( 'settings', { items: items, action: action } ).then(
			( json ) => this.moveItem( items, action ),
			( err )  => this.setState({ updateError: true, updateMessage: err.message })
		);
	}

	/**
	 * Move item in the UI.
	 *
	 * @param {array}  items
	 * @param {string} action
	 */
	moveItem( items, action ) {
		let headerItems = this.state.headerItems.slice();
		let unusedHeaderItems = this.state.unusedHeaderItems.slice();

		items.forEach((item) => {
			if ( 'add' === action ) {
				const index = unusedHeaderItems.indexOf( item );
				headerItems = headerItems.concat( unusedHeaderItems.splice( index, 1 ) );
			} else {
				const index = headerItems.indexOf( item );
				unusedHeaderItems = unusedHeaderItems.concat( headerItems.splice( index, 1 ) );
			}
		});

		this.setState({
			loading: false,
			headerItems: headerItems,
			unusedHeaderItems: unusedHeaderItems
		});
	}

	/**
	 * Render component
	 *
	 * @returns {*}
	 */
	render() {
		if ( this.state.loading ) {
			return (
				<div className="me-main-content">
					<div className="wooya-description">
						<p>{ __( 'Loading...', 'wooya' ) }</p>
					</div>
				</div>
			)
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

				<Files />

				<YmlListControl
					settings={ this.state.options }
					fetchWP={ this.fetchWP } // TODO: do we need this?
					headerFields={ this.state.headerFields }
					headerItems={ this.state.headerItems }
					unusedHeaderItems={ this.state.unusedHeaderItems }
					handleItemMove={ this.handleItemMove }
					error={ this.state.updateError }
					errorMsg={ this.state.updateMessage }
				/>
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