import React from 'react';
import Button from '../button';
import './style.scss';

const {__} = wooyaI18n;

/**
 * Polyfill for IE9+ for closest()
 */
if (!Element.prototype.matches) {
  Element.prototype.matches = Element.prototype.msMatchesSelector ||
    Element.prototype.webkitMatchesSelector;
}

if (!Element.prototype.closest) {
  Element.prototype.closest = function(s) {
    let el = this;

    do {
      if (el.matches(s)) return el;
      el = el.parentElement || el.parentNode;
    } while (el !== null && el.nodeType === 1);
    return null;
  };
}

/**
 * File list control component
 *
 * @since 2.0.0
 */
class Files extends React.Component {
  /**
   * Files constructor
   *
   * @param {object} props
   */
  constructor(props) {
    super(props);

    this.state = {
      selected: [],
    };

    // This binding is necessary to make `this` work in the callback.
    this.selectFile = this.selectFile.bind(this);
    this.removeSelection = this.removeSelection.bind(this);
  }

  /**
   * Select input when clicking on a row.
   *
   * @param {object} e
   */
  selectFile(e) {
    if ( ! e.target.matches('a') ) {
      const row = e.target.closest('.wooya-yml-item');
      const checkbox = row.querySelector('input[type="checkbox"]');
      let selectedItems = this.state.selected;

      if ( 'checkbox' !== e.target.type ) {
        checkbox.checked = !checkbox.checked;
      }

      row.classList.toggle('selected');

      // Item selected. Add to state if not already there.
      if ( true === checkbox.checked && 'undefined' === typeof selectedItems[checkbox.value] ) {
        selectedItems.push(checkbox.value);
      }

      // Item deselected. Remove from state if it is already there.
      if ( false === checkbox.checked ) {
        selectedItems = selectedItems.filter((e) => e !== checkbox.value);
      }

      this.setState({
        selected: selectedItems,
      });
    }
  }

  /**
   * Remove selected files.
   *
   * @param {object} e
   */
  removeSelection(e) {
    e.target.disabled = true;
    const fileList = document.querySelector('.wooya-files-list');
    fileList.classList.add('in-progress');

    this.props.fetchWP.post('files', {files: this.state.selected}).then(
        () => {
          e.target.disabled = false;
          this.props.onDelete();
          fileList.classList.remove('in-progress');
        },
        (err) => this.props.onError(err.message)
    );
  }

  /**
   * Render component
   *
   * @return {*}
   */
  render() {
    if ( 0 === Object.entries(this.props.files).length ) {
      return null;
    }

    const files = Object.entries(this.props.files).map((file) => {
      const url = this.props.path + file[0];
      return (
        <div className="wooya-yml-item" onClick={this.selectFile}>
          <div className="wooya-yml-item-select">
            <input type="checkbox" name="files[]" value={file[0]} />
          </div>
          <div className="wooya-yml-item-title">
            <a href={url} target="_blank">{file[0]}</a>
          </div>
        </div>
      );
    });

    return (
      <div className="wooya-files">
        <h2 className="wooya-files-title">
          {__( 'Available YML files', 'wooya' )}
        </h2>
        <div className="wooya-list-header">
          <div/>
          <Button
            buttonText={__( 'Remove files', 'wooya' )}
            className='wooya-btn wooya-btn-red'
            onClick={this.removeSelection}
            disabled={!this.state.loading && 0 === this.state.selected.length}
          />
        </div>
        <div class="wooya-files-list">
          {files}
        </div>
      </div>
    );
  }
}

export default Files;
