/* global ajaxurl */

/**
 * External dependencies
 */
import { fetch } from 'whatwg-fetch';

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
			if ( this.options.restActive ) {
				this[ method ] = this._setupRestAPI( method );
			} else {
				this[ method ] = this._setupAjaxAPI( method );
			}
		} );
	}

	/**
	 * Setup REST API endpoints.
	 *
	 * @param {string} method
	 * @return {function(*=, *=): *} Response.
	 * @private
	 */
	_setupRestAPI( method ) {
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

	/**
	 * Setup AJAX endpoints.
	 *
	 * @param {string} method
	 * @return {function(*=, *=): *} Response.
	 * @private
	 */
	_setupAjaxAPI( method ) {
		if ( 'get' === method ) {
			method = 'post';
		}

		return ( endpoint = '/', data = false ) => {
			const fetchObject = {
				credentials: 'same-origin',
				method,
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
				},
				body: 'action=me_' + endpoint + '&_wpnonce=' + this.options.restNonce + '&data=' + JSON.stringify( data ),
			};

			return fetch( ajaxurl, fetchObject )
				.then( ( response ) => {
					return response.json().then( ( json ) => {
						return response.ok ? json.data : Promise.reject( json.data );
					} );
				} );
		};
	}
}

export default FetchWP;
