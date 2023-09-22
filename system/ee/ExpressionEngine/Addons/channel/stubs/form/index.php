<html>
    <head>
        <title>Create entry in <?=$channel_title?></title>
        {!-- https://docs.expressionengine.com/latest/channels/channel-form/overview.html --}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style type="text/css">
/* Common styles */
.ee-cform .element-wrapper {
    margin: 0;
    padding: 0;
    margin-bottom: 20px;
    border: 0;
    min-width: 0;
    font-family: -apple-system, BlinkMacSystemFont, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;
  }
  
  .ee-cform .element-wrapper fieldset {
    border:none;
    padding: 0;
  }
  
  .ee-cform .element-label {
    display: block;
    max-width: 100%;
    font-weight: 600;
    color: #0d0d19;
    margin-bottom: 10px;
    font-size: 1rem;
  }

  .ee-cform .element-wrapper textarea,
  .ee-cform .element-wrapper input[type=text],
  .ee-cform .element-wrapper input[type=email],
  .ee-cform .element-wrapper input[type=number],
  .ee-cform .element-wrapper input[type=password],
  .ee-cform .element-wrapper input[type=url],
  .ee-cform .element-wrapper input[type=search],
  .ee-cform .element-wrapper input[type=date] {
    display: block;
    width: 100% !important;
    padding: 8px 15px !important;
    font-size: 1rem !important;
    line-height: 1.6 !important;
    color: #0d0d19 !important;
    background-color: #fff !important;
    background-image: none !important;
    transition: border-color 200ms ease, box-shadow 200ms ease !important;
    -webkit-appearance: none !important;
    border: 1px solid #cbcbda !important;
    border-radius: 5px !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
  }

    .ee-cform .element-wrapper textarea:focus,
    .ee-cform .element-wrapper input[type=text]:focus,
    .ee-cform .element-wrapper input[type=email]:focus,
    .ee-cform .element-wrapper input[type=number]:focus,
    .ee-cform .element-wrapper input[type=password]:focus,
    .ee-cform .element-wrapper input[type=url]:focus,
    .ee-cform .element-wrapper input[type=search]:focus,
    .ee-cform .element-wrapper input[type=date]:focus {
      border-color: #5d63f1 !important;
      outline: none !important;
      box-shadow: 0 0 0 2px #bbbdf9 !important;
    }

    .ee-cform textarea {
      height: 210px;
    }

/* END Common styles */

/*GRID field*/
  .ee-cform .element-wrapper .grid-field {
    width: 100%;
  }

  .ee-cform .element-wrapper .grid-field .table-responsive {
    box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
    border: 1px solid #dfe0ef;
    overflow-x: auto;
    display: block;
    width: 100%;
  }

  .ee-cform .element-wrapper .grid-field .table-responsive table {
    margin-bottom: 10px;
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    border: none;
    background: transparent;
    box-shadow: none;
    margin-bottom: 0;
  }

  .ee-cform .element-wrapper .grid-field__table th {
    font-size: 0.9em;
    background: #f7f7fb;
    border-right: 1px solid #dfe0ef;
    white-space: nowrap;
    text-align: left;
    border-bottom: 1px solid #dfe0ef;
    color: #8f90b0;
    font-weight: 500;
  }
  .ee-cform .element-wrapper .grid-field__table td:first-child, .grid-field__table th:first-child {
    padding-left: 15px;
  }
  .ee-cform .element-wrapper .grid-field__table td:last-child, .grid-field__table th:last-child {
    padding-right: 15px;
    border-right: none;
    min-width: 80px;
  }
  .ee-cform .element-wrapper .grid-field__table .no-results, .grid-field__table .field-no-results {
    background: none;
    border: none;
    width: 100%;
    text-align: center;
    line-height: 1.6;
    border-radius: 5px;
  }

  .ee-cform .element-wrapper .grid-field__table tr.no-results td {
    border-bottom: none;
  }

  .ee-cform .element-wrapper .no-results a {
    -webkit-appearance: none;
    display: inline-block;
    font-weight: 500;
    text-align: center;
    vertical-align: middle;
    touch-action: manipulation;
    background-image: none;
    cursor: pointer;
    border: 1px solid transparent;
    white-space: nowrap;
    -webkit-transition: background-color 0.15s ease-in-out;
    -moz-transition: background-color 0.15s ease-in-out;
    -o-transition: background-color 0.15s ease-in-out;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
    font-size: 1rem;
      color: #5D63F1;
    background-color: #e0e2fc;
    border-color: #e0e2fc;
    font-weight: normal;
    text-decoration: none;
    padding: 5px 15px !important;
    font-size: 0.8rem;
    line-height: 1.5;
    border-radius: 4px;
    margin-left: 5px;
  }

  .ee-cform .element-wrapper .no-results a:hover {
    color: #5D63F1;
    background-color: #cecffb;
    border-color: #cecffb;
  }

  .ee-cform .element-wrapper .grid-field__footer {
    margin-top: 10px;
  }

  .ee-cform .element-wrapper .grid-field__footer .button-group {
    display: flex;
    flex-wrap: wrap;
    position: relative; 
  }

  .ee-cform .element-wrapper .grid-field__footer .button-group > .button {
    margin-left: 0;
    padding: 5px 15px !important;
    font-size: 0.8rem;
    line-height: 1.5;
    border-radius: 4px;
    color: #0d0d19;
    background: #fff;
    font-weight: normal;
    text-transform: none;
    -webkit-appearance: none;
    display: inline-block;
    font-weight: 500;
    text-align: center;
    vertical-align: middle;
    touch-action: manipulation;
    cursor: pointer;
    border: 1px solid #cbcbda;
    white-space: nowrap;
    transition: background-color 0.15s ease-in-out;
    user-select: none;
    box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
  }

  .ee-cform .element-wrapper .grid-field__footer .button-group > .button:hover {
    background: #f7f7fb;
  }

  .ee-cform .element-wrapper .grid-field__table tbody td {
    vertical-align: top;
    border-right: 1px solid #dfe0ef;
    border-bottom: 1px solid #dfe0ef;
    min-width: 250px;
    max-width: 600px;
  }

  .ee-cform .element-wrapper .grid-field__column--tools {
    vertical-align: top;
    font-size: 1rem;
    padding-left: 8px;
    padding-right: 8px !important;
  }

  .ee-cform .element-wrapper .grid-field__column--tools .button {
    margin-bottom: 5px;
    cursor: move;
    padding: 5px 15px !important;
    font-size: 0.8rem;
    line-height: 1.5;
    border-radius: 4px;
    color: #0d0d19;
    background: #fff;
    font-weight: normal;
      text-transform: none;
    -webkit-appearance: none;
    display: inline-block;
    font-weight: 500;
    text-align: center;
    vertical-align: middle;
    touch-action: manipulation;
    cursor: pointer;
    border: 1px solid #cbcbda;
    white-space: nowrap;
    transition: background-color 0.15s ease-in-out;
    user-select: none;
    box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);
  }

  .ee-cform .element-wrapper .grid-field__column--tools .button i {
    text-transform: none;
    font-style: normal;
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
  }

  .ee-cform .button.cursor-move span i:before {
    content: '\f0b2' !important;
  }
