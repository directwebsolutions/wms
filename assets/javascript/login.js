window.onload = function(){
    if(window.jQuery){
        $("#login").on("submit",function(a){
            a.preventDefault();
            $("#login_message").addClass("information");
            $("#login_message").removeClass("failed");
            $("#login_message").html("Loading..");
            $.ajax({
                url:$(this).attr("action"),
                type:"POST",
                data: $(this).serialize() + "&via_js=" + 1,
                success:function(data){
                    if (data=="reload") {
                        window.location.reload();
                    } else {
                        window.location.replace(data);
                    }
                },
                error:function(request,status,error){
                    $("#login_message").addClass("failed");
                    $("#login_message").removeClass("information");
                    $("#login_message").html(request.responseText);
                }
            });
        });
    }
};