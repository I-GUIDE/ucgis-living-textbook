require('../css/content/content.scss');

let inDoubleColumn = false;
const inDoubleColumnChecksum = Math.random().toString(36);

export function setDoubleColumnDetected(checksum) {
  if (checksum === inDoubleColumnChecksum) {
    console.info("DoubleColumn context detected!");
    $('#no-browser-warning').slideUp();
    inDoubleColumn = true;
  }
}

function findParent(tag, el) {
  while (el) {
    if ((el.nodeName || el.tagName).toLowerCase() === tag.toLowerCase()) {
      return el;
    }
    el = el.parentNode;
  }
  return null;
}

$(function () {
  require('./content/eventHandler');
  require('./content/eventDispatcher');

  eDispatch.checkForDoubleColumn(inDoubleColumnChecksum);

  // Load tooltips
  $('[data-toggle="tooltip"]').tooltip({trigger: "hover"});

  // Bind to all links
  $(document.body).on('click', function (e) {
    // Disable this behavior if the browser has not been found
    if (!inDoubleColumn) return;

    e = e || event;

    var from = findParent('a', e.target || e.srcElement);
    if (from) {
      var $from = $(from);

      // Exclude 'no-link' class from handler
      if ($from.hasClass('no-block')) return;

      // Exclude hash urls
      var url = $from.attr('href');
      if (url.startsWith('#')) return;

      // Exclude javascript urls
      if (url.startsWith('javascript')) return;

      // Build options
      var options = {
        topLevel: $from.hasClass('top-level')
      };

      // Load the new page
      e.preventDefault();
      eDispatch.pageLoad(url, options);
    }
  });

  // Disable submit buttons on submit
  let $form = $('form');
  $form.submit(function () {
    if ($(this).attr('target') === '_blank') return;
    $(this).find(':input[type=submit]')
        .addClass('disabled')
        .on('click', function (e) {
          e.preventDefault();
          return false;
        });
  });

  // Auto focus first form field
  $form.find('input').first().focus();

  // Page loaded event
  eDispatch.pageLoaded();

  // Check in double column has been detected (after timeout)
  setTimeout(() => {
    if (!inDoubleColumn) {
      $('#no-browser-warning').slideDown();
    }
  }, 5000)
});
