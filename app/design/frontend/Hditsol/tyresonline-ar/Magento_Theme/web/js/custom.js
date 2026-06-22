

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
});


$(window).scroll(function(){
  if ($(window).scrollTop() >= 150) {
    $('header').addClass('fixed');
   }
   else {
    $('header').removeClass('fixed');
   }
});


$(document).ready(function(){
  "use strict";
  var progressPath = document.querySelector('.progress-wrap path');
  var pathLength = progressPath.getTotalLength();
  progressPath.style.transition = progressPath.style.WebkitTransition = 'none';
  progressPath.style.strokeDasharray = pathLength + ' ' + pathLength;
  progressPath.style.strokeDashoffset = pathLength;
  progressPath.getBoundingClientRect();
  progressPath.style.transition = progressPath.style.WebkitTransition = 'stroke-dashoffset 10ms linear';    
  var updateProgress = function () {
    var scroll = $(window).scrollTop();
    var height = $(document).height() - $(window).height();
    var progress = pathLength - (scroll * pathLength / height);
    progressPath.style.strokeDashoffset = progress;
  }
  updateProgress();
  $(window).scroll(updateProgress); 
  var offset = 50;
  var duration = 0;
  jQuery(window).on('scroll', function() {
    if (jQuery(this).scrollTop() > offset) {
      jQuery('.progress-wrap').addClass('active-progress');
    } else {
      jQuery('.progress-wrap').removeClass('active-progress');
    }
  });       
  jQuery('.progress-wrap').on('click', function(event) {
    event.preventDefault();
    jQuery('html, body').animate({scrollTop: 0}, duration);
    return false;
  })
});


$('.menu-toggle a').click(function() {
   $(this).toggleClass('active');
   $('.nav-fullscreen').toggleClass('open');
   $('html').toggleClass('scroll-overflow');
   //$('body').toggleClass('scroll-padding');
});


$(document).ready(function(){
  "use strict";
    var $nav = $(".main-navbar nav"),
    $slideLine = $(".slide-bg"),
    $currentItem = $(".active");

$(function(){  
  // Menu has active item
   if ($currentItem[0]) {
    $slideLine.css({
      "width": $currentItem.width(),
      "left": $currentItem.position().left
    });
  }
  
  // Underline transition
  $($nav).find("li").hover(
    // Hover on
    function(){
      $slideLine.css({
        "width": $(this).width(),
        "left": $(this).position().left,
      });
    },
    // Hover out
    function(){
      if ($currentItem[0]) {
        // Go back to current
        $slideLine.css({
          //"width": $currentItem.width(),
          //"left": $currentItem.position().left,
        });
      } else {
        // Disapear
        $slideLine.width(0);
      }
    }
   );
});
});


$('.izimodal').iziModal({
  overlayColor: 'rgba(255, 255, 255, 0.9)',
  bodyOverflow: true,
  width: false,
  background: '#D70000',
  radius: false,
  transitionIn: false,
  transitionOut: false,
  transitionInOverlay: false,
  transitionOutOverlay: false
});


$(document).ready(function() {
    $('.select2').select2({
      dropdownCssClass: "dropdown-style1",
    });
});

$(document).ready(function() {
  $('.select2-nosearch').select2({
    dropdownCssClass: "dropdown-style2",
    minimumResultsForSearch: -1
  });
});

$(document).ready(function() {
  $('.select2-qty').select2({
    dropdownCssClass: "dropdown-style2 text-center",
    minimumResultsForSearch: -1
  });
});

$('.offer-slider').owlCarousel({
    items:1,
    loop:true,
    margin:0,
    lazyLoad:true,
    autoplay: true,
    nav:true,
    navText: [
      '<svg aria-hidden="true" focusable="false" data-prefix="fal" data-icon="chevron-left" class="svg-inline--fa fa-chevron-left fa-w-8" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><path fill="currentColor" d="M238.475 475.535l7.071-7.07c4.686-4.686 4.686-12.284 0-16.971L50.053 256 245.546 60.506c4.686-4.686 4.686-12.284 0-16.971l-7.071-7.07c-4.686-4.686-12.284-4.686-16.97 0L10.454 247.515c-4.686 4.686-4.686 12.284 0 16.971l211.051 211.05c4.686 4.686 12.284 4.686 16.97-.001z"></path></svg>',
      '<svg aria-hidden="true" focusable="false" data-prefix="fal" data-icon="chevron-right" class="svg-inline--fa fa-chevron-right fa-w-8" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512"><path fill="currentColor" d="M17.525 36.465l-7.071 7.07c-4.686 4.686-4.686 12.284 0 16.971L205.947 256 10.454 451.494c-4.686 4.686-4.686 12.284 0 16.971l7.071 7.07c4.686 4.686 12.284 4.686 16.97 0l211.051-211.05c4.686-4.686 4.686-12.284 0-16.971L34.495 36.465c-4.686-4.687-12.284-4.687-16.97 0z"></path></svg>'
    ],
    dots:true,
    responsive:{
        991.99:{
            dots:false
        },
      }
});

