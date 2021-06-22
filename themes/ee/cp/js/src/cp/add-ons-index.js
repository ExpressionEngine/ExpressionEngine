window.onload = function () {
    var Search_Term = document.getElementById("Search_Term");
    if(Search_Term){
        Search_Term.addEventListener('input', SearchFilter);
    }
    
    var Installed = document.getElementById("Installed_Sort");
    if(Installed){
        Installed.addEventListener('click', FilterInstalled);
    }
    

    var Uninstalled = document.getElementById("Uninstalled_Sort");
    if(Uninstalled){
        Uninstalled.addEventListener('click', FilterUninstalled);
    }
    

    var close_banner = document.getElementById("alert_close");
    if(close_banner){
        close_banner.addEventListener('click', hide);
    }
   

    var see_updates = document.getElementById("ShowMe");
    if(see_updates){
        see_updates.addEventListener('click', FilterUpdate);
    }
    

    var Update = document.getElementById("Update_Sort");
    if(Update){
        Update.addEventListener('click', FilterUpdate);
    }
    

    function hide() { 
        document.getElementById("alert_banner").style.display='none'; 
        return false;
    }

    function SearchFilter() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("Search_Term");
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
        var table, vr;
        table = document.getElementById("main_Table");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
            if(tr[i].className == "app-listing__row_update"){
            tr[i].style.display = "";
            }else {
                tr[i].style.display = "none";
            }
        }
        return false;
    }

    function FilterInstalled(){
        var table, vr;
        table = document.getElementById("main_Table");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
            if(tr[i].className == "app-listing__row"){
            tr[i].style.display = "";
            }else {
                tr[i].style.display = "none";
            }
        }
        return false;
    }

    function FilterUninstalled(){
        var table, vr;
        table = document.getElementById("main_Table");
        tr = table.getElementsByTagName("tr");

        for (i = 0; i < tr.length; i++) {
            if(tr[i].className == "app-listing__row_uninstalled"){
            tr[i].style.display = "";
            }else {
                tr[i].style.display = "none";
            }
        }
        return false;
    }
}