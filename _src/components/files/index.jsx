import React from 'react';

const { __ } = wooya_i18n;

import './style.scss';

/**
 * File list control component
 *
 * @since 2.0.0
 */
class Files extends React.Component {
	/**
	 * Files constructor
	 *
	 * @param props
	 */
	constructor(props) {
		super(props);
	}

	/**
	 * Render component
	 *
	 * @returns {*}
	 */
	render() {
		return (
			<div className="wooya-files">
				<h2 className="wooya-files-title">{__( 'Available YML files', 'wooya' )}</h2>
			</div>
		);
	}
}

export default Files;
