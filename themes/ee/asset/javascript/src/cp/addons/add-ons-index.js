window.onload = function () {
    var Search_Term = document.getElementById("search_term");
    if(Search_Term){
        Search_Term.addEventListener('input', SearchFilter);
    }
    
    var Installed = document.getElementById("installed_sort");
    if(Installed){
        Installed.addEventListener('click', FilterInstalled);
    }
    

    var Uninstalled = document.getElementById("uninstalled_sort");
    if(Uninstalled){
        Uninstalled.addEventListener('click', FilterUninstalled);
    }
    

    var close_banner = document.getElementById("alert_close");
    if(close_banner){
        close_banner.addEventListener('click', hide);
    }
   

    var see_updates = document.getElementById("show_me");
    if(see_updates){
        see_updates.addEventListener('click', FilterUpdate);
    }
    

    var Update = document.getElementById("update_sort");
    if(Update){
        Update.addEventListener('click', FilterUpdate);
    }

    var All = document.getElementById("show_all");
    if(All){
        All.addEventListener('click', ShowAll);
    }


    function ShowAll(){
        var table, name;
        table = document.getElementById("main_Table");
        tr = table.getElementsByTagName("tr");
        name = document.getElementById("current_status");

        for (i = 0; i < tr.length; i++) {
            if(tr[i].className == "app-listing__row" ||tr[i].className == "app-listing__row_h app-listing__row--head"){
            tr[i].style.display = "";
            }else {
                tr[i].style.display = "";
            }
        }
        document.getElementById('installed_sort').className="sidebar__link";
        document.getElementById('update_sort').className="sidebar__link";
        document.getElementById('uninstalled_sort').className="sidebar__link";

        document.getElementById('show_all').className="sidebar__link  active";
       
        return false;
    }
    

    function hide() { 
        document.getElementById("alert_banner").style.display='none'; 
        return false;
    }

    function SearchFilter() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("search_term");
        filter = input.value.toUpperCase();
        table = document.getElementById("main_Table");
        tr = table.getElementsByTagName("tr");
        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName("strong")[0];
            if (td) {
                txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
            }       
        }
    }


    function FilterUpdate(){
        var table, name;
        table = document.getElementById("main_Table");
        tr = table.getElementsByTagName("tr");
        name = document.getElementById("current_status");

        for (i = 0; i < tr.length; i++) {
            if(tr[i].className == "app-listing__row_update"||tr[i].className == "app-listing__row_h app-listing__row--head"){
            tr[i].style.display = "";
            }else {
                tr[i].style.display = "none";
            }
        }
        document.getElementById('installed_sort').className="sidebar__link";
        document.getElementById('show_all').className="sidebar__link";
        document.getElementById('uninstalled_sort').className="sidebar__link";

        document.getElementById('update_sort').className="sidebar__link  active";
        return false;
    }

    function FilterInstalled(){
        var table, name;
        table = document.getElementById("main_Table");
        tr = table.getElementsByTagName("tr");
        name = document.getElementById("current_status");

        for (i = 0; i < tr.length; i++) {
            if(tr[i].className == "app-listing__row" ||tr[i].className == "app-listing__row_h app-listing__row--head"){
            tr[i].style.display = "";
            }else {
                tr[i].style.display = "none";
            }
        }
        document.getElementById('uninstalled_sort').className="sidebar__link";
        document.getElementById('show_all').className="sidebar__link";
        document.getElementById('update_sort').className="sidebar__link";
        
        document.getElementById('installed_sort').className="sidebar__link  active";
        return false;
    }

    function FilterUninstalled(){
        var table, name;
        table = document.getElementById("main_Table");
        tr = table.getElementsByTagName("tr");
        name = document.getElementById("current_status");

        for (i = 0; i < tr.length; i++) {
            if(tr[i].className == "app-listing__row_uninstalled"||tr[i].className == "app-listing__row_h app-listing__row--head"){
            tr[i].style.display = "";
            }else {
                tr[i].style.display = "none";
            }
        }
        document.getElementById('installed_sort').className="sidebar__link";
        document.getElementById('show_all').className="sidebar__link";
        document.getElementById('update_sort').className="sidebar__link";

        document.getElementById('uninstalled_sort').className="sidebar__link  active";
        return false;
    }
}