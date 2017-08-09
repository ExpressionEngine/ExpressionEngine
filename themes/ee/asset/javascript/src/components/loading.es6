function Loading (props) {
  return (
    <label className="field-loading">
      {(props.text ? props.text : EE.lang.loading)}<span></span>
    </label>
  )
}
