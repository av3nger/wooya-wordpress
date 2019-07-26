/* global wooyaI18n */

/**
 * External dependencies
 */
import React from 'react';
import Select from 'react-select';

const { __ } = wooyaI18n;

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * YML list item component
 *
 * @since 2.0.0
 */
class YmlListItem extends React.Component {
	/**
	 * YmlListItem constructor
	 *
	 * @param {Object} props
	 */
	constructor( props ) {
		super( props );

		this.state = {
			value: this.props.value,
			selected: false,
		};

		this.handleSelectChange = this.handleSelectChange.bind( this );
		this.handleChange = this.handleChange.bind( this );
		this.handleBlur = this.handleBlur.bind( this );
		this.handleItemSelect = this.handleItemSelect.bind( this );
	}

	/**
	 * Handle select change.
	 *
	 * @param {string} selectedOption
	 */
	handleSelectChange( selectedOption ) {
		this.setState( {
			value: selectedOption,
		} );

		this.props.onBlur( this.props.name, selectedOption );
	}

	/**
	 * Update text value on user input.
	 *
	 * @param {Object} e
	 */
	handleChange( e ) {
		let value;

		if ( e.target.type === 'checkbox' ) {
			value = e.target.checked;

			// Disable/enable delivery options settings.
			if ( 'delivery_options' === e.target.id ) {
				document.getElementById( 'cost' )
					.closest( '.wooya-yml-item' )
					.classList.toggle( 'disabled' );
				document.getElementById( 'days' )
					.closest( '.wooya-yml-item' )
					.classList.toggle( 'disabled' );
				document.getElementById( 'order_before' )
					.closest( '.wooya-yml-item' )
					.classList.toggle( 'disabled' );
			}
		} else {
			value = e.target.value;
		}

		this.setState( {
			value,
		} );

		if ( 'select-one' === e.target.type || 'checkbox' === e.target.type ) {
			if ( value !== this.props.value ) {
				this.props.onBlur( this.props.name, value );
			}
		}
	}

	/**
	 * Update checked status on user select.
	 *
	 * @param {Object} e
	 */
	handleItemSelect( e ) {
		this.props.updateSelection(
			e.target.dataset.type,
			this.props.name,
			e.target.checked
		);

		this.setState( {
			selected: e.target.checked,
		} );
	}

	/**
	 * Handle onBlur event.
	 *
	 * Do not send data to the server if nothing has changed.
	 *
	 * @param {Object} e
	 */
	handleBlur( e ) {
		if ( e.target.value !== this.props.value ) {
			this.props.onBlur( this.props.name, e.target.value );
		}
	}

	/**
	 * Render component
	 *
	 * @return {*} Component
	 */
	render() {
		let htmlElement = '';

		if ( 'text' === this.props.input.type ) {
			htmlElement = <input type="text" name={ this.props.name }
				value={ this.state.value } data-type={ this.props.type }
				id={ this.props.name } placeholder={ this.props.placeholder }
				onChange={ this.handleChange } onBlur={ this.handleBlur } />;
		}

		if ( 'textarea' === this.props.input.type ) {
			htmlElement = <textarea name={ this.props.name } data-type={ this.props.type }
				onChange={ this.handleChange } onBlur={ this.handleBlur }>
				{ this.state.value }
			</textarea>;
		}

		if ( 'select' === this.props.input.type ) {
			const options = Object.entries( this.props.input.values ).map( ( item ) => {
				return <option value={ item[ 0 ] }>{ item[ 1 ] }</option>;
			} );

			htmlElement = <select name={ this.props.name } value={ this.state.value }
				data-type={ this.props.type } onChange={ this.handleChange }>
				{ options }
			</select>;
		}

		if ( 'multiselect' === this.props.input.type ) {
			const options = Object.entries( this.props.input.values ).map( ( value ) => {
				return { value: value[ 0 ], label: value[ 1 ] };
			} );

			htmlElement = <Select
				isMulti
				value={ this.state.value }
				onChange={ this.handleSelectChange }
				options={ options }
				className="wooya-select-container"
				classNamePrefix="wooya-select"
			/>;
		}

		if ( 'checkbox' === this.props.input.type ) {
			htmlElement = <div className="wooya-yml-checkbox">
				<span className="wooya-switch-label">{ __( 'Disabled', 'market-exporter' ) }</span>

				<label className="wooya-switch">
					<input id={ this.props.name } type="checkbox" name={ this.props.name }
						checked={ this.state.value } onChange={ this.handleChange }
						data-type={ this.props.type } />
					<span className="slider">&nbsp;</span>
				</label>

				<span className="wooya-switch-label">{ __( 'Active', 'market-exporter' ) }</span>
			</div>;
		}

		let className = 'wooya-yml-item';
		if ( 'undefined' !== typeof this.props.disabled && this.props.disabled ) {
			className = 'wooya-yml-item disabled';
		}

		return (
			<div className={ className }>
				<div className="wooya-yml-item-select">
					<input type="checkbox" id={ this.props.name }
						data-type={ this.props.type } onChange={ this.handleItemSelect }
						checked={ this.state.selected } />
				</div>

				<div className="wooya-yml-item-title">
					<label htmlFor={ this.props.name }>
						{ this.props.name }
					</label>

					{ htmlElement }
				</div>

				<div className="wooya-yml-item-description">
					<p>{ this.props.input.description }</p>
				</div>
			</div>
		);
	}
}

export default YmlListItem;