/*END GRID field*/

/*-- RTE field --*/
.ee-cform .ck.ck-toolbar {
  background: #fff !important;
}

.ee-cform .ck.ck-button.ck-on, .ee-cform a.ck.ck-button.ck-on {
  background: #ecedf5 !important
}

.ee-cform .redactor-air a:hover, .ee-cform .redactor-toolbar a:hover {
  outline: none;
  background: #5D63F1;
  color: #ffffff;
}

.ee-cform .redactor-air a:hover i, .ee-cform .redactor-toolbar a:hover i {
  color: inherit
}

.ee-cform .redactor-source-view,
.ee-cform .redactor-source-view.redactor-styles-on {
  border-color: #000 !important;
}

.ee-cform .element-wrapper .redactor-source-view .redactor-toolbar a {
  color: #fff !important;
  opacity: 0.5 !important;
}

.ee-cform .element-wrapper .redactor-source-view .redactor-toolbar a i{
  color: inherit;
}

.ee-cform .element-wrapper .redactor-source,
.ee-cform .element-wrapper .redactor-source:focus,
.ee-cform .element-wrapper .redactor-source:hover {
  background: #252525 !important;
  color: #ccc !important;
  border: none !important;
  border-radius: 0 !important;
}
/* END RTE field */

/* SELECT field */
  .ee-cform .element-wrapper select {
  color: #0d0d19;
  background-color: #fff;
  font-weight: normal;
  -webkit-appearance: none;
  display: inline-block;
  font-weight: 500;
  text-align: center;
  vertical-align: middle;
  touch-action: manipulation;
  background-image: none;
  cursor: pointer;
  border: 1px solid #cbcbda;
  white-space: nowrap;
  -webkit-transition: background-color 0.15s ease-in-out;
  -moz-transition: background-color 0.15s ease-in-out;
  -o-transition: background-color 0.15s ease-in-out;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  padding: 8px 20px !important;
  font-size: 1rem;
  line-height: 1.6;
  border-radius: 5px;
  }
/* END SELECT field */

/* Selectable buttons field */
.ee-cform .selectable_buttons {
  position: relative;
  display: inline-block;
  vertical-align: middle;
}
.ee-cform .selectable_buttons .button{
  position: relative;
  float: left;
}

