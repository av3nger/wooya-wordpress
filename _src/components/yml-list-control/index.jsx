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
			updateError: false,
			updateMessage: ''
		};
	}

	/**
	 * Handle item move (add/remnove from YML list)
	 *
	 * @param {array}  items
	 * @param {string} action  Accepts: 'add', 'remove'.
	 */
	handleItemMove( items, action = 'add' ) {
		this.props.fetchWP.post( 'settings', { items: items, action: action } ).then(
			( json ) => this.moveItem( items, action ),
			( err )  => this.setState({ updateError: true, updateMessage: err.message, showAddDiv: false })
		);
	}

	/**
	 * Move item in the UI.
	 *
	 * @param {array}  items
	 * @param {string} action
	 */
	moveItem( items, action ) {
		let headerItems = this.props.headerItems.slice();
		let unusedHeaderItems = this.props.unusedHeaderItems.slice();

		if ( 'add' === action ) {
			const index = unusedHeaderItems.indexOf( item );
			headerItems = headerItems.concat( unusedHeaderItems.splice( index, 1 ) );
		} else {
			const index = headerItems.indexOf( item );
			unusedHeaderItems = unusedHeaderItems.concat( headerItems.splice( index, 1 ) );
		}

		this.setState({
			showAddDiv: false,
			headerItems: headerItems, // TODO: we need to update this. move the action to app.js?
			unusedHeaderItems: unusedHeaderItems // TODO: same here
		});
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
					onClick={ () => this.handleItemMove( item, 'remove' ) }
				/>
			);
		} );

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
						buttonText={ __( 'Remove all settings', 'wooya' ) }
						className='wooya-btn wooya-btn-gray'
						disabled='true'
					/>
				</div>

				<div className="wooya-list-content">
					{ this.state.updateError && <Notice type='error' message={ this.state.updateMessage } /> }

					<h3 className="wooya-settings-sub-shop">{ __('Shop', 'wooya' ) }</h3>

					{ items }
				</div>

				{ this.state.showAddDiv &&
					<AddSettingModal
						hideModal={ () => this.setState( { showAddDiv: false } ) }
						shopFields={ this.props.headerFields }
						shopItems={ this.props.unusedHeaderItems }
						submitData={ ( items ) => {
							this.handleItemMove( items, 'add' )
						} }
					/>
				}
			</div>
		);
	}
}

export default YmlListControl;