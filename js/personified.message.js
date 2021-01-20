/**
 * @file
 * Initializes all Personified Message blocks.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.personifiedMessage = {
    attach: function attach(context, settings) {

      if (typeof settings.personifiedMessage === 'undefined') {
        return;
      }

      var data = {
        'query': getQueryParams(),
        'cookie': $.cookie(),
        'localstorage': localStorage,
      };

      $.each(settings.personifiedMessage, function (id, args) {
        var element = $('#' + id, context);
        if (element.length) {
          element.html(Drupal.jsonTemplate.plugins[args.transformer](args.template, data));
        }
      });

      function getQueryParams() {
        var result = {};
        var params = window.location.search.substring(1).split('&');
        for (var i = 0; i < params.length; i++) {
          var param = params[i].split('=');
          result[param[0]] = param[1] === undefined ? true : decodeURIComponent(param[1]);
        }
        return result;
      }

    }
  };

})(jQuery, Drupal);
