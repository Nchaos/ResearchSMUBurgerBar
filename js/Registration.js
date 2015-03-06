function goToIndex(){
    window.location = "index.html";
}

function signUp(event){
    event.preventDefault();
    var password = $("#password").val();
    var cpassword = $("#confirmPassword").val();

    if(password !== cpassword){
        alert("Passwords do not match. Try again.");
        return;
    }
    else{
        $.ajax({
            type: "POST",
            url: "api/createUserAccount",
            data: {
                fName: $("#givenName").val(),
                lName: $("#surname").val(),
                email: $("#email").val(),
                password: $("#password").val(),
                CCprovider: $("#ccProvider").val(),
                CCNumber: $("#ccNumber").val(),
            },
            success:function(json){
                $.ajax({
                    type: "POST",
                    url: "api/loginUser",
                    data: {
                        email: $("#email").val(),
                        password: $("#password").val()
                    },
                    success: function(json){
                       window.location = "index.html";
                    }
            });
                $("#email").val("");
                $("#password").val("");
                $("#surname").val("");
                $("#givenName").val("");
                $("#confirmPassword").val("");
                $("#ccNumber").val("");
                $("#ccProvider #Visa").prop("selected",true);
                $("#signIn").css("display", "none");
                $("#signedIn").css("display", "block");        
        }});
    }
}

function signIn(){
    event.preventDefault();
    $.ajax({
            type: "POST",
            url: "api/Login",
            data: {
                email: $("#email").val(),
                password: $("#password").val()
            },
            success: function(json){
                console.log(json);
                if(json === null){
                    alert("The information entered was not correct. Try Again.");
                }
                else{
                    $("#email").val("");
                    $("#password").val("");

                    window.location = "orderpage.html";

                }

            }
    });
}



function signout(){
    $.ajax({
            type: "POST",
            url: "api/Logout",
            success: function(){
                $("#signIn").css("display", "block");
                $("#signedIn").css("display", "none");
            }});

}

