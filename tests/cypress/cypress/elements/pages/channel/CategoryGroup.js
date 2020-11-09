import ControlPanel from '../ControlPanel'

class CategoryGroup extends ControlPanel {
    constructor() {
        super()
        this.url = 'admin.php?/cp/categories';

        this.selectors = Object.assign(this.selectors, {
            "table_list": '.tbl-list-wrap',
            "category_groups": '.folder-list li',
            "group_names": '.folder-list > li > a',
        })
    }

    groupNames() {
        return cy.task('db:query', 'SELECT group_name as name FROM exp_category_groups ORDER BY group_name ASC').then(function([rows, fields]) {
            return rows;
        })
    }

    groupNamesWithCatCount() {
        return cy.task('db:query', [
            'SELECT group_name as name, count(exp_categories.cat_id) as count',
            'FROM exp_category_groups',
            'LEFT JOIN exp_categories ON exp_categories.group_id = exp_category_groups.group_id',
            'GROUP BY group_name, exp_category_groups.group_id',
            'ORDER BY exp_category_groups.group_name ASC'
        ].join("\n")).then(function([rows, fields]) {
            return rows;
        });
    }

}
export default CategoryGroup;