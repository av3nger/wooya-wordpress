import React from 'react';

const {__} = wooyaI18n;

import AddSettingModal from '../add-setting-modal';
import Button from '../button';
import YmlListItem from '../yml-list-item';

import './style.scss';

/**
 * YML list control component
 *
 * TODO: YmlListItem should updateSettings after a couple of
 * seconds after user finished editing
 *
 * @since 2.0.0
 */
class YmlListControl extends React.Component {
  /**
   * YmlListControl constructor
   *
   * @param {object} props
   */
  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      showAddDiv: false,
      selected: {
        shop: [],
        offer: [],
        delivery: [],
        misc: [],
      },
    };

    // Bind the this context to the handler function.
    this.handleKeyUp = this.handleKeyUp.bind(this);
    this.updateSettings = this.updateSettings.bind(this);
    this.updateSelection = this.updateSelection.bind(this);
    this.handleItemMove = this.handleItemMove.bind(this);
  }

  /**
   * If Enter is pressed - submit the form.
   *
   * @param {object} e
   */
  handleKeyUp(e) {
    if (13 === e.keyCode) {
      this.updateSettings(e.target.name, e.target.value);
    }
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

    this.props.fetchWP.post('settings', {items: form, action: 'save'}).then(
        () => {
          if ( 'undefined' !== typeof el[0] ) {
            el[0].removeAttribute('disabled');
            el[0].classList.remove('saving');
          }
        },
        (err) => this.props.onError(err.message)
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
   * Handle item move (add/remove from YML list)
   *
   * @param {array}  items
   * @param {string} action  Accepts: 'add', 'remove'.
   */
  handleItemMove(items, action = 'add') {
    this.setState({
      loading: true,
    });

    this.props.fetchWP.post('settings', {items: items, action: action}).then(
        () => this.moveItem(items, action),
        (err) => this.props.onError(err.message)
    );
  }

  /**
   * Move item in the UI.
   *
   * @param {object}  elements
   * @param {string} action
   */
  moveItem(elements, action) {
    const items = this.props.options;
    const unusedItems = this.props.unusedItems;

    Object.entries(elements).forEach((type) => {
      if ( 0 === type[1].length ) {
        return;
      }

      type[1].forEach(( item ) => {
        if ('add' === action) {
          const index = unusedItems[type[0]].indexOf(item);
          const name = unusedItems[type[0]].splice(index, 1);
          const value = this.props.fields[type[0]][name[0]].default;
          items[type[0]] = Object.assign({}, items[type[0]], {[name]: value});
        } else {
          delete items[type[0]][item];
          unusedItems[type[0]].push(item);
        }
      });
    });

    this.props.handleUpdate(items, unusedItems);

    this.setState({
      loading: false,
      selected: {
        shop: [],
        offer: [],
      },
    });
  }

  /**
   * Render component
   *
   * @return {*}
   */
  render() {
    const items = [];

    // Build the current items list.
    Object.entries(this.props.options).forEach((type) => {
      items[type[0]] = Object.entries(type[1]).map((item) => {
        const dependecy = this.props.fields[type[0]][item[0]].depends_on;
        let disabled = false;
        if ( 'undefined' !== typeof dependecy ) {
          disabled = false === this.props.options[type[0]][dependecy];
        }

        return (
          <YmlListItem
            input={this.props.fields[type[0]][item[0]]}
            name={item[0]}
            value={item[1]}
            type={type[0]}
            placeholder={this.props.fields[type[0]][item[0]].placeholder}
            disabled={disabled}
            onBlur={this.updateSettings}
            updateSelection={this.updateSelection}
          />
        );
      } );
    });

    const selectedItems = 0 === this.state.selected.shop.length &&
        0 === this.state.selected.offer.length;

    let classes = 'wooya-list-content';
    if (this.state.loading || 0 === Object.entries(this.props.options).length) {
      classes = 'wooya-list-content in-progress';
    }

    return (
      <div className="me-list-group me-list-group-panel" id="me_yml_store">
        <h2 className="wooya-settings-title">{__( 'Settings', 'wooya' )}</h2>

        {0 < Object.entries(this.props.options).length &&
          <div className="wooya-list-header">
            <Button
              buttonText={__('Add new setting', 'wooya')}
              className='wooya-btn wooya-btn-transparent'
              onClick={() => this.setState({showAddDiv: !this.state.showAddDiv})}
              disabled={this.props.unusedItems.length === 0}
            />

            <Button
              buttonText={__('Remove settings', 'wooya')}
              className='wooya-btn wooya-btn-red'
              onClick={() => this.handleItemMove(this.state.selected, 'remove')}
              disabled={selectedItems}
            />
          </div>
        }

        <div className={classes}>
          <form id="wooya-settings-form" onKeyUp={this.handleKeyUp}>
            {'undefined' !== typeof items.shop && items.shop.length > 0 &&
              <h3 className="wooya-settings-sub-shop">
                {__('Shop', 'wooya')}
              </h3>
            }
            {items.shop}

            {'undefined' !== typeof items.offer && items.offer.length > 0 &&
              <h3 className="wooya-settings-sub-offer">
                {__('Offer', 'wooya')}
              </h3>
            }
            {items.offer}

            {'undefined' !== typeof items.delivery &&
              items.delivery.length > 0 &&
              <h3 className="wooya-settings-sub-shop">
                {__('Delivery options', 'wooya')}
              </h3>
            }
            {items.delivery}

            {'undefined' !== typeof items.misc && items.misc.length > 0 &&
              <h3 className="wooya-settings-sub-offer">
                {__('Misc', 'wooya')}
              </h3>
            }
            {items.misc}
          </form>
        </div>

        {this.state.showAddDiv &&
        <AddSettingModal
          hideModal={ () => this.setState({showAddDiv: false}) }
          fields={this.props.fields}
          items={this.props.unusedItems}
          submitData={(items) => {
            this.setState({showAddDiv: false});
            this.handleItemMove(items, 'add');
          }}
        />
        }
      </div>
    );
  }
}

export default YmlListControl;
