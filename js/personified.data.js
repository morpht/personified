/**
 * @file
 * Initializes all Personified Data blocks.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.personifiedData = {
    attach: function attach(context, settings) {

      if (typeof settings.personifiedData === 'undefined') {
        return;
      }

      $.each(settings.personifiedData, function (id, args) {
        var element = $('#' + id, context);
        if (element.length) {
          var urlParams = {};
          $.each(args.params, function (id, param) {
            var value;
            switch (param.source_type) {
              case 'query':
                value = getQueryParam(param.source_key);
                break;
              case 'cookie':
                value = $.cookie(param.source_key);
                break;
              case 'local_storage':
                value = localStorage.getItem(param.source_key);
                break;
              case 'data_layer':
                value = getDataLayerParam(param.source_key);
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
              element.html(Drupal.jsonTemplate.render(data, args.template));
            },
            error: function (jqXHR, textStatus, errorThrown) {
              console.debug('Personified: JSON data not available for endpoint "' + this.url + '".');
            }
          });
        }
      });

      function getQueryParam(key) {
        var params = window.location.search.substring(1).split('&');
        for (var i = 0; i < params.length; i++) {
          var param = params[i].split('=');
          if (param[0] === key) {
            return param[1] === undefined ? true : decodeURIComponent(param[1]);
          }
        }
      }

      function getDataLayerParam(key) {
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
