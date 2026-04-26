$(document).ready(function () {
  'use strict';

  var $document = $(document);
  var $window = $(window);

  $('.dropdown-menu').on('click', 'a.dropdown-toggle', function () {
    var $element = $(this);
    var $parent = $element.offsetParent('.dropdown-menu');

    if (!$element.next().hasClass('show')) {
      $element.parents('.dropdown-menu').first().find('.show').removeClass('show');
    }

    $element.next('.dropdown-menu').toggleClass('show');
    $element.parent('li').toggleClass('show');

    $element.parents('li.nav-item.dropdown.show').on('hidden.bs.dropdown', function () {
      $('.dropdown-menu .show').removeClass('show');
    });

    if (!$parent.parent().hasClass('navbar-nav')) {
      $element.next().css({
        top: $element[0].offsetTop,
        left: $parent.outerWidth() - 4
      });
    }

    return false;
  });

  if ($window.width() < 1199) {
    $document.on('click', function (event) {
      var clickover = $(event.target);
      var isOpened = $('#navbarSupportedContent').hasClass('show');

      if (isOpened && !(clickover.is('.navbar-nav li, .navbar-nav .dropdown *'))) {
        $('button.navbar-toggler').trigger('click');
      }
    });
  }

  $window.on('scroll', function () {
    if ($window.scrollTop() >= 1) {
      $('.bc-nav').addClass('fixed');
    } else {
      $('.bc-nav').removeClass('fixed');
    }
  });

  $('.nav-tabs').on('click', '.nav-link', function () {
    var targetId = $(this).data('attr');
    $('.event-nav').removeAttr('id').attr('id', targetId);
  });

  if ($.fn.owlCarousel) {
    $('.banner-dark-wrap').owlCarousel({
      loop: true,
      items: 1,
      autoplay: true,
      dots: false,
      nav: true,
      navText: ['<img src="images/leftArr.png" alt="" />', '<img src="images/leftArr.png" alt="" />'],
      mouseDrag: false,
      autoplayTimeout: 3000,
      autoplayHoverPause: true,
      smartSpeed: 500
    });

    $('.clients').owlCarousel({
      loop: true,
      autoplay: true,
      margin: 20,
      dots: false,
      nav: false,
      responsive: {
        0: { items: 2 },
        480: { items: 3 },
        992: { items: 4 },
        1200: { items: 6 }
      }
    });

    $('.post-slider').owlCarousel({
      loop: true,
      items: 1,
      autoplay: true,
      dots: false,
      nav: true,
      navText: ['<i class="fas fa-angle-left"></i>', '<i class="fas fa-angle-right"></i>'],
      mouseDrag: false,
      autoplayTimeout: 2000,
      smartSpeed: 500
    });
  }

  if ($.fn.countdown) {
    $('.banner-countdown').each(function () {
      var endTime = $(this).data('time');

      $(this).countdown(endTime, function (timer) {
        var countText = '';
        countText += '<span class="section_count"><span class="section_count_data"><span class="count-data"><span class="tcount days">%D </span><span class="text">Days</span></span></span></span>';
        countText += '<span class="section_count"><span class="section_count_data"><span class="count-data"><span class="tcount hours">%H</span><span class="text">Hours</span></span></span></span>';
        countText += '<span class="section_count"><span class="section_count_data"><span class="count-data"><span class="tcount minutes">%M</span><span class="text">Mins</span></span></span></span>';
        countText += '<span class="section_count"><span class="section_count_data"><span class="count-data"><span class="tcount seconds">%S</span><span class="text">Secs</span></span></span></span>';
        $(this).html(timer.strftime(countText));
      });
    });
  }

  if ($.fn.magnificPopup) {
    $('.club-gallery, .club-gallery-2').each(function () {
      $(this).magnificPopup({
        delegate: 'a',
        type: 'image',
        gallery: {
          enabled: true
        }
      });
    });

    $('.banner-play-button a').magnificPopup({
      type: 'iframe',
      gallery: {
        enabled: true
      }
    });

    $.extend(true, $.magnificPopup.defaults, {
      iframe: {
        patterns: {
          youtube: {
            index: 'youtube.com/',
            id: 'v=',
            src: 'https://www.youtube.com/embed/%id%?autoplay=1'
          }
        }
      }
    });
  }

  $('.bubble-bg').on('mousemove', function (event) {
    var moveX = (($window.width() / 2) - event.screenX) * 0.02;
    var moveY = (($window.height() / 2) - event.screenY) * 0.02;

    $('.bubble-img').css('transform', 'translate(' + (moveX / 5) + 'px, ' + (moveY / 5) + 'px)');
  });

  $('.plClose').on('click', function (event) {
    var clickover = $(event.target);
    var isOpened = $('.pl-container').hasClass('h-show');

    if (isOpened && !(clickover.is('.pl-ul'))) {
      $('.ap__controls--playlist').trigger('click');
    }
  });

  if ($.fn.ajaxChimp) {
    $('.newsletter-form').ajaxChimp({
      callback: mailchimpResponse,
      url: 'YourLinkWillBeHere'
    });
  }

  function mailchimpResponse(response) {
    if (response.result === 'success') {
      $('.newsletter-success').html(response.msg).fadeIn().delay(3000).fadeOut();
    } else if (response.result === 'error') {
      $('.newsletter-error').html(response.msg).fadeIn().delay(3000).fadeOut();
    }
  }
});
