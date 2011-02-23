
$(document).ready(function(){$(".remove_size").click(function(){var a=$(this).attr("size_short_name_").substr(16),b=$(this).parent().parent();alert(a);$.ajax({dataType:"json",data:"id="+a,url:EE.BASE+"&C=content_files&M=delete_dimension",success:function(c){if(c.response==="success"){$.ee_notice(EE.lang.size_deleted,{type:"success",open:true,close_on_click:true});$(b).fadeOut("slow",function(){$(this).remove()})}else $.ee_notice(EE.lang.size_not_deleted,{type:"error",open:true,close_on_click:true})}});
return false})});
