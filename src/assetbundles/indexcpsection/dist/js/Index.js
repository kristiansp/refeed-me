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

// TODO: May not be needed
/*
if (typeof Craft.RefeedMe === typeof undefined) {
    Craft.RefeedMe = {};
}
*/
$(function() {
    // Settings pane toggle for feeds index
    $(document).on('click', '#feedgroups .settings', function(e) {
        e.preventDefault();

        var row = $(this).parents('tr').data('id') + '-settings';
        var $settingsRow = $('tr[data-settings-id="' + row + '"] .settings-pane');

        $settingsRow.toggle();
    });

    var FeedGroups = Garnish.Base.extend(
    {
        feedGroups: null,
        $container: null,
        $addFeedGroupBtn: null,

        init: function() {
            this.feedGroups = [];
            this.$container = $('#feedgroups');

            var $feedGroups = this.getFeedGroups();

            for (var i = 0; i < $feedGroups.length; i++) {
                var feedGroup = new FeedGroup($feedGroups[i]);
                this.feedGroups.push(feedGroup);
            }

            /*
            this.sorter = new Garnish.DragSort($routes, {
                axis: Garnish.Y_AXIS,
                onSortChange: $.proxy(this, 'updateRouteOrder')
            });
            */

            this.$addFeedGroupBtn = $('#add-feedgroup-btn');

            this.addListener(this.$addFeedGroupBtn, 'click', 'addFeedGroup');
        },

        getFeedGroups: function() {
            return this.$container.find('tbody tr.feedgroup');
        },

        addFeedGroup: function() {
            new FeedGroupModal();
        }
    });

    var FeedGroup = Garnish.Base.extend(
    {
        $container: null,
        id: null,
        feeds: null,
        modal: null,

        init: function(container) {
            this.$container = $(container);

            this.id = $(this.$container).attr('data-id');
            this.feeds = JSON.parse( $(this.$container).attr('data-feeds') );

            // TODO: Should be attached to something else (icon)
            this.addListener(this.$container, 'click', 'edit');
        },

        edit: function() {
            if (!this.modal) {
                this.modal = new FeedGroupModal(this);
            }
            else {
                this.modal.show();
            }
        },
    });

    var FeedGroupModal = Garnish.Modal.extend( {

        feedGroup: null,
        feedsTable: null,

        init: function(feedGroup) {

            this.feedGroup = feedGroup;

            // TODO: Change this to createNode for the form, and add the rest from template?

            // Create <form> element (to avoid getting a documentFragment)
            var form = document.createElement("form");
            form.classList.add('modal');

            // Set form to to contents of template
            var template = document.querySelector('#feedgroup-modal');
            var content = template.content.cloneNode(true);

            // Add content to form, and form to DOM
            form.appendChild(content);
            var containerHtml = document.body.appendChild(form);

            $container = $(containerHtml).appendTo(Garnish.$bod);

            this.feedsTable = new Craft.AdminTable({
                tableSelector: $container.find('table'),
                noObjectsSelector: '#nofeedgroups',
                newObjectBtnSelector: '#newfeedcontainer', // TODO: What is this?
                sortable: true,
                reorderAction: 'refeed-me/feedgroups/reorder-feeds',
                confirmDeleteMessage: 'You can\'t delete feeds from here.',
                reorderSuccessMessage: 'Successfully reordered feeds',
                reorderFailMessage: 'Failed to reorder feeeds',
            });

            checkboxCells = $container.find('.checkbox');
            for (var i = 0; i < checkboxCells.length; i++) {
                var feedId = checkboxCells[i].dataset.feedid;
                var settings = {
                    'name': feedId,
                    'checked': (feedGroup && feedGroup.feeds.includes(parseInt(feedId))) ? 'checked' : null
                }
                checkbox = Craft.ui.createCheckbox(settings);
                checkbox.appendTo(checkboxCells[i]);
            }

            this.base($container);

            this.$saveBtn = $container.find('.submit:first');
            this.$cancelBtn = $container.find('.cancel:first');

            this.addListener(this.$container, 'submit', 'save');
            this.addListener(this.$cancelBtn, 'click', 'cancel');

        },

        save: function(event) {
            event.preventDefault();

            form = event.target;
            selectedFeeds = $(form).find(':checked');

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

            form.reset();
            this.hide();
        },

        show: function() {
            this.base();
        },

        cancel: function() {
            this.hide();
        }

    });

    Craft.RefeedMe = new FeedGroups();
});
