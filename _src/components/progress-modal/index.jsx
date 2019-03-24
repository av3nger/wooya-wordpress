import React from 'react';

const {__,sprintf} = wooyaI18n;

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
    if ( this.state.currentStep !== this.state.totalSteps ) {
      this.generateYML();
    } else {
      setTimeout(this.props.onFinish, 2000);
    }
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
          if ( 'undefined' !== typeof response.code && 200 !== response.code ) {
            // Display error.
            this.props.onError(response.code);
          } else {
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
          if ( 'undefined' !== typeof err.data.status ) {
            this.props.onError(err.data.status);
          }
        }
    );
  }

  /**
   * Render component
   *
   * @return {*}
   */
  render() {
    let progressStatus;
    if ( 0 === this.state.currentStep ) {
      progressStatus = __('Initializing generation engine...', 'wooya');
    } else if ( this.state.currentStep === this.state.totalSteps ) {
      progressStatus = __('Finalizing...', 'wooya');
    } else {
      progressStatus = sprintf(
          __('Generating file: step %d of %d', 'wooya'),
          this.state.currentStep,
          this.state.totalSteps
      );
    }

    return (
      <div className="wooya-progress-modal wooya-modal">
        <div className="wooya-modal-content">
          <h3>{__('Generating file', 'wooya')}</h3>

          <div className="progress-bar">
            <div className="progress-filler"
              style={{width: `${this.state.progress}%`}} />
          </div>
          {progressStatus}
        </div>
      </div>
    );
  }
}

export default ProgressModal;
