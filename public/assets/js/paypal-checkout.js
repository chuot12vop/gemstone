(function (global) {
  'use strict';

  var instances = null;

  function loadSdk(url) {
    if (typeof paypal !== 'undefined' && typeof paypal.createInstance === 'function') {
      return Promise.resolve();
    }
    if (!url) {
      return Promise.reject(new Error('PayPal Web SDK URL missing'));
    }

    var existing = document.querySelector('script[data-paypal-web-sdk-v6], script[src*="/web-sdk/v6/core"]');
    if (existing) {
      return new Promise(function (resolve, reject) {
        if (typeof paypal !== 'undefined' && typeof paypal.createInstance === 'function') {
          resolve();
          return;
        }
        existing.addEventListener('load', function () { resolve(); }, { once: true });
        existing.addEventListener('error', function () { reject(new Error('PayPal SDK failed to load')); }, { once: true });
      });
    }

    return new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.src = url;
      script.async = true;
      script.dataset.paypalWebSdkV6 = '1';
      script.dataset.sdkIntegrationSource = 'checkout-paypal-v6';
      script.onload = function () { resolve(); };
      script.onerror = function () { reject(new Error('PayPal Web SDK failed to load')); };
      document.head.appendChild(script);
    });
  }

  function createInstance(config) {
    if (!instances) {
      instances = new Map();
    }

    var components = Array.from(new Set(config.components || ['paypal-payments'])).sort();
    var tokenOrClientId = config.clientToken || config.clientId;
    var key = [config.webSdkUrl, tokenOrClientId, components.join('|')].join('::');

    if (!instances.has(key)) {
      instances.set(key, loadSdk(config.webSdkUrl).then(function () {
        if (typeof paypal === 'undefined' || typeof paypal.createInstance !== 'function') {
          throw new Error('PayPal Web SDK v6 is unavailable.');
        }

        var options = { components: components, pageType: 'checkout' };
        if (config.clientToken) {
          options.clientToken = config.clientToken;
        } else {
          options.clientId = config.clientId;
        }
        return paypal.createInstance(options);
      }));
    }

    return instances.get(key);
  }

  function isExpectedError(error) {
    if (!error) return false;

    var code = String(error.code || '');
    var name = String(error.name || '');
    var message = String(error.message || '');
    return code === 'ERR_DEV_RECEIVED_CLIENT_ERROR_RESPONSE'
      || name === 'DevError'
      || name === 'SdkInitError'
      || message.indexOf('findEligibleMethods') !== -1
      || message.indexOf('fetching eligible methods') !== -1;
  }

  function logError(label, error) {
    if (!isExpectedError(error)) {
      console.error(label, error);
    }
  }

  function errorMessage(error, fallback) {
    var messages = [];
    var visited = new Set();

    function add(value) {
      var message = String(value || '').trim();
      if (message && messages.indexOf(message) === -1) messages.push(message);
    }

    function inspect(value, depth) {
      if (value === null || value === undefined || depth > 4) return;
      if (typeof value === 'string') {
        var text = value.trim();
        if (!text) return;
        if (text[0] === '{' || text[0] === '[') {
          try {
            inspect(JSON.parse(text), depth + 1);
            return;
          } catch (parseError) {}
        }
        add(text);
        return;
      }
      if (typeof value !== 'object' || visited.has(value)) return;

      visited.add(value);
      ['issue', 'description', 'message'].forEach(function (key) {
        if (typeof value[key] === 'string') add(value[key]);
      });
      ['details', 'data', 'body', 'cause', 'error', 'errors', 'response'].forEach(function (key) {
        if (value[key] !== undefined) inspect(value[key], depth + 1);
      });
      if (Array.isArray(value)) {
        value.forEach(function (item) { inspect(item, depth + 1); });
      }
    }

    inspect(error, 0);
    if (!messages.length) return fallback;

    var code = String(error && (error.code || error.name) || '').trim();
    return 'PayPal' + (code ? ' (' + code + ')' : '') + ': ' + messages.join(' — ');
  }

  global.PayPalCheckout = Object.freeze({
    createInstance: createInstance,
    errorMessage: errorMessage,
    logError: logError,
  });
})(globalThis);
