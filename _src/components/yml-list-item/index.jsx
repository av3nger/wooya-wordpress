import React from 'react';

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

		this.state = {
			value: this.props.value,
			selected: false
		};

		this.handleChange = this.handleChange.bind(this);
		this.handleBlur = this.handleBlur.bind(this);
		this.handleItemSelect = this.handleItemSelect.bind(this);
	}

	/**
	 * Update text value on user input.
	 *
	 * @param e
	 */
	handleChange(e) {
		this.setState({
			value: e.target.value
		});
	}

	/**
	 * Update checked status on user select.
	 *
	 * @param e
	 */
	handleItemSelect(e) {
		this.props.updateSelection(this.props.name, e.target.checked);
		this.setState({
			selected: e.target.checked
		});
	}

	/**
	 * Handle onBlur event.
	 *
	 * Do not send data to the server if nothing has changed.
	 *
	 * @param e
	 */
	handleBlur(e) {
		if ( e.target.value !== this.props.value ) {
			this.props.onBlur(this.props.name, e.target.value);
		}
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
					<input type="checkbox" id={this.props.name} onChange={this.handleItemSelect} checked={this.state.selected} />
				</div>

				<div className="wooya-yml-item-title">
					<label htmlFor={this.props.name}>
						{this.props.name}
					</label>

					<input type="text" name={this.props.name} value={ this.state.value } onChange={ this.handleChange } onBlur={ this.handleBlur } />
				</div>

				<div className="wooya-yml-item-description">
					<p>{ this.props.description }</p>
				</div>
			</div>
		);
	}
}

export default YmlListItem;