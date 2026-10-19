/// <reference types="Cypress" />

const fieldTypes = ['grid', 'file_grid']
const deletionActions = ['delete-entry', 'delete-fluid']
const fixtureCommand = 'php support/fixtures/fluidGridDeletion.php'

// Read the stored records independently of the fixture and the cleanup implementation.
function snapshot(entryId, fixture) {
    const queries = {
        entries: `SELECT * FROM exp_channel_titles WHERE entry_id = ${entryId}`,
        items: `SELECT * FROM exp_fluid_field_data WHERE entry_id = ${entryId} ORDER BY id`,
        values: `SELECT * FROM exp_channel_data_field_${fixture.gridId} WHERE entry_id = ${entryId} ORDER BY id`,
        rows: `SELECT * FROM exp_channel_grid_field_${fixture.gridId} WHERE entry_id = ${entryId} ORDER BY row_id`,
        relationships: `SELECT * FROM exp_relationships WHERE parent_id = ${entryId}
            AND grid_field_id = ${fixture.gridId} ORDER BY relationship_id`
    }
    const records = {}

    Object.entries(queries).forEach(([name, sql]) => {
        cy.task('db:query', sql).then(([rows]) => { records[name] = rows })
    })

    return cy.then(() => records)
}

context('Fluid Grid deletion storage cleanup', () => {
    beforeEach(() => {
        // Reset for every test and retry, including generated field tables.
        cy.task('db:seed')
    })

    fieldTypes.forEach(fieldType => {
        deletionActions.forEach(action => {
            it(`${action} cleans ${fieldType} rows and relationships without touching another entry`, () => {
                cy.exec(`${fixtureCommand} setup ${fieldType}`).then(({ stdout }) => {
                    const fixture = JSON.parse(stdout)
                    let survivorBefore
                    let childBefore

                    snapshot(fixture.deletedId, fixture).then(before => {
                        expect(before.entries).to.have.length(1)
                        expect(before.items).to.have.length(2)
                        expect(before.values).to.have.length(2)
                        expect(before.rows).to.have.length(4)
                        expect(before.relationships).to.have.length(4)
                        expect(new Set(before.rows.map(row => row.fluid_field_data_id)).size).to.equal(2)
                    })
                    snapshot(fixture.survivorId, fixture).then(before => {
                        expect(before.entries).to.have.length(1)
                        expect(before.items).to.have.length(2)
                        expect(before.values).to.have.length(2)
                        expect(before.rows).to.have.length(4)
                        expect(before.relationships).to.have.length(4)
                        survivorBefore = before
                    })
                    snapshot(fixture.childId, fixture).then(before => { childBefore = before })

                    cy.exec(`${fixtureCommand} ${action} ${fixture.deletedId}`).its('stdout').should('be.empty')

                    snapshot(fixture.deletedId, fixture).then(after => {
                        expect(after.entries).to.have.length(action === 'delete-entry' ? 0 : 1)
                        expect(after.items, 'deleted Fluid items').to.be.empty
                        expect(after.values, 'deleted Fluid values').to.be.empty
                        expect(after.rows, 'deleted Grid rows').to.be.empty
                        expect(after.relationships, 'deleted nested relationships').to.be.empty
                    })
                    snapshot(fixture.survivorId, fixture).then(after => {
                        expect(after, 'other entry and all its field data').to.deep.equal(survivorBefore)
                    })
                    snapshot(fixture.childId, fixture).then(after => {
                        expect(after, 'related child entry').to.deep.equal(childBefore)
                    })
                })
            })
        })
    })
})