.ee-cform .selectable_buttons .button:hover,
.ee-cform .selectable_buttons .button:focus,
.ee-cform .selectable_buttons .button:active,
.ee-cform .selectable_buttons .button.active{
  z-index: 2;
}

.ee-cform .selectable_buttons {
  display: flex;
  flex-wrap:wrap;
}

.ee-cform .selectable_buttons .button + .button,
.ee-cform .selectable_buttons .button + .button-group,
.ee-cform .selectable_buttons .selectable_buttons + .button,
.ee-cform .selectable_buttons .selectable_buttons + .selectable_buttons {
  margin-left: 1px;
}

.ee-cform .selectable_buttons .button.button--default + .button.button--default,
.ee-cform .selectable_buttons .button.button--default + .button-group,
.ee-cform .selectable_buttons .selectable_buttons + .button.button--default,
.ee-cform .selectable_buttons .selectable_buttons + .selectable_buttons {
  margin-left: -1px;
}

.ee-cform .selectable_buttons > .button:not(:first-of-type):not(:last-of-type):not(.dropdown-toggle) {
  border-radius: 0;
}

.ee-cform .selectable_buttons > .button:first-of-type {
  margin-left: 0;
  border-bottom-right-radius: 0;
  border-top-right-radius: 0;

}

.ee-cform .selectable_buttons > .button:last-of-type:not(:first-of-type) {
  border-bottom-left-radius: 0;
  border-top-left-radius: 0;
}

.ee-cform .selectable_buttons label{
  max-width: 100%;
  margin-bottom: 5px;
}

.ee-cform .selectable_buttons .button {
  -webkit-appearance: none;
  display: inline-block;
  font-weight: 500;
  text-align: center;
  vertical-align: middle;
  touch-action: manipulation;
  background-image: none;
  cursor: pointer;
  border: 1px solid transparent;
  white-space: nowrap;
  -webkit-transition: background-color 0.15s ease-in-out;
  -moz-transition: background-color 0.15s ease-in-out;
  -o-transition: background-color 0.15s ease-in-out;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  box-shadow: 0 1px 2px 0 var(--ee-shadow-button);
  padding: 8px 20px !important;
  font-size: 1rem;
  line-height: 1.6;
  border-radius: 5px;
}

.ee-cform .selectable_buttons > .button--default {
  color: #0d0d19;
  background-color: #fff;
  border-color: #cbcbda;
  font-weight: normal;
}

.ee-cform .selectable_buttons > .button--default:hover {
  color: #0d0d19;
  background-color: #f7f7fb;
  border-color: #cbcbda;
  text-decoration: none;
}

.selectable_buttons .button.active {
  background: #eaebfd;
}
/* END Selectable buttons field */

/* Note field */
.ee-cform .element-wrapper .note-fieldtype {
  color: #8f90b0;
  background: #f7f7fb;
  border-radius: 5px;
  padding: 14px 20px 16px 55px;
  margin-bottom: -5px;
  position: relative;
  overflow: hidden;
}

.ee-cform .element-wrapper .note-fieldtype .note-fieldtype__icon {
  display: block;
  background: #ecedf5;
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  width: 35px;
}

.ee-cform .element-wrapper .note-fieldtype .note-fieldtype__icon i {
  margin: 0;
  margin-left: 11px;
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  color: #8f90b0;
  opacity: 0.6;
  font-family: "Font Awesome 6 Free";
  font-style: normal;
  font-weight: 900;
}

.ee-cform .element-wrapper .note-fieldtype .note-fieldtype__content {
  font-size: 90%;
}

.ee-cform .element-wrapper .note-fieldtype .note-fieldtype__content p {
  color: inherit;
}
/* END Note field */

/* CHECKBOX field */
.ee-cform .checkboxes-wrapper label {
  display: block;
  position: relative;
  padding: 5px;
  padding-left: 25px;
  cursor: pointer;
  transition: background 100ms ease;
  color: #0d0d19;
  border-radius: 5px;
  font-weight: 400;
  font-family: -apple-system, BlinkMacSystemFont, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;
}

.ee-cform .checkboxes-wrapper label input[type="checkbox"] {
  background-color: #f7f7fb;
  transition: all 100ms ease;
  width: 15px;
  height: 15px;
  margin: 0;
  padding: 0;
  -webkit-appearance: none;
  -moz-appearance: none;
  cursor: pointer;
  border: 1px solid #cbcbda;
  border-radius: 3px;
  z-index: 1;
  position: absolute;
  top: 7px;
  left: 0px;
}

.ee-cform .checkboxes-wrapper label input[type=checkbox]:checked {
  background: #5D63F1;
  border-color: #5D63F1;
}

