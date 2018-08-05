import React from 'react';

import { __ } from '@wordpress/i18n';

import './style.scss';

/**
 * YML list item component
 *
 * @since 1.0.0
 */
class YmlListItem extends React.Component {
	/**
	 * YmlListItem constructor
	 *
	 * @param props
	 */
	constructor( props ) {
		super( props );
	}

	/**
	 * Render component
	 *
	 * @returns {*}
	 */
	render() {
		return (
			<div className="wooya-yml-item">
				<div className="wooya-yml-item-select">
					<span className="dashicons dashicons-minus me-tooltip-element" onClick={ this.props.onClick } aria-hidden="true" />
					{ __( 'Remove item', 'wooya' ) }
				</div>

				<div className="wooya-yml-item-title">
					<p>
						<strong>{ this.props.name }</strong>
					</p>
					<input type="text" value={ this.props.value } />
				</div>

				<div className="wooya-yml-item-description">
					<p>{ this.props.description }</p>
				</div>
			</div>
		);
	}
}

export default YmlListItem;