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
	constructor(props) {
		super(props);

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
		this.props.updateSelection(e.target.dataset.type, this.props.name, e.target.checked);
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
		let htmlElement = '';

		if ( 'text' === this.props.input['type'] ) {
			htmlElement = <input type="text" name={this.props.name} value={this.state.value} data-type={this.props.type}
								 onChange={this.handleChange} onBlur={this.handleBlur}/>;
		}

		if ( 'select' === this.props.input['type'] ) {
			const options = Object.entries(this.props.input.values).map(item => {
				return <option value={item[0]}>{item[1]}</option>;
			});

			htmlElement = <select name={this.props.name} value={this.state.value} data-type={this.props.type}>
				{options}
			</select>
		}


		return (
			<div className="wooya-yml-item">
				<div className="wooya-yml-item-select">
					<input type="checkbox" id={this.props.name} data-type={this.props.type}
						   onChange={this.handleItemSelect} checked={this.state.selected}/>
				</div>

				<div className="wooya-yml-item-title">
					<label htmlFor={this.props.name}>
						{this.props.name}
					</label>

					{htmlElement}
				</div>

				<div className="wooya-yml-item-description">
					<p>{this.props.input['description']}</p>
				</div>
			</div>
		);
	}
}

export default YmlListItem;