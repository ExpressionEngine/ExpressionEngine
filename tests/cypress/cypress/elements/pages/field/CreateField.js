import ControlPanel from '../ControlPanel'

class CreateField extends ControlPanel {
    constructor() {
            super()
            
            this.url = 'admin.php?/cp/fields/create';
            this.elements({
                "Type" : '#fieldset-field_type',
                "Type_Options" : "div[class= 'select__dropdown-item']", 
                "Save": 'button[type="submit"][value="save"]',

                "Name" : 'input[type="text"][name = "field_label"]',
                "Instructions" : 'textarea[name = "field_instructions"]',

                "Required" : 'button[data-toggle-for = "field_required"]',
                "Search" : 'button[data-toggle-for = "field_search"]',
                "Hidden" : 'button[data-toggle-for = "field_is_hidden"]',

                //Field Options
                "MinRows" : 'input[name = "grid_min_rows"]',
                "MaxRows" : 'input[name = "grid_max_rows"]',
                "Reorder" : 'button[data-toggle-for = "allow_reorder"]',

                //Grid Fields
                "NewCol" : 'a[rel= "add_new"]',
                "ColType" : 'label[class= "select__button-label act"]',

                "GridName" : 'input[name = "grid[cols][new_0][col_label]"]',
                "GridInstruction" : 'textarea[name = "grid[cols][new_0][col_instructions]"]',
                "GridRequired" : 'button[data-toggle-for = "grid[cols][new_0][col_required]"]'
            })
        }
    
    prepareForFieldTest(name) {
        cy.authVisit('admin.php?/cp/fields')
        cy.get('.main-nav__title > h1').contains('Field')
        cy.get('.main-nav__toolbar > .button').contains('New Field')
        cy.get('.filter-bar').should('exist')
    
        cy.visit('admin.php?/cp/fields/create')
        cy.get('[data-input-value=field_type] .select__button').click()
        this.get('Type_Options').contains(name).click()
        let title = 'AA ' + name + ' Test'
        this.get('Name').type(title)
    
        cy.hasNoErrors()
        this.get('Save').eq(0).click()
        cy.get('p').contains('has been created')
    
        cy.visit('admin.php?/cp/design/group/create')
        cy.get('input[name="group_name"]').eq(0).type('aa' + name.replace(' ', ''))
        cy.get('[value="Save Template Group"]').eq(0).click()
        cy.get('p').contains('has been created')
    
        cy.log('Creates a Channel to work in')
        cy.visit('admin.php?/cp/channels')
        cy.get('.list-item__content:contains(AATestChannel)').first().click()
        cy.get('button').contains('Fields').click()
        cy.get('div').contains('AA ' + name + ' Test').click()
        cy.get('button').contains('Save').eq(0).click()
    }
}
export default CreateField;
