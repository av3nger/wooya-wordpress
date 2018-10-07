import React from 'react';
import PerfectScrollbar from 'perfect-scrollbar';

import { __ } from "@wordpress/i18n/build/index";

import Button from '../button';

import './style.scss';

/**
 * Add new setting modal
 *
 * @since 1.0.0
 */
class AddSettingModal extends React.Component {
	/**
	 * Files constructor
	 *
	 * @param props
	 */
	constructor(props) {
		super(props);

		this.state = {
			selectedItems: []
		};
	}

	/**
	 * Init component states
	 */
	componentDidMount() {
		// Scroll box support.
		const container = document.getElementById('wooya-select-box');
		new PerfectScrollbar(container);

		// Hide the modal if clicked outside the modal region.
		const modal = document.querySelector('.wooya-add-setting-modal');
		document.addEventListener('mousedown', e => {
			if ( e.target === modal ) {
				this.props.hideModal();
			}
		});
	}

	/**
	 * Handle click on: Offer/Shop selector.
	 *
	 * @param e
	 */
	toggleType(e) {
		const selector = document.querySelector('.wooya-switch > input[type="checkbox"]');
		selector.checked = 'offer' === e.target.dataset.type;
	}

	/**
	 * Get selected inputs.
	 *
	 * @returns {Array}
	 */
	getSelectedItems() {
		const items = document.querySelectorAll('.wooya-select-box input[type="checkbox"]');

		let selected = [];
		items.forEach(item => {
			if ( item.checked ) {
				selected.push(item.id);
			}
		});

		return selected;
	}

	/**
	 * Render component
	 *
	 * @returns {*}
	 */
	render() {
		// Build the unused items list.
		const itemAvailable = this.props.shopItems.map(item => {
			return (
				<div className="wooya-new-item" data-name={item}>
					<input type="checkbox" id={item} onClick={ () => this.setState({ selectedItems: this.getSelectedItems() }) }/>
					<label for={item}>
						{item}
					</label>

					<p>{ this.props.shopFields[ item ].description }</p>
				</div>
			);
		} );

		return (
			<div className="wooya-add-setting-modal">
				<div className="wooya-modal-content">
					<span className="wooya-close" onClick={this.props.hideModal}>&times;</span>
					<h3>{__( 'Add new setting', 'wooya' )}</h3>

					<span className="wooya-switch-label" data-type="shop" onClick={this.toggleType}>
						{__( 'Shop', 'wooya' )}
					</span>

					<label className="wooya-switch">
						<input type="checkbox" />
						<span className="slider"></span>
					</label>

					<span className="wooya-switch-label" data-type="offer" onClick={this.toggleType}>
						{__( 'Offer', 'wooya' )}
					</span>

					<div className="wooya-select-box" id="wooya-select-box">
						<h4>{__( 'Select setting', 'wooya' )}</h4>

						{itemAvailable}
					</div>

					<Button
						buttonText={__( 'Add selected items', 'wooya' )}
						className="wooya-btn wooya-btn-red"
						onClick={ () => this.props.submitData( this.state.selectedItems ) }
						disabled={ this.state.selectedItems.length === 0 }
					/>
				</div>
			</div>
		);
	}
}

export default AddSettingModal;