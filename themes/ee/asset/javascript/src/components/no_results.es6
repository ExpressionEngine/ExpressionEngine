function NoResults (props) {
  return (
    <label className="field-empty" dangerouslySetInnerHTML={{__html: props.text}} />
  )
}
