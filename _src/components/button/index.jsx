import React from 'react';

import './style.scss';

/**
 * Functional button component
 *
 * @since 2.0.0
 *
 * @param {object} props
 * @returns {*}
 * @constructor
 */
function Button(props) {
	return (
		<button className={props.className} onClick={props.onClick} disabled={props.disabled}>
			{props.buttonText}
		</button>
	);
}

export default Button;
