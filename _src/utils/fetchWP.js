/**
 * External dependencies
 */
import fetch from 'isomorphic-fetch';

const methods = [
	'get',
	'post',
	'put',
	'delete',
];

/**
 * Class to fetch data from WordPress REST API
 *
 * @since 2.0.0
 */
class FetchWP {
	/**
   * Class constructor
   *
   * @param {Object} options
   */
	constructor( options = {} ) {
		this.options = options;

		/** @var {string} options.restURL */
		if ( ! options.restURL ) {
			throw new Error( 'restURL option is required' );
		}

		/** @var {string} options.restNonce */
		if ( ! options.restNonce ) {
			throw new Error( 'restNonce option is required' );
		}

		methods.forEach( ( method ) => {
			this[ method ] = this._setup( method );
		} );
	}

	/**
     * Setup
     *
     * @param {string} method
     * @return {function(*=, *=): *} Response.
     * @private
     */
	_setup( method ) {
		return ( endpoint = '/', data = false ) => {
			const fetchObject = {
				credentials: 'same-origin',
				method,
				headers: {
					Accept: 'application/json',
					'Content-Type': 'application/json',
					'X-WP-Nonce': this.options.restNonce,
				},
			};

			if ( data ) {
				fetchObject.body = JSON.stringify( data );
			}

			return fetch( this.options.restURL + endpoint, fetchObject )
				.then( ( response ) => {
					return response.json().then( ( json ) => {
						return response.ok ? json : Promise.reject( json );
					} );
				} );
		};
	}
}

export default FetchWP;
