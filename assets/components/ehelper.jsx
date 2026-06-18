// ehelper.jsx — shared helpers for Lendro frontend
(function () {
  const proto = window.location.protocol;
  const host  = window.location.host;
  window.baseUrl     = proto + '//' + host;
  window.apiEndPoint = proto + '//' + host + '/api/v1';

  window.saveStorage = function (key, value) {
    try {
      localStorage.setItem(key, typeof value === 'object' ? JSON.stringify(value) : value);
    } catch (e) {}
  };

  window.getStorage = function (key) {
    try {
      const val = localStorage.getItem(key);
      if (val === null) return null;
      try { return JSON.parse(val); } catch (e) { return val; }
    } catch (e) { return null; }
  };

  window.removeStorage = function (key) {
    try { localStorage.removeItem(key); } catch (e) {}
  };
})();
