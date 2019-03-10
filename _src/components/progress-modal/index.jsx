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
      totalSteps: 0,
      currentStep: 0,
      progress: 0,
    };

    if ( 0 === this.state.currentStep ) {
      this.startGenerationProcess();
    } else {
      this.updateGenerationProgress();
    }
  }

  startGenerationProcess() {
    this.props.fetchWP.post('generate').then(
        (response) => {
          console.log(response);
        }
    );
  }

  updateGenerationProgress() {
    this.props.fetchWP.get('generate').then(
        (response) => {
          console.log(response);
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
