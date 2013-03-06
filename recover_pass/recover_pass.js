if (window.rcmail) {

        rcmail.addEventListener('init', function(evt) {

                var tab = $('<span>').attr('id', 'settingstabpluginrecover_pass').addClass('tablink');
                var button = $('<a>').attr('href',rcmail.env.comm_path + '&_action=plugin.recover_pass').html(rcmail.gettext('recover_pass', 'recover_pass')).appendTo(tab);

                button.bind('click', function(e) {
                        return rcmail.command('plugin.recover_pass', this);});

                rcmail.add_element(tab, 'tabs');

                rcmail.register_command('plugin.recover_pass', function() {
                        rcmail.goto_url('plugin.recover_pass')}, true);

                rcmail.register_command('plugin.recover_pass-save', function() {

                        var input = document.createElement("input");
                        input.setAttribute("type", "text");
                        input.setAttribute("name", "do");
                        input.setAttribute("value", "save");
                        document.getElementById("recover_pass_form").appendChild(input);

                        rcmail.gui_objects.recover_pass_form.submit();

                }, true);

                 rcmail.register_command('plugin.recover_pass-delete', function() {

                        var input = document.createElement("input");
                        input.setAttribute("type", "text");
                        input.setAttribute("name", "do");
                        input.setAttribute("value", "delete");
                        document.getElementById("recover_pass_form").appendChild(input);

                        rcmail.gui_objects.recover_pass_form.submit();

                }, true);



        })
}

