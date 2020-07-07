class ElementCollection {
    constructor(elements, parent = null) {
        this.elements = elements;
        this.parent = parent;
    }

    find(element) {
        console.log(`Finding ${element}`, { elements: this.elements });

        if (element.includes('.')) {
            let el = this.find(element.split('.').eq(0));

            if (el instanceof ElementCollection) {
                return el.find(element.split('.').slice(1).join('.'))
            }

            return el;
        }

        let el = this.elements[element];

        return (el instanceof ElementCollection) ? el.parent : el;

        // return [
        //     this.parent,
        //     this.elements[element]
        // ].filter(function(el) {
        //     return el != null;
        // }).join(' ');
    }
}
export default ElementCollection;


// "template_groups": this.section(
//     '.sidebar .scroll-wrap ul.folder-list[data-name="template-group"] > li', {
//     "name": 'a[href*="cp/design/manager"]',
//     "edit": '.toolbar .edit a',
//     "remove": '.toolbar .remove a',
// }
// ),