
(function ($) {
    "use strict";

    var input = $('.validate-input .input100');

    $('.validate-form').on('submit',function(e){
        e.preventDefault();
        var check = true;

        for(var i=0; i<input.length; i++) {
            if(validate(input[i]) == false){
                showValidate(input[i]);
                check=false;
            }
        }

        if(check) {
            submitEmail();
        }
    });


    $('.validate-form .input100').each(function(){
        $(this).focus(function(){
           hideValidate(this);
        });
    });

    function validate (input) {
        if($(input).attr('type') == 'email' || $(input).attr('name') == 'email') {
            if($(input).val().trim().match(/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{1,5}|[0-9]{1,3})(\]?)$/) == null) {
                return false;
            }
        }
        else {
            if($(input).val().trim() == ''){
                return false;
            }
        }
    }

    function showValidate(input) {
        var thisAlert = $(input).parent();

        $(thisAlert).addClass('alert-validate');
    }

    function hideValidate(input) {
        var thisAlert = $(input).parent();

        $(thisAlert).removeClass('alert-validate');
    }

    
    

    $('.simpleslide100').each(function(){
        var delay = 7000;
        var speed = 1000;
        var itemSlide = $(this).find('.simpleslide100-item');
        var nowSlide = 0;

        $(itemSlide).hide();
        $(itemSlide[nowSlide]).show();
        nowSlide++;
        if(nowSlide >= itemSlide.length) {nowSlide = 0;}

        setInterval(function(){
            $(itemSlide).fadeOut(speed);
            $(itemSlide[nowSlide]).fadeIn(speed);
            nowSlide++;
            if(nowSlide >= itemSlide.length) {nowSlide = 0;}
        },delay);
    });

    function initKenBurnsSlider() {
        const slides = $('.ken-burns-slide');
        let currentSlide = 0;
        
        if (slides.length === 0) return;
        
        slides.removeClass('active');
        $(slides[currentSlide]).addClass('active');
        
        setInterval(function() {
            $(slides[currentSlide]).removeClass('active');
            currentSlide = (currentSlide + 1) % slides.length;
            $(slides[currentSlide]).addClass('active');
        }, 6000);
    }
    
    function submitEmail() {
        var email = $('input[name="email"]').val();
        var button = $('.validate-form button');
        var originalText = button.html();
        
        button.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.ajax({
            url: 'submit_email.php',
            type: 'POST',
            data: { email: email },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('input[name="email"]').val('');
                    $('.wrap-input100 .bor1 span').text('Thank you! We\'ll be in touch soon.');
                    button.html('<i class="zmdi zmdi-check fs-30 cl1"></i>');
                    
                    setTimeout(function() {
                        button.html(originalText).prop('disabled', false);
                        $('.wrap-input100 .bor1 span').text('Share your email');
                    }, 3000);
                } else {
                    alert(response.message || 'Something went wrong. Please try again.');
                    button.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('Network error. Please try again.');
                button.html(originalText).prop('disabled', false);
            }
        });
    }
    
    $(document).ready(function() {
        initKenBurnsSlider();
    });


})(jQuery);