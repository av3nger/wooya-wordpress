import React from 'react';
import PerfectScrollbar from 'perfect-scrollbar';

import { __ } from "@wordpress/i18n/build/index";

import './style.scss';

/**
 * Add new setting modal
 *
 * @since 1.0.0
 */
class AddSettingModal extends React.Component {
	/**
	 * Files constructor
	 *
	 * @param props
	 */
	constructor( props ) {
		super( props );
	}

	/**
	 * Init component states
	 */
	componentDidMount() {
		const container = document.getElementById('wooya-select-box');
		new PerfectScrollbar( container );
	}

	/**
	 * Handle click on: Offer/Shop selector.
	 *
	 * @param e
	 */
	toggleType( e ) {
		const selector = document.querySelector('.wooya-switch > input[type="checkbox"]');
		selector.checked = 'offer' === e.target.dataset.type;
	}

	closeModal() {

	}

	/**
	 * Render component
	 *
	 * @returns {*}
	 */
	render() {
		return (
			<div className="wooya-add-setting-modal">
				<div className="wooya-modal-content">
					<span className="wooya-close" onClick={ this.props.onClick }>&times;</span>
					<h3>{ __( 'Add new setting', 'wooya' ) }</h3>

					<span className="wooya-switch-label" data-type="shop" onClick={ this.toggleType }>
						{ __( 'Shop', 'wooya' ) }
					</span>

					<label className="wooya-switch">
						<input type="checkbox" />
						<span className="slider"></span>
					</label>

					<span className="wooya-switch-label" data-type="offer" onClick={ this.toggleType }>
						{ __( 'Offer', 'wooya' ) }
					</span>

					<div className="wooya-select-box" id="wooya-select-box">
						<h4>{ __( 'Select setting', 'wooya' ) }</h4>

						Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin pretium in libero vel laoreet. Duis pharetra orci quis quam malesuada convallis. Vestibulum facilisis arcu arcu, id posuere justo convallis feugiat. Praesent molestie tempus velit laoreet sagittis. Nunc in hendrerit velit, sed varius arcu. Cras ac eros quis sem commodo sagittis. Praesent scelerisque ante ac commodo facilisis.

						Donec mollis dictum sapien sit amet laoreet. Mauris nisl lorem, tempus vitae nulla pulvinar, tincidunt tincidunt lectus. Integer vel nisi massa. Aliquam imperdiet sollicitudin massa, non blandit metus. Nulla et sapien ante. Proin venenatis condimentum ipsum ac feugiat. Proin fermentum vitae nibh in accumsan. Vivamus vel auctor quam. Cras vitae diam quam. In venenatis nibh id nulla ultrices suscipit. Proin ac nisl sed sem ultricies posuere at at ex. Pellentesque dui tellus, imperdiet ac leo nec, consequat pulvinar tortor. Sed pellentesque lectus vitae nisl dapibus, sit amet congue arcu vestibulum. Donec vitae massa id nisi dapibus pulvinar.

						Sed velit urna, dignissim ut nunc at, viverra pulvinar erat. Vestibulum interdum orci quis ante tincidunt egestas. Nam lacus quam, suscipit eu cursus aliquam, imperdiet ornare ante. Donec iaculis tristique facilisis. Praesent sit amet erat commodo, rutrum mauris nec, tristique lorem. Praesent ut mauris ut arcu condimentum fermentum. Vivamus iaculis, urna at volutpat dignissim, quam dui venenatis sem, vitae congue sapien nulla ac ante. Aenean id interdum est. Fusce facilisis rutrum ante, vel volutpat ligula efficitur id. Vestibulum finibus venenatis sem quis posuere. Ut aliquam ac arcu eget bibendum. Cras rhoncus sit amet nisl ac iaculis.

						Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin pretium in libero vel laoreet. Duis pharetra orci quis quam malesuada convallis. Vestibulum facilisis arcu arcu, id posuere justo convallis feugiat. Praesent molestie tempus velit laoreet sagittis. Nunc in hendrerit velit, sed varius arcu. Cras ac eros quis sem commodo sagittis. Praesent scelerisque ante ac commodo facilisis.

						Donec mollis dictum sapien sit amet laoreet. Mauris nisl lorem, tempus vitae nulla pulvinar, tincidunt tincidunt lectus. Integer vel nisi massa. Aliquam imperdiet sollicitudin massa, non blandit metus. Nulla et sapien ante. Proin venenatis condimentum ipsum ac feugiat. Proin fermentum vitae nibh in accumsan. Vivamus vel auctor quam. Cras vitae diam quam. In venenatis nibh id nulla ultrices suscipit. Proin ac nisl sed sem ultricies posuere at at ex. Pellentesque dui tellus, imperdiet ac leo nec, consequat pulvinar tortor. Sed pellentesque lectus vitae nisl dapibus, sit amet congue arcu vestibulum. Donec vitae massa id nisi dapibus pulvinar.

						Sed velit urna, dignissim ut nunc at, viverra pulvinar erat. Vestibulum interdum orci quis ante tincidunt egestas. Nam lacus quam, suscipit eu cursus aliquam, imperdiet ornare ante. Donec iaculis tristique facilisis. Praesent sit amet erat commodo, rutrum mauris nec, tristique lorem. Praesent ut mauris ut arcu condimentum fermentum. Vivamus iaculis, urna at volutpat dignissim, quam dui venenatis sem, vitae congue sapien nulla ac ante. Aenean id interdum est. Fusce facilisis rutrum ante, vel volutpat ligula efficitur id. Vestibulum finibus venenatis sem quis posuere. Ut aliquam ac arcu eget bibendum. Cras rhoncus sit amet nisl ac iaculis.
					</div>
				</div>
			</div>
		);
	}
}

export default AddSettingModal;