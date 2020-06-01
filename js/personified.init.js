/**
 * @file
 * Initializes all Personified blocks.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.personified = {
    attach: function attach(context, settings) {

      if (typeof settings.personified === 'undefined') {
        return;
      }

      $.each(settings.personified, function (id, args) {
        var element = $('#' + id, context);
        if (element.length) {
          console.debug('Personified: Initialization of "' + id + '".');

          var urlParams = {};
          $.each(args.params, function (id, param) {
            var value;
            switch (param.source_type) {
              case 'query':
                value = personifiedQueryParam(param.source_key);
                break;
              case 'cookie':
                value = $.cookie(param.source_key);
                break;
              case 'local_storage':
                value = localStorage.getItem(param.source_key);
                break;
              case 'data_layer':
                value = personifiedDataLayerParam(param.source_key);
                break;
              case 'window':
                value = window[param.source_key];
                break;
            }
            if (typeof value !== 'undefined' && value !== null) {
              urlParams[param.endpoint_key] = value;
            }
            else if (param.default_value !== '') {
              urlParams[param.endpoint_key] = param.default_value;
            }
          });

          $.ajax({
            type: 'GET',
            url: args.endpoint,
            data: urlParams,
            success: function (data, textStatus, jqXHR) {
              // Try to parse JSON data if not parsed yet.
              if (typeof data !== 'object') {
                try {
                  data = JSON.parse(data);
                }
                catch (e) {
                  console.debug('Personified: Unable to parse JSON data for endpoint "' + this.url + '".');
                  return;
                }
              }
              var template = settings.personifiedTemplate[args.template];
              var compiled = Handlebars.compile(template);
              element.html(compiled(data));
            },
            error: function (jqXHR, textStatus, errorThrown) {
              console.debug('Personified: JSON data not available for endpoint "' + this.url + '".');
            }
          });
        }
      });

      function personifiedQueryParam(key) {
        var params = window.location.search.substring(1).split('&');
        for (var i = 0; i < params.length; i++) {
          var param = params[i].split('=');
          if (param[0] === key) {
            return param[1] === undefined ? true : decodeURIComponent(param[1]);
          }
        }
      }

      function personifiedDataLayerParam(key) {
        if (typeof window.google_tag_manager !== 'undefined') {
          var gtm = window.google_tag_manager;
          for (var name in gtm) {
            if (gtm.hasOwnProperty(name) && typeof gtm[name] === 'object') {
              if (typeof gtm[name].dataLayer !== 'undefined') {
                return gtm[name].dataLayer.get(key);
              }
            }
          }
        }
      }

    }
  };

})(jQuery, Drupal);
