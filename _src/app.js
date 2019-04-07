import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';

import './app.scss';

const {__} = wooyaI18n;

import FetchWP from './utils/fetchWP';

import Button from './components/button';
import Files from './components/files';
import Notice from './components/notice';
import ProgressModal from './components/progress-modal';
import YmlListControl from './components/yml-list-control';

/**
 * Wooya React component
 */
class Wooya extends React.Component {
  /**
   * Wooya constructor
   *
   * @param {object} props
   */
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      options: [], // Plugin options.
      fields: [], // List of available fields.
      unusedItems: [], // Not used fields (available to add via modal).
      selected: {
        shop: [],
        offer: [],
        delivery: [],
        misc: [],
      },
      error: {
        show: false,
        message: '',
        link: '',
      },
      showProgressModal: false,
    };

    // Bind the this context to the handler function.
    this.handleItemMove = this.handleItemMove.bind(this);
    this.updateSettings = this.updateSettings.bind(this);
    this.updateSelection = this.updateSelection.bind(this);

    /**
     * @type {FetchWP}
     * @param {string} this.props.wpObject.api_url
     * @param {string} this.props.wpObject.api_nonce
     */
    this.fetchWP = new FetchWP({
      restURL: this.props.wpObject.api_url,
      restNonce: this.props.wpObject.api_nonce,
    });
  }

  /**
   * Init component states
   */
  componentDidMount() {
    this.fetchWP.get('settings').then(
        (json) => this.getElements(json),
        (err) => this.showError(0, err.message)
    );
  }

  /**
   * Get all the elements and sort out the ones that are in use to a
   * separate variable.
   *
   * @param {json} options
   */
  getElements(options) {
    this.fetchWP.get('elements').then(
        (json) => {
          const unusedItems = {
            shop: [],
            offer: [],
          };

          Object.entries(json).forEach((type) => {
            Object.keys(type[1]).filter((element) => {
              if ( ! ( type[0] in unusedItems ) ) {
                return true;
              }

              if ( 'undefined' === typeof options[type[0]] || ! ( element in options[type[0]] ) ) {
                unusedItems[type[0]].push(element);
                return false;
              }

              return true;
            });
          });

          this.setState({
            loading: false,
            options: options,
            fields: json,
            unusedItems: unusedItems,
          });
        },
        (err) => this.showError(0, err.message)
    );
  }

  /**
   * Move item in the UI.
   *
   * @param {object}  elements
   * @param {string} action
   */
  moveItem(elements, action) {
    const items = this.state.options;
    const unusedItems = this.state.unusedItems;

    Object.entries(elements).forEach((type) => {
      if ( 0 === type[1].length ) {
        return;
      }

      type[1].forEach(( item ) => {
        if ('add' === action) {
          const index = unusedItems[type[0]].indexOf(item);
          const name = unusedItems[type[0]].splice(index, 1);
          const value = this.state.fields[type[0]][name[0]].default;
          items[type[0]] = Object.assign({}, items[type[0]], {[name]: value});
        } else {
          delete items[type[0]][item];
          unusedItems[type[0]].push(item);
        }
      });
    });

    this.setState({
      loading: false,
      options: items,
      unusedItems: unusedItems,
      selected: {
        shop: [],
        offer: [],
      },
    });
  }

  /**
   * Handle item move (add/remove from YML list)
   *
   * @param {array}  items
   * @param {string} action  Accepts: 'add', 'remove'.
   */
  handleItemMove(items, action = 'add') {
    this.setState({
      loading: true,
    });

    this.fetchWP.post('settings', {items: items, action: action}).then(
        () => this.moveItem(items, action),
        (err) => this.showError(0, err.message)
    );
  }

  /**
   * Update the data in the database.
   *
   * TODO: handle checkbox loading status.
   *
   * @param {string} item  Item for which we update the setting.
   * @param {string} value
   */
  updateSettings(item, value) {
    const el = document.getElementsByName(item);
    let type = 'offer'; // Setting type: shop or offer.

    // Multi-select will not not be found.
    if ( 'undefined' !== typeof el[0] ) {
      el[0].setAttribute('disabled', 'disabled');
      el[0].classList.add('saving');
      type = el[0].dataset.type;
    }

    const form = {
      type: type,
      name: item,
      value: value,
    };

    this.fetchWP.post('settings', {items: form, action: 'save'}).then(
        () => {
          if ( 'undefined' !== typeof el[0] ) {
            el[0].removeAttribute('disabled');
            el[0].classList.remove('saving');
          }
        },
        (err) => this.showError(0, err.message)
    );
  }

  /**
   * Update the selection. Will be used later for removing items from the list.
   *
   * @param {string}  type   Option type: shop, offer.
   * @param {string}  item   Item name.
   * @param {boolean} value  Checked value.
   */
  updateSelection(type, item, value) {
    const selectedItems = this.state.selected;

    // We could have combined both checks into a single filter return,
    // but that would not be so readable.

    // Item selected. Add to state if not already there.
    if ( true === value && 'undefined' === typeof selectedItems[type][item] ) {
      selectedItems[type].push(item);
    }

    // Item deselected. Remove from state if it is already there.
    if ( false === value ) {
      selectedItems[type] = selectedItems[type].filter((e) => e !== item);
    }

    this.setState({
      selected: selectedItems,
    });
  }

  /**
   * Display error notice.
   *
   * @param {int} code        Error message code.
   * @param {string} message  Error message text.
   * @param {string} link     Link for more info button in error message.
   */
  showError(code, message = '', link = '') {
    if ( 'undefined' === typeof code ) {
      return;
    }

    // Try to look for error message by code.
    if ( '' === message ) {
      message = ajax_strings.errors['error_' + code];
    }

    if ( '' === link ) {
      link = ajax_strings.errors['link_' + code];
    }

    this.setState({
      loading: false,
      error: {
        show: true,
        message: message,
        link: link,
      },
      showProgressModal: false,
    });
  }

  /**
   * Hide error message.
   */
  hideError() {
    this.setState({
      error: {
        show: false,
        message: '',
        link: '',
      },
    });
  }

  /**
   * Render component
   *
   * @return {*}
   */
  render() {
    /*
    if (this.state.loading && ! this.state.error.show) {
      return (
        <div className="me-main-content">
          <div className="wooya-description">
            <p>{__( 'Loading...', 'wooya' )}</p>
          </div>
        </div>
      );
    }
    */

    const selectedItems = 0 === this.state.selected.shop.length && 0 === this.state.selected.offer.length;

    return (
      <div className="me-main-content">
        {this.state.error.show &&
        <Notice type='error'
          message={this.state.error.message}
          link={this.state.error.link}
          onHide={this.hideError}
        />}

        {this.state.options &&
        <Button
          buttonText={__( 'Generate YML', 'wooya' )}
          className='wooya-btn wooya-btn-red wooya-btn-center'
          onClick={() => this.setState({
            showProgressModal: ! this.state.showProgressModal,
          })}
        /> }

        <Files
          fetchWP={this.fetchWP}
          onError={(error) => this.showError(0, error)}
        />

        <YmlListControl
          options={this.state.options}
          fields={this.state.fields}
          unusedItems={this.state.unusedItems}
          handleItemMove={this.handleItemMove}
          handleItemUpdate={this.updateSettings}
          updateSelection={this.updateSelection}
          removeSelection={
            () => this.handleItemMove(this.state.selected, 'remove')
          }
          selectedItems={selectedItems}
        />

        {this.state.showProgressModal &&
        <ProgressModal
          onFinish={() => {
            Files.updateFileList();
            this.setState({showProgressModal: false});
          }}
          onError={(errorCode) => {
            this.setState({showAddDiv: false});
            this.showError(errorCode);
          }}
          fetchWP={this.fetchWP}
        />
        }
      </div>
    );
  }
}

Wooya.propTypes = {
  wpObject: PropTypes.object,
};

document.addEventListener('DOMContentLoaded', function() {
  ReactDOM.render(
      /** @var {object} window.ajax_strings */
      <Wooya wpObject={window.ajax_strings} />,
      document.getElementById('wooya_components')
  );
});
