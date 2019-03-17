import React from 'react';

const {__} = wooyaI18n;

import './style.scss';

/**
 * Progress modal.
 *
 * @since 2.0.0
 */
class ProgressModal extends React.Component {
  /**
   * Files constructor
   *
   * @param {object} props
   */
  constructor(props) {
    super(props);

    this.state = {
      currentStep: 0,
      totalSteps: 0,
      progress: 0,
    };

    this.generateYML();
  }

  /**
   * When data is updated, update next batch.
   */
  componentDidUpdate() {
    // TODO: Do not update when finished.
    this.generateYML();
  }

  /**
   * Generate YML.
   */
  generateYML() {
    const args = {
      step: this.state.currentStep,
      steps: this.state.totalSteps,
    };

    this.props.fetchWP.post('generate', args).then(
        (response) => {
          console.log(response);

          if ( 'undefined' !== typeof response.code && 200 !== response.code ) {
            // Display error.
            this.props.onError(response.code);
          } else {
            // TODO: check how this error is displayed.
            if ( 'undefined' === typeof response.step ) {
              this.props.onError(500);
            }

            if ( 'undefined' === typeof response.steps ) {
              this.props.onError(500);
            }

            this.setState({
              currentStep: response.step,
              totalSteps: response.steps,
              progress: 100 / response.steps * response.step,
            });
          }
        },
        (err) => {
          // TODO: properly parse errors.
          console.log(err);
          //if ( 'undefined' !== typeof err.data.status ) {
          //  this.props.onError(err.data.status);
          //}
        }
    );
  }

  /**
   * Render component
   *
   * @return {*}
   */
  render() {
    return (
      <div className="wooya-progress-modal wooya-modal">
        <div className="wooya-modal-content">
          <h3>{__('Generating file', 'wooya')}</h3>

          <div className="progress-bar">
            <div className="progress-filler"
              style={{width: `${this.state.progress}%`}} />
          </div>
          {__('Progress bar status...', 'wooya')}
        </div>
      </div>
    );
  }
}

export default ProgressModal;