.ee-cform .checkboxes-wrapper label input[type=checkbox]:after {
  transition: all 100ms ease;
  display: block;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  opacity: 0;
  font-size: 12px;
  margin: 0 !important;
  color: #fff !important;
  content: "\f00c";
  font-family: "Font Awesome 6 Free";
  font-weight: 600;
}

.ee-cform .checkboxes-wrapper label input[type=checkbox]:checked:after {
  opacity: 1;
}

/* END CHECKBOX field */

/* RADIO BUTTONS field*/
.ee-cform .radio-btn-wrap label {
  display: block;
  margin-bottom: 0;
  position: relative;
  font-weight: normal;
  max-width: 100%;
  position: relative;
  padding: 5px;
  padding-left: 25px;
  cursor: pointer;
  transition: background 100ms ease;
  color: #0d0d19;
  border-radius: 5px;
}

.ee-cform .radio-btn-wrap label input[type='radio'] {
    z-index: 1;
    position: absolute;
    top: 7px;
    left: 0px;
    transition: all 100ms ease;
    width: 15px;
    height: 15px;
    margin: 0;
    padding: 0;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
    border: 1px solid #cbcbda;
    border-radius: 50%;
    font-family: inherit;
    font-size: 100%;
    line-height: 1.15;
}

.ee-cform .radio-btn-wrap label input[type=radio]:after {
    transition: all 100ms ease;
    content: "";
    display: block;
    position: absolute;
    width: 5px;
    height: 5px;
    left: 4px;
    top: 4px;
    border-radius: 50%;
    background: #fff;
    transform: rotate(45deg) scale(0);
    opacity: 0;
}

.ee-cform .radio-btn-wrap label input[type=radio]:checked {
    background: #5d63f1;
    border-color: #5d63f1;
}

.ee-cform .radio-btn-wrap label input[type=radio]:checked:after {
    opacity: 1;
    transform: rotate(45deg) scale(1);
}
/* END RADIO BUTTONS field*/

/* Toggle field */
.ee-cform .toggle-btn {
  -webkit-appearance: none;
  appearance: none;
  cursor: pointer;
  position: relative;
  display: inline-block;
  height: 26px;
  width: 48px !important;
  transition: all 100ms ease;
  border-radius: 13px;
  background: #cbcbda;
  text-decoration: none;
  border: 1px solid transparent;
}

.ee-cform .toggle-btn .slider {
  position: absolute;
  display: block;
  left: 2px;
  top: 2px;
  width: 20px;
  height: 20px;
  transition: all 200ms ease;
  box-shadow: 0 2px 5px rgba(120, 119, 140, 0.077);
  border-radius: 50%;
  background: #fff;
}

.ee-cform .toggle-btn.on {
  background: #5D63F1;
}

.ee-cform .toggle-btn.on .slider {
  transform: translateX(22px);
  background: #fff;
}

.ee-cform .toggle-btn.disabled {
  cursor: not-allowed;
  opacity: 0.5;
}


.ee-cform .toggle-btn:focus {
  outline: none;
  border: 1px solid  #5D63F1;
  box-shadow: none;
}

.ee-cform .toggle-btn.off:focus .slider {
  width: 18px;
  height: 18px;
  left: 3px;
  top: 3px;
}


.ee-cform .toggle-tools {
  margin-top: 1em;
}

.ee-cform .toggle-tools b {
  vertical-align: middle;
  font-weight: 500;
}

.ee-cform .toggle-tools button {
  vertical-align: middle;
  margin-left: 0.5em;
}
/* END Toggle field */
</style>
    </head>
    <body>
        <div>
            <h1>Create entry in <?=$channel_title?></h1>
            {exp:channel:form channel="<?=$channel?>"}
                <fieldset>
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" value="{title}" size="50" maxlength="200" onkeyup="liveUrlTitle(event);">
                </fieldset>

                <fieldset>
                    <label for="url_title">URL Title</label>
                    <input type="text" name="url_title" id="url_title" value="{url_title}" maxlength="<?=URL_TITLE_MAX_LENGTH?>" size="50">
                </fieldset>

                <?php foreach ($fields as $field) : ?>
                    {!-- Fieldtype: <?=$field['field_type']?> --}
                    {!-- Docs: <?=$field['docs_url']?> --}
                    <fieldset class="element-wrapper <?=$field['field_type']?>-wrap">
                        <label for="<?=$field['field_name']?>" class="element-label"><?=$field['field_label']?></label>
                        <?=$this->embed($field['stub'], $field);?>
                    </fieldset>
                <?php endforeach; ?>
                {pagination}
            {/exp:channel:form}
        </div>
    </body>
</html>