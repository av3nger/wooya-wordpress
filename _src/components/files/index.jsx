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
      loading: true,
      files: [],
      path: '',
      selected: [],
    };

    // This binding is necessary to make `this` work in the callback.
    this.selectFile = this.selectFile.bind(this);
    Files.updateFileList = Files.updateFileList.bind(this);
    this.removeSelection = this.removeSelection.bind(this);
  }

  /**
   * Init component states
   */
  componentDidMount() {
    Files.updateFileList();
  }

  /**
   * Update the files list.
   */
  static updateFileList() {
    const fileList = document.querySelector('.wooya-files-list');
    fileList.classList.add('in-progress');

    this.props.fetchWP.get('files').then(
        (response) => {
          fileList.classList.remove('in-progress');
          this.setState({
            loading: false,
            files: response.files,
            path: response.url,
            selected: [],
          });
        }
    );
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
    const buttonState = e.target;
    buttonState.disabled = true;

    this.setState({
      loading: true,
    });

    this.props.fetchWP.post('files', {files: this.state.selected}).then(
        () => {
          Files.updateFileList();
          buttonState.disabled = false;
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
    const files = Object.entries(this.state.files).map((file) => {
      const url = this.state.path + file[0];
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

    let classes = 'wooya-files-list';
    if ( this.state.loading ) {
      classes = 'wooya-files-list in-progress';
    }

    return (
      <div className="wooya-files">
        <h2 className="wooya-files-title">
          {__( 'Available YML files', 'wooya' )}
        </h2>
        {0 < files.length && !this.state.loading &&
          <div className="wooya-list-header">
            <div/>
            <Button
              buttonText={__('Remove files', 'wooya')}
              className='wooya-btn wooya-btn-red'
              onClick={this.removeSelection}
              disabled={0 === this.state.selected.length}
            />
          </div>
        }

        <div className={classes}>
          {0 === files.length && !this.state.loading &&
            <span className="wooya-empty-files">
              {__('You have not yet generated any files', 'wooya')}
            </span>
          }
          {!this.state.loading && 0 < files.length && files}
        </div>
      </div>
    );
  }
}

export default Files;
