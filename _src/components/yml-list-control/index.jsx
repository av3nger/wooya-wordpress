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
			showAddDiv: false
		};
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
					onClick={ () => this.props.handleItemMove( item, 'remove' ) }
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
					{ this.props.error && <Notice type='error' message={ this.props.errorMsg } /> }

					<h3 className="wooya-settings-sub-shop">{ __('Shop', 'wooya' ) }</h3>

					{ items }
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