$('.blog-list-slider').owlCarousel({
    loop:true,
    lazyLoad: true,
    margin:25,
    nav:true,
    dots:false,
    navText: [
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36.557 52.555"> <g id="Arrow_2_1_copy" data-name="Arrow 2 1 copy" transform="translate(-166.293 -346.008)"> <path d="M 202.85009765625 398.56298828125 L 191.0196990966797 398.56298828125 L 166.2926940917969 372.2865905761719 L 191.0196990966797 346.0083923339844 L 202.85009765625 346.0083923339844 L 178.1248016357422 372.2865905761719 L 202.85009765625 398.56298828125 Z" stroke="none"/> <path d="M 202.85009765625 398.56298828125 L 191.0196990966797 398.56298828125 L 166.2926940917969 372.2865905761719 L 191.0196990966797 346.0083923339844 L 202.85009765625 346.0083923339844 L 178.1248016357422 372.2865905761719 L 202.85009765625 398.56298828125 Z M 202.85009765625 398.56298828125 L 178.1248016357422 372.2865905761719 L 202.85009765625 346.0083923339844 L 191.0196990966797 346.0083923339844 L 166.2926940917969 372.2865905761719 L 191.0196990966797 398.56298828125 L 202.85009765625 398.56298828125 Z" stroke="none" fill="#131315"/> </g> </svg>',
      '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36.557 52.555"> <g id="Arrow_2_1" data-name="Arrow 2 1" transform="translate(-1717.15 -346.008)"> <path d="M 1728.980346679688 398.56298828125 L 1717.14990234375 398.56298828125 L 1741.875244140625 372.2865905761719 L 1717.14990234375 346.0083923339844 L 1728.980346679688 346.0083923339844 L 1753.707275390625 372.2865905761719 L 1728.980346679688 398.56298828125 Z" stroke="none"/> <path d="M 1728.980346679688 398.56298828125 L 1717.14990234375 398.56298828125 L 1741.875244140625 372.2865905761719 L 1717.14990234375 346.0083923339844 L 1728.980346679688 346.0083923339844 L 1753.707275390625 372.2865905761719 L 1728.980346679688 398.56298828125 Z M 1728.980346679688 398.56298828125 L 1753.707275390625 372.2865905761719 L 1728.980346679688 346.0083923339844 L 1717.14990234375 346.0083923339844 L 1741.875244140625 372.2865905761719 L 1717.14990234375 398.56298828125 L 1728.980346679688 398.56298828125 Z" stroke="none" fill="#131315"/> </g> </svg>'
    ],
    responsive:{
        0:{
            items:1,
            nav:false,
            dots:true
        },
        479:{
            items:1,
            nav:false,
            dots:true
        },
        575.99:{
            items:2,
            nav:false,
            dots:true
        },
        767.99:{
            items:2,
            nav:false,
            dots:true
        },
        991.99:{
            items:2,
            nav:false,
            dots:true
        },
        1025:{
            items:2,
            nav:false,
            dots:true
        },
        1199.99:{
            items:3
        }
    }
});


$('.services-slider').owlCarousel({
    loop:true,
    margin:30,
    nav:false,
    dots:false,
    center: true,
    autoWidth:true,
    responsive:{
        0:{
            items:2,
        },
        479:{
            items:1,
        },
        575.99:{
            items:2,
        },
        767.99:{
            items:6,
        }
    }
});


$('.product-offer-slider .slider').owlCarousel({
  items:1,
  loop:true,
  margin:0,
  lazyLoad:true,
  autoplay: true,
  autoplayTimeout:1000,
  nav:false,
  dots:false,
  touchDrag:false,
  mouseDrag:false,
  animateOut: 'fadeOut'
});

//window.dispatchEvent(new Event('resize'));

$(window).on("load", function () {
 // window.dispatchEvent(new Event('resize'));
});

document.addEventListener('lazybeforeunveil', function(e){
    var bg = e.target.getAttribute('data-bg');
    if(bg){
        e.target.style.backgroundImage = 'url(' + bg + ')';
    }
});


jQuery(document).ready(function(){
    jQuery('.scrollbar-inner').scrollbar();
    jQuery('.scrollbar-outer').scrollbar();
});


$(window).on("load", function () {
   $('.preloader').fadeOut('10');
   $('body').removeClass('overflow-hidden');
});