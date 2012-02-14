// do not do anything, if no language is defined
if (wpc.enabled_languages.length > 0) {
  if (wpc.default_language == undefined) {
    wpc.default_language = wpc.enabled_languages[0];
  }

  (function($) {
    if (wpc.get_active_language == undefined) {
      wpc.get_active_language = function(element) {
        return wpc.get_surrounding_metabox(element).data("wpc_active_language");
      };
    }

    if (wpc.set_active_language == undefined) {
      /**
       * set the active language for the metabox.
       * should be the change handler of the select widget.
       */
      wpc.set_active_language = function () {
        var element = $(this);
        var language = element.val();
        var metabox = wpc.get_surrounding_metabox(element);
        metabox.data(".wpc_active_language", language);

        metabox.find(".wpc_localized_input_").filter("[lang!="+language+"]").hide();
        metabox.find(".wpc_localized_input_").filter("[lang="+language+"]").show();
      };
    }

    if (wpc.tokenize_localized_string == undefined) {
      /**
       * given a string in localized format
       * (i.e. [:de]german[:en]english or <!-- :en -->...<!--:-->)
       * returns a hash with the languages-keys and the localized text value
       * (e.g. {de: "german", en: "english"})
       */
      wpc.tokenize_localized_string = function(text) {
        var ret = {};

        var langs = [];
        var lang_texts = [];
        if (text.match(/^\[:\w\w\]/g)) {
          langs = text.match(/\[:\w\w\]/g).map(function (l) {return l.slice(2,4);});
          lang_texts = text.split(/\[:\w\w\]/).slice(1);
        } else if (text.match(/^<!--:\w\w-->/g)) {
          langs = text.match(/<!--:\w\w-->/g).map(function (l) {return l.slice(5,7);});
          lang_texts = text.replace(/^<!--:\w\w-->([\s\S]*)<!--:-->$/, "$1").split(/<!--:--><!--:\w\w-->/g);
        } else {
          // if string is not localized, assume the text is in the default lang
          langs = [wpc.default_language];
          lang_texts = [text];
        }
        var i = langs.length;
        while (i--) {
          ret[langs[i]] = lang_texts[i];
        }
        return ret;
      };
    }

    if (wpc.join_localized_string == undefined) {
      /**
       * join an associative array of the form
       * {de: "german", en: "english"} into a quicktags-delimited string
       */
      wpc.join_localized_string = function (texts) {
        var ret = "";
        for (var lang in texts) {
          if (texts.hasOwnProperty(lang)) {
            //ret+= "[:"+lang+"]" + texts[lang];
            ret+= "<!--:"+lang+"-->"+texts[lang]+"<!--:-->";
          }
        }
        return ret;
      };
    }

    if (wpc.localized_input_changed == undefined) {
      /**
       * set the original's value to the combined value of all localized fields
       */
      wpc.localized_input_changed = function () {
        var element = $(this);
        var changed_language = element.data("lang");
        var combined = $("#"+ element.attr('id').replace(new RegExp("_"+changed_language+"$"),''));

        var texts = wpc.tokenize_localized_string(combined.val());
        texts[changed_language] = element.val();

        combined.val(wpc.join_localized_string(texts));
      };
    }

    $(document).ready(function (){
      /* set active language for each metabox */
      $(".postbox").each(function() {
        $(this).data("wpc_active_language", wpc.default_languages);
      });

      /* add dropdown box for active language */
      /* create element first */
      var lang_selector = $("<select></select>").addClass("wpc_language_selector")
      lang_selector.change(wpc.set_active_language);
      var i = wpc.enabled_languages.length;
      while (i--) {
        $("<option>"+ wpc.enabled_languages[i]+ "</option>").prependTo(lang_selector);
      }
      /* insert after the title in the header of the metabox */
      $(".postbox").has(".wpc_localized_input").find(".hndle").each(function() {
        lang_selector.clone(true, true).appendTo($(this));
      });

      /* hide each .wpc_localized_input and add two localized versions after */
      $(".wpc_localized_input").each(function() {
        var original = $(this);
        var id = original.attr('id');
        var placeholder = original.attr("placeholder");
        var name = original.attr('name');

        var active_language = wpc.default_language;

        /* parse original value and save it */
        var loc_values = wpc.tokenize_localized_string(original.val());
        original.data("wpc_lang_values", loc_values);

        original.hide();

        var i = wpc.enabled_languages.length;
        while (i--) {
          var language = wpc.enabled_languages[i];
          original.clone().attr({
            id:          id+'_'+language,
            placeholder: placeholder+" ("+language+")",
            lang:        language,
            name:        name+'_'+language,
            value:       loc_values[language]
          }).data('lang', language).removeClass("wpc_localized_input").addClass("wpc_localized_input_ wpc_localized_input_"+language).blur(wpc.localized_input_changed).insertAfter(original);
        }
        /* show active language input field */
        $('#'+id+'_'+wpc.default_language).show();
      });
    });
  })(jQuery);
}
