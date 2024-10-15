import ControlPanel from '../ControlPanel'

class ChannelFields extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/fields';

        this.selectors = Object.assign(this.selectors, {
            "create_new": '.main-nav__toolbar a.button--action',
            "fields": '.list-group > .list-item',
            "fields_edit": '.list-group > .list-item > a.list-item__content',
            "fields_checkboxes": '.list-group > .list-item input[type="checkbox"]',
        })
    }

    getCopyButtonByFieldName(fieldName) {
        return cy.get('ul.list-group li').then(($listItems) => {
            let foundElement;
            $listItems.each((index, $el) => {
                if ($el.querySelector('.list-item__title').textContent.trim().includes(fieldName)) {
                    foundElement = $el.querySelector('.app-badge');
                    return false; // Break the loop
                }
            });
            if (foundElement) {
                return cy.wrap(foundElement);
            } else {
                throw new Error(`Field with name "${fieldName}" not found`);
            }
        });
    }
}
export default ChannelFields;
