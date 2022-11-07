export function createDeveloperObjectOnPro() {
    window.EE.pro.refresh = function() {
        let event = new Event('eeprorefresh');
        document.dispatchEvent(event);
    }
}