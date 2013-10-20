
XMLHttpRequest.prototype.uniqueID = function( ) {
    if (!this.uniqueIDMemo) {
        this.uniqueIDMemo = Math.floor(Math.random( ) * 1000);
    }
    return this.uniqueIDMemo;
}

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

function isStringEmpty(mixed_var){
    var key;
    if (mixed_var === "" ||
        mixed_var === 0 ||
        mixed_var === "0" ||
        mixed_var === null ||        mixed_var === false ||
        typeof mixed_var === 'undefined'
        ){
            return true;
    }
    if (typeof mixed_var == 'object') {
        for (key in mixed_var) {
            return false;
        }
        return true;
    }
    return false;
}

RegExp.quote = function(str) {
    return (str+'').replace(/([.?*+^$[\]\\(){}|-])/g, "\\$1");
};

function urldecode (str) {
  return decodeURIComponent((str + '').replace(/\+/g, '%20'));
}


String.prototype.stripTags = function() {
    return this.replace(/(<([^>]+)>)/ig,"");
};

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





/* Date formatting v2.0
 *
 * This work is licensed under a Creative Commons Attribution 3.0 Unported License
 * http://creativecommons.org/licenses/by/3.0/
 *
 * Author: Andy Harrison, http://dragonzreef.com/
 * Date: 16 September 2011
 */

Date.prototype.getMonthName = function(){ return (["January","February","March","April","May","June","July","August","September","October","November","December"])[this.getMonth()]; }
Date.prototype.getUTCMonthName = function(){ return (["January","February","March","April","May","June","July","August","September","October","November","December"])[this.getUTCMonth()]; }
Date.prototype.getDayName = function(){ return (["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"])[this.getDay()]; }
Date.prototype.getUTCDayName = function(){ return (["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"])[this.getUTCDay()]; }

//if useUTC is true, UTC values will be used instead of local time values
Date.prototype.format = function(formatStr, useUTC)
{
    /*
    Format string variables:

    %Y  4-digit year (e.g., 2011)
    %y  2-digit year (e.g., 11)
    %M  2-digit month (01-12)
    %m  month (1-12)
    %B  full month name (January-December)
    %b  abbreviated month name (Jan-Dec)
    %D  2-digit day of month (01-31)
    %d  day of month (1-31)
    %o  ordinal of the day of month (st, nd, rd, th)
    %W  full weekday name (Sunday-Saturday)
    %w  abbreviated weekday name (Sun-Sat)
    %I  hour in 24-hour format (00-23)
    %H  2-digit hour in 12-hour format (01-12)
    %h  hour in 12-hour format (1-12)
    %P  AM/PM
    %p  am/pm
    %q  a/p
    %N  2-digit minute (00-59)
    %n  minute (0-59)
    %S  2-digit second (00-59)
    %s  second (0-59)
    %Z  3-digit milliseconds (000-999)
    %z  milliseconds (0-999)
    %e  UTC offset +/-
    %F  2-digit hour offset (00-23)
    %f  hour offset (0-23)
    %G  2-digit minute offset (00-59)
    %g  minute offset (0-59)

    %%  percent sign
    */

    function pad(numStr, digits)
    {
        numStr = numStr.toString();
        while(numStr.length < digits) numStr = "0"+numStr;
        return numStr;
    }

    var theYear = useUTC ? this.getUTCFullYear() : this.getFullYear();
    var theMonth = useUTC ? this.getUTCMonth() : this.getMonth();
    var theMonthName = useUTC ? this.getUTCMonthName() : this.getMonthName();
    var theDate = useUTC ? this.getUTCDate() : this.getDate();
    var theDayName = useUTC ? this.getUTCDayName() : this.getDayName();
    var theHour = useUTC ? this.getUTCHours() : this.getHours();
    var theMinute = useUTC ? this.getUTCMinutes() : this.getMinutes();
    var theSecond = useUTC ? this.getUTCSeconds() : this.getSeconds();
    var theMS = useUTC ? this.getUTCMilliseconds() : this.getMilliseconds();
    var theOffset = useUTC ? 0 : -this.getTimezoneOffset(); //offset in minutes

    var v = /%(.)/, m, formatted = "", d, h;
    while((m = v.exec(formatStr)) != null)
    {
        formatted += formatStr.slice(0, m.index);
        switch(m[1])
        {
            case "Y": formatted += theYear; break;
            case "y": formatted += theYear.toString().slice(-2); break;
            case "M": formatted += pad(theMonth+1, 2); break;
            case "m": formatted += theMonth+1; break;
            case "B": formatted += theMonthName; break;
            case "b": formatted += theMonthName.slice(0,3); break;
            case "D": formatted += pad(theDate, 2); break;
            case "d": formatted += theDate; break;
            case "o":
                d = theDate;
                formatted += (d==1 || d==21 || d==31) ? "st" : (d==2 || d==22) ? "nd" : (d==3 || d==23) ? "rd" : "th";
                break;
            case "W": formatted += theDayName; break;
            case "w": formatted += theDayName.slice(0,3); break;
            case "I": formatted += pad(theHour, 2); break;
            case "H":
                h = theHour % 12;
                if(h==0) h = 12;
                formatted += pad(h, 2);
                break;
            case "h":
                h = theHour % 12;
                if(h==0) h = 12;
                formatted += h;
                break;
            case "P": formatted += (theHour<12 ? "AM" : "PM"); break;
            case "p": formatted += (theHour<12 ? "am" : "pm"); break;
            case "q": formatted += (theHour<12 ? "a" : "p"); break;
            case "N": formatted += pad(theMinute, 2); break;
            case "n": formatted += theMinute; break;
            case "S": formatted += pad(theSecond, 2); break;
            case "s": formatted += theSecond; break;
            case "Z": formatted += pad(theMS, 3); break;
            case "z": formatted += theMS; break;
            case "e": formatted += theOffset < 0 ? "-" : "+"; break;    //if offset==0, it will be "+"
            case "F": formatted += pad(Math.floor(Math.abs(theOffset)/60), 2); break;
            case "f": formatted += Math.floor(Math.abs(theOffset)/60); break;
            case "G": formatted += pad(theOffset%60, 2); break;
            case "g": formatted += theOffset%60; break;
            case "%": formatted += "%"; break;
            default: formatted += m[0];
        }
        formatStr = formatStr.slice(m.index+2);
    }
    formatted += formatStr;

    return formatted;
}

