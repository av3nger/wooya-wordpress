/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Capitalize first letter of message.
 *
 * @param {string} string
 * @return {string} Message
 */
function capitalizeFirstLetter( string ) {
	return string.charAt( 0 ).toUpperCase() + string.slice( 1 );
}

/**
 * Functional error component
 *
 * @since 1.1.0
 *
 * @param {Object} props
 * @return {*} Notice
 * @constructor
 */
function Notice( props ) {
	const classNames = 'wooya-notice wooya-' + props.type;

	return (
		<div className={ classNames }>
			<strong>{ capitalizeFirstLetter( props.type ) }</strong>
			<p>{ props.message }</p>
			{ props.link &&
			<p><a href={ props.link }>More info</a></p> }
			<span className="wooya-close" onClick={ props.onHide }>&times;</span>
		</div>
	);
}

export default Notice;
