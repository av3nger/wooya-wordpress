import React from 'react';

import './style.scss';

/**
 * Capitalize first letter of message.
 *
 * @param {string} string
 * @return {string}
 */
function capitalizeFirstLetter( string ) {
  return string.charAt(0).toUpperCase() + string.slice(1);
}

/**
 * Functional error component
 *
 * @since 1.1.0
 *
 * @param {object} props
 * @return {*}
 * @constructor
 */
function Notice( props ) {
  const classNames = 'wooya-notice wooya-' + props.type;

  return (
    <div className={ classNames }>
      <p>{ capitalizeFirstLetter( props.type ) }: { props.message }</p>
    </div>
  );
}

export default Notice;
