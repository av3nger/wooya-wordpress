/* global wooyaI18n */

/**
 * External dependencies
 */
import React from 'react';
import PerfectScrollbar from 'perfect-scrollbar';

const { __ } = wooyaI18n;

/**
 * Internal dependencies
 */
import Button from '../button';

import './style.scss';

/**
 * Add new setting modal
 *
 * @since 2.0.0
 */
class AddSettingModal extends React.Component {
	/**
	 * Files constructor
	 *
	 * @param {Object} props
	 */
	constructor( props ) {
		super( props );

		this.state = {
			selectedItems: {
				shop: [],
				offer: [],
			},
		};

		this.getSelectedItems = this.getSelectedItems.bind( this );
	}

	/**
	 * Init component states
	 */
	componentDidMount() {
		// Scroll box support.
		const containers = document.querySelectorAll( '.wooya-select-box' );
		containers.forEach( ( container ) => new PerfectScrollbar( container ) );

		// Hide the modal if clicked outside the modal region.
		const modal = document.querySelector( '.wooya-add-setting-modal' );
		document.addEventListener( 'mousedown', ( e ) => {
			if ( e.target === modal ) {
				this.props.hideModal();
			}
		} );
	}

	/**
	 * Handle click on: Offer/Shop selector.
	 *
	 * @param {Object} e
	 */
	static toggleType( e ) {
		const selector = document.querySelector( '.wooya-modal-content .wooya-switch > input[type="checkbox"]' );

		const showEl = document.querySelector( '.wooya-select-box:not(.hidden)' );
		const hideEl = document.querySelector( '.wooya-select-box.hidden' );

		selector.checked = 'offer' === hideEl.dataset.type;

		showEl.classList.toggle( 'hidden' );
		hideEl.classList.toggle( 'hidden' );
	}

	/**
	 * Get selected inputs.
	 */
	getSelectedItems() {
		const items = document.querySelectorAll( '.wooya-select-box input[type="checkbox"]' );

		const selected = {
			shop: [],
			offer: [],
		};

		items.forEach( ( item ) => {
			if ( item.checked ) {
				selected[ item.dataset.type ].push( item.id );
			}
		} );

		this.setState( {
			selectedItems: selected,
		} );
	}

	/**
	 * Render component
	 *
	 * @return {*} Component.
	 */
	render() {
		// Build the unused items list.
		const itemAvailable = [];

		// Build the current items list.
		Object.keys( this.props.fields ).forEach( ( field ) => {
			itemAvailable[ field ] = Object.entries( this.props.fields[ field ] )
				.filter( ( item ) => {
					if ( 'undefined' !== typeof this.props.items[ field ] ) {
						return this.props.items[ field ].includes( item[ 0 ] );
					}
				} )
				.map( ( item ) => {
					return (
						<div className="wooya-new-item" data-name={ item[ 0 ] }>
							<input type="checkbox" name={ item[ 0 ] } id={ item[ 0 ] } data-type={ field } onClick={ this.getSelectedItems } />
							<label htmlFor={ item[ 0 ] }>
								{ item[ 0 ] }
							</label>

							<p>{ __( this.props.fields[ field ][ item[ 0 ] ].description, 'market-exporter' ) }</p>
						</div>
					);
				} );
		} );

		return (
			<div className="wooya-add-setting-modal wooya-modal">
				<div className="wooya-modal-content">
					<span className="wooya-close" onClick={ this.props.hideModal }>&times;</span>
					<h3>{ __( 'Add new setting', 'market-exporter' ) }</h3>

					<span className="wooya-switch-label" data-type="shop" onClick={ AddSettingModal.toggleType }>
						{ __( 'Shop', 'market-exporter' ) }
					</span>

					<label className="wooya-switch">
						<input type="checkbox" onClick={ AddSettingModal.toggleType } />
						<span className="slider">&nbsp;</span>
					</label>

					<span className="wooya-switch-label" data-type="offer" onClick={ AddSettingModal.toggleType }>
						{ __( 'Offer', 'market-exporter' ) }
					</span>

					<div className="wooya-select-box" data-type="shop">
						<h4>{ __( 'Select setting', 'market-exporter' ) }</h4>

						{ itemAvailable.shop.length > 0 && itemAvailable.shop }
						{ itemAvailable.shop.length === 0 &&
						<h2>
							{ __( 'No available items to select', 'market-exporter' ) }
						</h2>
						}
					</div>

					<div className="wooya-select-box hidden" data-type="offer">
						<h4>{ __( 'Select setting', 'market-exporter' ) }</h4>

						{ itemAvailable.offer.length > 0 && itemAvailable.offer }
						{ itemAvailable.offer.length === 0 &&
						<h2>
							{ __( 'No available items to select', 'market-exporter' ) }
						</h2>
						}
					</div>

					<Button
						buttonText={ __( 'Add items', 'market-exporter' ) }
						className="wooya-btn wooya-btn-red"
						onClick={ () => this.props.submitData( this.state.selectedItems ) }
						disabled={ this.state.selectedItems.shop.length === 0 && this.state.selectedItems.offer.length === 0 }
					/>
				</div>
			</div>
		);
	}
}

export default AddSettingModal;
