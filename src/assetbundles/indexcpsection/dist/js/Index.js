/**
 * Refeed Me plugin for Craft CMS
 *
 * Index Field JS
 *
 * @author    Kristian S. P.
 * @copyright Copyright (c) 2020 Kristian S. P.
 * @link      https://github.com/kristiansp
 * @package   RefeedMe
 * @since     0.9.0-dev
 */

var NewFeedGroupModal = Garnish.Modal.extend( {

    init: function(containerHtml, settings) {

        $container = $(containerHtml).appendTo(Garnish.$bod);

        this.base($container, settings);

        this.$saveBtn = $container.find('.submit:first');
        this.$cancelBtn = $container.find('.cancel:first');

        this.addListener(this.$container, 'submit', 'save');
        this.addListener(this.$cancelBtn, 'click', 'cancel');
    },

    save: function(event) {
        event.preventDefault();

        form = event.target;

        selectedFeeds = form.find(':checked');

        feeds = [];

        for (var i = 0; i < selectedFeeds.length; i++) {
            feeds[i] = selectedFeeds[i].name;
        }

        data = {'feeds' : feeds};
        data[Craft.csrfTokenName] = Craft.csrfTokenValue;

        Craft.postActionRequest('refeed-me/feedgroups/save', data, function(response, textStatus) {

            // NB! This is probably overkill, but maybe it's useful
            if (textStatus === 'success') {
                if (response.success) {
                    console.log(response.msg);
                } else {
                    console.log('AJAX worked, but failed to save');
                }
            }
        });

        this.hide();
    },

    show: function() {
        this.base();
    },

    cancel: function() {
        this.hide();
    }

});
