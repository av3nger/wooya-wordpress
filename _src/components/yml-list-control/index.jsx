import React from 'react';

const { __ } = wooya_i18n;

import Button from '../button';
import YmlListItem from '../yml-list-item';
import AddSettingModal from '../add-setting-modal';

import './style.scss';

/**
 * YML list control component
 *
 * @since 1.0.0
 */
class YmlListControl extends React.Component {
	/**
	 * YmlListControl constructor
	 *
	 * @param props
	 */
	constructor(props) {
		super(props);

		this.state = {
			showAddDiv: false,
		};

		this.handleKeyUp = this.handleKeyUp.bind(this);
	}

	/**
	 * If Enter is pressed - submit the form.
	 *
	 * @param e
	 */
	handleKeyUp(e) {
		if (13 === e.keyCode) this.props.handleItemUpdate(e.target.name, e.target.value);
	}

	/**
	 * Render component
	 *
	 * @returns {*}
	 */
	render() {
		let items = [];

		// Build the current items list.
		Object.entries(this.props.options).forEach(type => {
			items[type[0]] = Object.entries(type[1]).map(item => {
//console.log( this.props.fields[type[0]][item[0]] );
				return (
					<YmlListItem
						input={this.props.fields[type[0]][item[0]]}
						name={item[0]}
						value={item[1]}
						type={type[0]}
						onBlur={this.props.handleItemUpdate}
						updateSelection={this.props.updateSelection}
					/>
				);
			} );
		});

		/**
		 * TODO: hide Add new settings button, if no elements present
		 * TODO: YmlListItem should handleItemUpdate after a couple of seconds after user finished editing
		 */
		return (
			<div className="me-list-group me-list-group-panel" id="me_yml_store">
				<h2 className="wooya-settings-title">{__( 'Settings', 'wooya' )}</h2>

				<div className="wooya-list-header">
					<Button
						buttonText={__( 'Add new setting', 'wooya' )}
						className='wooya-btn wooya-btn-transparent'
						onClick={ () => this.setState({ showAddDiv: ! this.state.showAddDiv }) }
						disabled={this.props.unusedItems.length === 0}
					/>

					<Button
						buttonText={__( 'Remove settings', 'wooya' )}
						className='wooya-btn wooya-btn-red'
						onClick={this.props.removeSelection}
						disabled={this.props.selectedItems}
					/>
				</div>

				<div className="wooya-list-content">
					{this.props.error && <Notice type='error' message={this.props.errorMsg} />}

					<form id="wooya-settings-form" onKeyUp={this.handleKeyUp}>
						<h3 className="wooya-settings-sub-shop">{__('Shop', 'wooya' )}</h3>
						{items.shop}

						<h3 className="wooya-settings-sub-offer">{__('Offer', 'wooya' )}</h3>
						{items.offer}
					</form>
				</div>

				{this.state.showAddDiv &&
					<AddSettingModal
						hideModal={ () => this.setState({ showAddDiv: false }) }
						fields={this.props.fields}
						items={this.props.unusedItems}
						submitData={items => {
							this.setState({ showAddDiv: false });
							this.props.handleItemMove(items, 'add');
						}}
					/>
				}
			</div>
		);
	}
}

export default YmlListControl;