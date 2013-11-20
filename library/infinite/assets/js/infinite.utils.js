function Timer(callback, delay) {
    var timerId, start, remaining = delay;

    this.pause = function() {
        window.clearTimeout(timerId);
        remaining -= new Date() - start;
    };

    this.resume = function() {
        start = new Date();
        timerId = window.setTimeout(callback, remaining);
    };

    this.resume();
}

RegExp.quote = function(str) {
    return (str+'').replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1");
};

String.prototype.stripTags = function() {
    return this.replace(/(<([^>]+)>)/ig,"");
};

String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

if (typeof JSON === 'undefined') {
    JSON = {};
}

JSON.stringify = JSON.stringify || function (obj) {
    var t = typeof (obj);
    if (t != "object" || obj === null) {
        // simple data type
        if (t == "string") obj = '"'+obj+'"';
        return String(obj);
    }
    else {
        // recurse array or object
        var n, v, json = [], arr = (obj && obj.constructor == Array);
        for (n in obj) {
            v = obj[n]; t = typeof(v);
            if (t == "string") v = '"'+v+'"';
            else if (t == "object" && v !== null) v = JSON.stringify(v);
            json.push((arr ? "" : '"' + n + '":') + String(v));
        }
        return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
    }
};


jQuery.cookie = function (key, value, options) {

    // key and at least value given, set cookie...
    if (arguments.length > 1 && String(value) !== "[object Object]") {
        options = jQuery.extend({}, options);

        if (value === null || value === undefined) {
            options.expires = -1;
        }

        if (typeof options.expires === 'number') {
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }

        value = String(value);

        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? value : encodeURIComponent(value),
            options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }

    // key and possibly options given, get cookie...
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};

jQuery.fn.usableObjectHeight = function(){
    var height = $(this).innerHeight() - parseInt($(this).css('padding-top'), 10) - parseInt($(this).css('padding-bottom'), 10);
    height = height - $(this).find(".header").outerHeight();
    $(this).find(".sorter").each(function() {
        height = height - 20;
    });
    $(this).find(".content").each(function() {
        height = height - parseInt($(this).css('padding-top'), 10) - parseInt($(this).css('padding-bottom'), 10);
    });
    return height -5;
};

jQuery.hashSize = function(hash) { var count = 0; for (var i in hash) count++; return count; };

$.fn.serializeHash = function() {
    var hash = {};
    function stringKey(key, value) {
      var beginBraket = key.lastIndexOf('[');
      if (beginBraket == -1) {
        var hash = {};
        hash[key] = value;
        return hash;
      }
      var newKey = key.substr(0, beginBraket);
      var newValue = {};
      newValue[key.substring(beginBraket + 1, key.length - 1)] = value;
      return stringKey(newKey, newValue);
    }

    $.each(this.serializeArray(), function() {
      $.extend(true, hash, stringKey(this.name, this.value));
    });
    return hash;
};

jQuery.debug = function(message){
    if (!$("body").hasClass('development')) { return; }
    if(console !== undefined){
        console.debug(message);
    }
};