$(function() {

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

    jQuery.ajaxUpdateOrder = function(order, settings){
        config = {
            'data': {},
            'url': '/manage/site/ajaxUpdateOrder',
            'type': 'POST',
            'dataType': 'json',
            'notifyMessage': 'Updating order...',
            'notifyContainer': notifyContainer,
            'notify': true
        };
        if(settings){ $.extend(config, settings); }

        if(config.url === '' && $(this).attr("href") !== undefined){
            config.url = $(this).attr("href");
        }
        $.extend(config.data, {'order': order, 'request_id': config.unique});
        if(config.url === ''){ alert("Ajax Error: No URL for simple request."); return false; }
        jQuery.ajax(config);
    };
    jQuery.fn.simpleAjax = function(settings) {
        config = {
            'url': '',
            'data': {},
            'type': 'POST',
            'checkEval': true,
            'dataType': 'json',
            'notifyMessage': 'Loading...',
            'notifyContainer': notifyContainer,
            'notify': true
        };
        if(settings){ $.extend(config, settings); }


        $(this).each(function(){
            var oconfig = jQuery.extend(true, {}, config);
            oconfig.unique = Math.floor(Math.random()*1100);
            if (oconfig.context === undefined) {
                oconfig.context = $(this);
            }
            if(oconfig.url === '' && $(this).attr("href") !== undefined){
                oconfig.url = $(this).attr("href");
            }
            if($(this).data("notify") !== undefined){
                oconfig.notify = $(this).data("notify");
            }
            if($(this).data("notify-message") !== undefined){
                oconfig.notifyMessage = $(this).data("notify-message");
            }
            if(oconfig.url === ''){ alert("Ajax Error: No URL for simple request."); return false; }
            if($(this).data('action') !== undefined) {
                oconfig.loadingMessage = $(this).data('action');
            }
            oconfig.initiator = $(this);
            jQuery.ajax(oconfig);
        });
        return $(this);
    };

    $.fn.defaultText = function(txt){
        return this.each( function(){
            var dtxt = txt;
            if (dtxt === undefined || dtxt === '') {
                dtxt = $(this).data('default-text');
            }
            $(this).data('default-text', dtxt);
            if ($(this).is('input')) {
                if($(this).val() === '' || $(this).val() == dtxt){
                    $(this).val(dtxt);
                    $(this).addClass('default-text');
                }
                $(this).focus(function(){
                    if($(this).hasClass('default-text')){
                        $(this).val('');
                        $(this).removeClass('default-text');
                    }
                });
                $(this).blur(function(){
                    if($(this).val() === ''){
                        $(this).val(dtxt);
                        $(this).addClass('default-text');
                    }
                });
                var self = this;
                $(this).parents('form').first().submit(function(){
                    if($(self).val() == dtxt){
                        $(self).val('');
                        /* $(self).focus(); */
                    }
                    return true;
                });
            } else if($(this).is('select')){
                if ($(this).val() === '') {
                    $(this).addClass('default-text');
                } else {
                    $(this).removeClass('default-text');
                }
                $(this).bind('change', function() {
                    if ($(this).val() === '') {
                        $(this).addClass('default-text');
                    } else {
                        $(this).removeClass('default-text');
                    }
                });
            }
        });
    };

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
    var originalSerialize = jQuery.fn.serialize;
    jQuery.fn.serialize = function(){
        this.each( function(){
            if($(this).hasClass('default-text') && $(this).val() == $(this).data('default-text')){
                $(this).val('');
            }
        });
        var serialized = originalSerialize.apply( this, arguments );
        this.each( function(){
            if($(this).hasClass('default-text')){
                $(this).val($(this).data('default-text'));
            }
        });

        return serialized;
    };
    jQuery.fn.allChecked = function() {
        var allChecked = true;
        this.each(function() {
            if(!$(this).is(":checked")) {
                allChecked = false;
            }
        });
        return allChecked;
    };
    jQuery.fn.getChecked = function() {
        var checkedItems = [];
        this.each(function() {
            if($(this).is(":checked")) {
                checkedItems.push($(this).val());
            }
        });
        return checkedItems;
    };

    jQuery.fn.findAll = function(selector) {
        return this.find(selector).add(this.filter(selector));
    };

    

    jQuery.debug = function(message){
        if(!_debug){ return true; }
        if(console !== undefined){
            console.debug(message);
        }
    };
    jQuery.limitString = function(string, len, wrap, end){
        if(end === undefined){ end = '&hellip;'; }
        if(string.length > len){
            var trimmed = string.substr(0, len - end.length) + end;
            if(wrap === undefined){ wrap = 'div'; }
            if(wrap !== false){
                return '<' + wrap + ' title="'+string+'">'+ trimmed +'</'+wrap+'>';
            }
        }else{
            return string;
        }
    };
    jQuery.fn.objectLength = function() {
        return parseInt(jQuery.map(this, function(n, i) { return i; }).length, 10);
    };
    Object.size = function(obj) {
        var size = 0, key;
        for (key in obj) {
            if (obj.hasOwnProperty(key)) size++;
        }
        return size;
    };
    jQuery.fn.getObjectId = function() {
        var id = $(this).attr("id");
        var href = $(this).attr("href");
        var parts = [];
        if(!isStringEmpty(id)){
            parts = id.split("-");
        }
        for(var key in parts){
            var part = parseInt(parts[key]);
            if(!isNaN(part) && part > 0){
                return part;
            }
        }

        if(!isStringEmpty(href)){
            parts = href.split("/");
        }

        for(var key in parts){
            var part = parseInt(parts[key]);
            if(!isNaN(part) && part > 0){
                return part;
            }
        }

        return false;
    };

    
    $.extend($.ui.dialog.prototype.options, {
        focus: function(event, ui) {
            var dialog = $(this).parents(".ui-dialog:first").first();
            var dialogZindex = parseInt(dialog.css("zIndex"), 10);
            $(this).find("select[multiple='multiple']").each(function() {
                if (!$(this).data('multiselect')) {
                    return;
                }
                var menu = $(this).multiselect('widget');
                menu.css('zIndex', dialogZindex + 1);
            });
        }
    });
});