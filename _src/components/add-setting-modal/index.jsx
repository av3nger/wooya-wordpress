import React from 'react';
import PerfectScrollbar from 'perfect-scrollbar';

import { __ } from "@wordpress/i18n/build/index";

import Button from '../button';

import './style.scss';
import YmlListItem from "../yml-list-item";

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
			selectedItems: {
				shop: [],
				offer: []
			}
		};

		this.getSelectedItems = this.getSelectedItems.bind(this);
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

		const showEl = document.querySelector('.wooya-select-box:not(.hidden)');
		const hideEl = document.querySelector('div[data-type="' + e.target.dataset.type + '"]');

		showEl.classList.toggle('hidden');
		hideEl.classList.toggle('hidden');
	}

	/**
	 * Get selected inputs.
	 *
	 * @returns {Array}
	 */
	getSelectedItems() {
		const items = document.querySelectorAll('.wooya-select-box input[type="checkbox"]');

		let selected = {
			shop: [],
			offer: []
		};

		items.forEach(item => {
			if ( item.checked ) {
				selected[item.dataset.type].push(item.id);
			}
		});

		this.setState({
			selectedItems: selected
		});
	}

	/**
	 * Render component
	 *
	 * @returns {*}
	 */
	render() {
		// Build the unused items list.
		let itemAvailable = [];

		// Build the current items list.
		Object.keys(this.props.fields).forEach(field => {
			itemAvailable[field] = Object.entries(this.props.fields[field])
			.filter(item => {
				return this.props.items[field].includes(item[0]);
			})
			.map(item => {
				return (
					<div className="wooya-new-item" data-name={item[0]}>
						<input type="checkbox" name={item[0]} id={item[0]} data-type={field} onClick={this.getSelectedItems}/>
						<label for={item[0]}>
							{item[0]}
						</label>

						<p>{this.props.fields[field][item[0]].description}</p>
					</div>
				);
			});
		});

		return (
			<div className="wooya-add-setting-modal">
				<div className="wooya-modal-content">
					<span className="wooya-close" onClick={this.props.hideModal}>&times;</span>
					<h3>{__('Add new setting', 'wooya')}</h3>

					<span className="wooya-switch-label" data-type="shop" onClick={this.toggleType}>
						{__('Shop', 'wooya')}
					</span>

					<label className="wooya-switch">
						<input type="checkbox" />
						<span className="slider"></span>
					</label>

					<span className="wooya-switch-label" data-type="offer" onClick={this.toggleType}>
						{__( 'Offer', 'wooya' )}
					</span>

					<div className="wooya-select-box" id="wooya-select-box" data-type="shop">
						<h4>{__('Select setting', 'wooya')}</h4>

						{itemAvailable.shop.length > 0 && itemAvailable.shop}
						{itemAvailable.shop.length === 0 &&
						<h2>
							{__('No available items to select', 'wooya')}
						</h2>
						}
					</div>

					<div className="wooya-select-box hidden" id="wooya-select-box" data-type="offer">
						<h4>{__( 'Select setting', 'wooya' )}</h4>

						{itemAvailable.offer.length > 0 && itemAvailable.offer}
						{itemAvailable.offer.length === 0 &&
						<h2>
							{__('No available items to select', 'wooya')}
						</h2>
						}
					</div>

					<Button
						buttonText={__('Add items', 'wooya')}
						className="wooya-btn wooya-btn-red"
						onClick={() => this.props.submitData(this.state.selectedItems)}
						disabled={this.state.selectedItems.shop.length === 0 && this.state.selectedItems.offer.length === 0}
					/>
				</div>
			</div>
		);
	}
}

export default AddSettingModal;