/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * PromoBox component.
 *
 * @since 2.1.0
 */
class PromoBox extends React.Component {
	/**
	 * Render component
	 *
	 * @return {*} Component.
	 */
	render() {
		return (
			<div className="wooya-promo-box">
				<div className="wooya-promo-box-row">
					<div className="wooya-promo-box-column">
						<label htmlFor="id">id</label>
						<input type="text" name="id" id="id" value="Promo10" maxLength="20" required />
					</div>

					<div className="wooya-promo-box-column">
						<label htmlFor="code">promo code</label>
						<input type="text" name="code" id="code" value="HAPPYNEWBENEFIT" required />
					</div>

					<div className="wooya-promo-box-column">
						<label htmlFor="discount">discount</label>
						<input type="number" name="discount" id="discount" value="300" step="50" required />
					</div>

					<div className="wooya-promo-box-column">
						<label htmlFor="unit">unit</label>
						<select name="unit">
							<option value="currency">currency</option>
							<option value="percent">percent</option>
						</select>
					</div>
				</div>

				<div className="wooya-promo-box-row">
					<label htmlFor="description">description</label>
					<textarea name="description" id="description" maxLength="500">Скидка 10% по уникальному промокоду!</textarea>
				</div>

				<div className="wooya-promo-box-row">
					<label htmlFor="url">url</label>
					<input type="text" name="url" id="url" value="http://best.seller.ru/promos/10" maxLength="512" />
				</div>
			</div>
		);
	}
}

export default PromoBox;
