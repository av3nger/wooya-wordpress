import React from 'react';

import { __ } from "@wordpress/i18n/build/index";

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
	constructor( props ) {
		super( props );

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
		// Build the current items list.
		const items = this.props.headerItems.map( item => {
			return (
				<YmlListItem
					name={ item }
					value={ this.props.settings[ item ] }
					description={ this.props.headerFields[ item ]['description'] }
					onBlur={ this.props.handleItemUpdate }
					updateSelection={ this.props.updateSelection }
				/>
			);
		} );

		/**
		 * TODO: hide Add new settings button, if no elements present
		 * TODO: YmlListItem should handleItemUpdate after a couple of seconds after user finished editing
		 */
		return (
			<div className="me-list-group me-list-group-panel" id="me_yml_store">
				<h2 className="wooya-settings-title">{ __( 'Settings', 'wooya' ) }</h2>

				<div className="wooya-list-header">
					<Button
						buttonText={ __( 'Add new setting', 'wooya' ) }
						className='wooya-btn wooya-btn-transparent'
						onClick={ () => this.setState( { showAddDiv: ! this.state.showAddDiv } ) }
						disabled={ this.props.unusedHeaderItems.length === 0 }
					/>

					<Button
						buttonText={ __( 'Remove settings', 'wooya' ) }
						className='wooya-btn wooya-btn-red'
						onClick={ this.props.removeSelection }
						disabled={ 0 === this.props.selectedItems }
					/>
				</div>

				<div className="wooya-list-content">
					{ this.props.error && <Notice type='error' message={ this.props.errorMsg } /> }

					<h3 className="wooya-settings-sub-shop">{ __('Shop', 'wooya' ) }</h3>

					<form id="wooya-settings-form" onKeyUp={this.handleKeyUp}>
					{ items }
					</form>
				</div>

				{ this.state.showAddDiv &&
					<AddSettingModal
						hideModal={ () => this.setState( { showAddDiv: false } ) }
						shopFields={ this.props.headerFields }
						shopItems={ this.props.unusedHeaderItems }
						submitData={ ( items ) => {
							this.setState( { showAddDiv: false } );
							this.props.handleItemMove( items, 'add' );
						} }
					/>
				}
			</div>
		);
	}
}

export default YmlListControl;