(function() {

    tinymce.create('tinymce.plugins.WidgetPlugin', {
        init : function(ed, url) {
            var self = this, lookup = {}, i, y, item, name;
            var node;
            var widget = ed.settings.box.getInnerList('WidgetList');

            if(!widget) return;


            function isWidgetImg(node) {
                return node && node.nodeName === 'IMG' && ed.dom.hasClass(node, 'mceItemWidget');
            };

            self.editor = ed;
            self.url = url;

            ed.onPreInit.add(function() {
                ed.schema.addValidElements('widget[*]');
                ed.parser.addNodeFilter('widget', function(nodes) {
                    var i = nodes.length;

                    while (i--){
                        node = nodes[i];
                        replacement = new tinymce.html.Node('img', 1);
                        replacement.attr({
                            src : '/sfPhastPlugin/js/tinymce/themes/advanced/img/trans.gif',
                            class: 'mceItemWidget',
                            'data-id': node.attr('data-id'),
                            'data-type': node.attr('data-type')
                        });

                        node.replace(replacement);
                    }

                });

                // Convert image placeholders to video elements
                ed.serializer.addNodeFilter('img', function(nodes, name, args) {
                    var i = nodes.length, node;

                    while (i--) {
                        node = nodes[i];
                        if ((node.attr('class') || '').indexOf('mceItemWidget') !== -1){
                            replacement = new tinymce.html.Node('widget', 1);
                            value = new tinymce.html.Node('#text', 3);
                            value.value = node.attr('data-id');
                            replacement.append(value);
                            replacement.attr({
                                'data-id': node.attr('data-id'),
                                'data-type': node.attr('data-type')
                            });
                            node.replace(replacement);
                        }
                    }
                });
            });


            ed.onInit.add(function() {
                if (ed.theme && ed.theme.onResolveName) {
                    ed.theme.onResolveName.add(function(theme, path_object) {
                        if (path_object.name === 'img' && ed.dom.hasClass(path_object.node, 'mceItemWidget'))
                            path_object.name = 'widget';
                    });
                }
            });

            ed.addCommand('mceWidget', function() {

                var node = $(ed.selection.getNode());
                var box = $$.Box.createContainerForList(
                    'WidgetList',
                    'Выберите виджет',
                    {edit: (node.length && node.hasClass('mceItemWidget')) ? node.data('id') : 'x', holder: ed.settings.box.parameters.holder}
                ).open();


            });

            ed.addButton('widget', {title : 'Виджет', cmd : 'mceWidget'});
            ed.onNodeChange.add(function(ed, cm, node) {
                cm.setActive('widget', isWidgetImg(node));
            });



        }

    });

    // Register plugin
    tinymce.PluginManager.add('widget', tinymce.plugins.WidgetPlugin);
})();
