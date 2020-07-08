<div class="box">
    <h1>Channel Layout Tabs:</h1>
    <div class="md-wrap">
        <br>

        <?= form_open($flux->moduleUrl('deleteLayoutComponentByName')) ?>
            <h3>Delete all
                <select name="component">
                    <option value="layouts">layouts</option>
                    <option value="tabs">tabs</option>
                    <option value="fields">fields</option>
                </select>
                named:
                <input style="width: 100px" type="text" name="name">
                <input type="submit">
            </h3>
        </form>


        <ul class="checklist">
            <?php foreach ($tabs as $layout_name => $tabs): ?>
                <button type="button" class="collapsible"><?= $layout_name ?></button>
                <div class="content">
                  <p>
                    <?php foreach ($tabs as $tab_name => $tab_values): ?>
                        <h3>Tab name: <?= $tab_name ?></h3>
                        <h3>Fields:</h3>
                        <code>
                            <?php foreach ($tab_values['fields'] as $field_name): ?>
                                <?= $field_name ?><br>
                            <?php endforeach; ?>
                        </code>
                        <br>
                    <?php endforeach; ?>
                  </p>
                </div>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<br>

<style type="text/css">
    /* Style the button that is used to open and close the collapsible content */
    .collapsible {
      background-color: #eee;
      color: #444;
      cursor: pointer;
      padding: 10px;
      width: 100%;
      border: none;
      text-align: left;
      outline: none;
      font-size: 12px;
    }

    /* Add a background color to the button if it is clicked on (add the .active class with JS), and when you move the mouse over it (hover) */
    .active, .collapsible:hover {
      background-color: #ccc;
    }

    /* Style the collapsible content. Note: hidden by default */
    .content {
      padding: 0 18px;
      display: none;
      overflow: hidden;
      background-color: #f1f1f1;
    }
</style>

<script type="text/javascript">
    var coll = document.getElementsByClassName("collapsible");
    var i;

    for (i = 0; i < coll.length; i++) {
      coll[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var content = this.nextElementSibling;
        if (content.style.display === "block") {
          content.style.display = "none";
        } else {
          content.style.display = "block";
        }
      });
    }
</script>