/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

interface ToggleProps {
    name: string,
    handleToggle: (newValue: boolean) => void,
    on: boolean,
    value: string
    offValue: string,
    onValue: string
}

interface ToggleState {
    on: boolean,
    value: string,
    onOff: 'on' | 'off',
    trueFalse: 'true' | 'false'
}

class Toggle extends React.Component<ToggleProps, ToggleState> {

    constructor(props: ToggleProps) {
        super(props)

        this.state = {
            on: props.on,
            value: props.value,
            onOff: props.on ? 'on' : 'off',
            trueFalse: props.on ? 'true' : 'false'
        }
    }

    handleClick = (event: React.MouseEvent<HTMLElement>) => {
        event.preventDefault()

        this.setState((prevState, props) => {
            if (props.handleToggle) {
                props.handleToggle(!prevState.on)
            }

            return {
                on: !prevState.on,
                value: (!prevState.on) ? props.offValue : props.onValue,
                onOff: !prevState.on ? 'on' : 'off',
                trueFalse: !prevState.on ? 'true' : 'false',
            }
        })
    }

    render() {
        return (
            <button type="button" className={"toggle-btn " + this.state.onOff} onClick={this.handleClick} title={this.state.onOff} data-state={this.state.onOff} aria-checked={this.state.trueFalse} role="switch">
                {this.props.name &&
                    <input type="hidden" name={this.props.name} value={this.state.value} />
                }
                <span className="slider"></span>
            </button>
        )
    }
}

function ToggleTools(props) {
    return (
        <div className="toggle-tools">
            <b>{props.label}</b>
            {props.children}
        </div>
    )
}
