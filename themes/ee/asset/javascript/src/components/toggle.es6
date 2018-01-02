/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

class Toggle extends React.Component {
  constructor (props) {
    super(props)

    this.state = {
      on: props.on,
      value: props.value,
      onOff: props.on ? 'on' : 'off',
      trueFalse: props.on ? 'true' : 'false',
    }
  }

  handleClick = (event) => {
    event.preventDefault()
    this.setState((prevState, props) => {
      if (props.handleToggle) props.handleToggle( ! prevState.on)
      return {
        on: ! prevState.on,
        value: ( ! prevState.on) ? props.offValue : props.onValue,
        onOff: !prevState.on ? 'on' : 'off',
        trueFalse: !prevState.on ? 'true' : 'false',
      }
    })
  }

  render () {
    return (
      <a href="#" className={"toggle-btn " + this.onOff} onClick={this.handleClick} alt={this.onOff} data-state={this.onOff} aria-checked={this.trueFalse} role="switch">
        {this.props.name &&
          <input type="hidden" name={this.props.name} value={this.state.value} />
        }
        <span className="slider"></span>
        <span className="option"></span>
      </a>
    )
  }
}

function ToggleTools (props) {
  return (
    <div className="toggle-tools">
      <b>{props.label}</b>
      {props.children}
    </div>
  )
}
