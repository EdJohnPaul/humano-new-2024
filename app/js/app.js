$(document).ready(function(){
    var App = {
        canvas  : $("#canvas"),
		navCanvas:	$("#navCanvas"),
        sideCanvas : $("#sideCanvas"),
        breadCrumb: $("#breadCrumb"),
        api     : Config.url + "/api",
        path    : Config.url + "/humano",
        token   : localStorage.getItem("Token"),
        username: localStorage.getItem("Username"),
		userType: localStorage.getItem("userType"),
        authenticate: function() {
            if (App.token === 0 || App.token === null && App.username === 0 || App.username === null) {
                window.location.href = "../auth/#/login/";
            }else{
                $.ajax({
                    type: "POST",
                    url: App.api + "/system/tokens/verify",
                    dataType: "json",
                    data: {
                        token: App.token,
						userid: App.username
                    },
                    success: function(data) {
						App.userType = data.type;
                        if (parseInt(data.verified) === 0) {
                            window.location.href = "../auth/#/login/";
                        }
                    }
                });
            }
        },
        initialize: function() {
            App.authenticate();
            $("input[name=user]").val(localStorage.getItem("UserId"));
            $("#username").text(localStorage.getItem("Username"));
            // setInterval(function() {
            //     App.authenticate();
            // }, 60000);
			
			var empUid = App.username;
        },
        // Added functions by Xandra Start
        toggleSidebar : function(){
            $(document).ready(function(){
                $('#toggle-sidebar').on('click', function(){
                    $('#sideCanvas').toggleClass("d-md-none");
                })
            })
        },

        sidebarLink : function(){
            $(document).ready(function() {
                $(".sidebar-link").click(function(event) {
                    $(".sidebar-link").removeClass("active");
                    $(".collapse.show").removeClass("show");
                    
                    $(this).addClass("active");
                    
                    var dropdown = $(this).next();
                    if (dropdown.hasClass("collapse")) {
                        dropdown.toggleClass("show");
                    }
                });
            });
        },

        // Desktop view arrow
        deskArrow : function(){
            $(document).ready(function(){
                $(".sidebar-link.has-dropdown").click(function(){
                    $(".sidebar-link.has-dropdown .fa-solid").removeClass("rotate");
                    
                    $(this).find(".fa-solid").toggleClass("rotate");
                });
            
                $(".sidebar-link:not(.has-dropdown)").click(function(){
                    $(".sidebar-link.has-dropdown .fa-solid").removeClass("rotate");
                    $("#request-arrow").removeClass("rotate");
                });
            });
        },

        // Mobile view arrow
        mobileArrow : function(){
            $(document).ready(function(){
                $(".nav-link.has-dropdown").click(function(){
                    $(".nav-link.has-dropdown .fa-solid").removeClass("rotate");
                    $(this).find(".fa-solid").toggleClass("rotate");
                });
            
                $(".nav-link:not(.has-dropdown)").click(function(){
                    $(".nav-link.has-dropdown .fa-solid").removeClass("rotate");
                    $("#request-arrow").removeClass("rotate");
                });
            });
        }
        // Added functions by Xandra Start
    }

    // $.Mustache.option.warnOnMissingTemplates = true;

    $.Mustache.load('templates/admin.html').done(function(){
        App.toggleSidebar();
        App.sidebarLink();
        App.deskArrow();
        App.mobileArrow();
        App.sideCanvas.html("").append($.Mustache.render("side-nav"));
        App.navCanvas.html("").append($.Mustache.render("admin-nav"));

        
        Path.map('#/dashboard/').to(function(){
            App.canvas.html("").append($.Mustache.render("dash-container"));
        });

        Path.map('#/master-file').to(function(){
            App.canvas.html("").append($.Mustache.render("master"));
        });
        Path.root();
        Path.listen();
    });
});


