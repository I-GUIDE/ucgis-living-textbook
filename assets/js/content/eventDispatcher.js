require('../event/eventTypes');

/**
 * This module generates events from the content side of the application
 */
(function (eDispatch, types) {

  /**
   * Dispatches the given message to the parent window
   * @param type
   * @param data
   */
  function dispatchParent(type, data) {
    console.info('Event dispatched from content', type, data);

    parent.postMessage({
      type: type,
      payload: data
    }, '*');
  }

  /**
   * Send a message to determine if it is executed in a double column environment
   */
  eDispatch.checkForDoubleColumn = function(checksum) {
    dispatchParent(types.CHECK_DOUBLE_COLUMN, {
      checksum: checksum,
    });
  };

  /**
   * Page needs to load event
   */
  eDispatch.pageLoad = function (url, options) {
    options = options || {};

    dispatchParent(types.PAGE_LOAD, {
      url: url,
      options: options
    })
  };

  /**
   * Page loaded event
   */
  eDispatch.pageLoaded = function () {
    // Check current path
    if (typeof currentUrl === 'undefined') {
      currentPath = window.location.pathname;
    }

    dispatchParent(types.PAGE_LOADED, {
      url: currentPath,
      title: document.title
    });
  };

  /**
   * Page submit event
   */
  eDispatch.pageSubmit = function (form) {
    if (form.attr('target') === '_blank') return;

    dispatchParent(types.PAGE_SUBMIT);
  };

  /**
   * Open concept browser event
   */
  eDispatch.toggleConceptBrowser = function () {
    dispatchParent(types.TOGGLE_CONCEPT_BROWSER);
  };

  /**
   * Show the given concept
   * @param id
   */
  eDispatch.showConcept = function (id) {
    dispatchParent(types.SHOW_CONCEPT, {
      id: id
    });
  };

}(window.eDispatch = window.eDispatch || {}, window.eType));
