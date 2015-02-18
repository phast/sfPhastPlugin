/**
 * Phast UI client
 * @author Pavel Bondarovich <p.bondarovich@gmail.com>
 *
 * @see $$.Box
 * @see $$.List
 */
$.fn.reverse = [].reverse;
$.fn.serializeJSON = function(){
    var json = {};
    jQuery.map($(this).serializeArray(), function(n, i){
        json[n['name']] = n['value'];
    });
    return json;
};

(function(window, undefined){


    var Phast = function(){
        console.log('Phast $$');
    }


    $.extend(Phast, {
        check: function(data){
            if(data.session_broken)
                document.location.reload();

            if(data.error){
                var out = "";
                if(jQuery.isArray(data.error)){
                    for(i in data.error){
                        out += data.error[i] + "\n";
                    }
                }else{
                    out += "Ошибка: " + data.error;
                }
                alert(out);
                return false;
            }
            return true;
        },
        error: function(type, message){
            if(type == 'parsererror' || type == 'error'){
                (window.open('', '', 'width=950,height=600,scrollbars=yes')).document.write(message);
            }
        },
        populateSelect: function(node, data){
            var output, type, types = ['$select', '$checkgroup', '$radiogroup'];
            var renderOptions = function(value, caption, field){
                switch(type){
                    case '$select':
                        return '<option value="' + value + '">' + caption + '</option>';
                        break;

                    case '$checkgroup':
                    case '$radiogroup':
                        return '<li><label><input type="' + (type == '$checkgroup' ? 'checkbox' : 'radio') + '" name="' + field + (type == '$checkgroup' ? '[]' : '') + '" value="' + value + '">' + caption + '</label></li>';
                        break;
                }
            };

            while(type = types.shift()){
                $.each(data[type] || [], function(field, options){
                    output = '';
                    if(options[''] !== undefined) output += renderOptions('', options['']);
                    $.each(options, function(value, caption){
                        if(value !== '') output += renderOptions(value, caption, field);
                    });
                    switch(type){
                        case '$select':
                            node.find('select[name^='+field+']').html(output);
                            break;

                        case '$checkgroup':
                            node.find('ul.phast-box-checkgroup[data-name='+field+']').html(output);
                            break;
                        case '$radiogroup':
                            node.find('ul.phast-box-radiogroup[data-name='+field+']').html(output);
                            break;
                    }
                });

            }

        },
        morph: function(n, u1, u234, u10, prepend){
            n = parseInt(n);
            n = Math.abs(n) % 100;
            if (n>10 && n<20) return (prepend ? n+prepend : '') + u10;
            n = n % 10;
            if (n>1 && n<5) return (prepend ? n+prepend : '') + u234;
            if (n==1) return (prepend ? n+prepend : '') + u1;
            return (prepend ? n+prepend : '') + u10;
        }
    });

    var ajax = function(url, data, settings){

        return $.ajax($.extend({

            type: 'post',
            url: url,
            data: data,
            dataType: 'json',
            cache: false

        }, settings || {}));

    };

    $(document).ajaxStart(function(){
        $('#header > i.loading').addClass('active');
    });

    $(document).ajaxStop(function(){
        $('#header > i.loading').removeClass('active');
    });


    /**
     * List
     * @todo отрефакторить работу отправки информации об открытых элементах и о страницах
     * @todo изобрести mask для объекта и для потомка
     * @todo [. Pattern], [Parent Pattern~10]$REL_COLUMN, [. Pattern~10]$PK
     * @todo Не забываем, что примари ключ может быть составным (разделяется запятой)
     * @todo При рендере страниц маску брать из DOM родителя TR
     */
    var List = function(model, options){

        var list = this, element, table, renderRows, items, timerLoading, timerRefresh, elementLoading;
        var sortData;
        var builded;
        var autoIncrement = 0;
        var triggerEvent = function(event, option){
            if(list.event) list.event.call(list, event, option || {}, list);
        }
        var filterRendered = false;

        $.extend(
            this,
            {
                id: model.id,

                box: null,

                element: null,
                table: null,

                empty: 'Элементы не найдены',

                autoload: true,
                layout: {},

                controls: [],
                columns: [],

                parameters: {},
                pages: {},
                opened: {},

                event: null,

                request: null,
                response: null,
                data: null
            },
            model,
            options || {},
            {
                load: function(silent){
                    triggerEvent('preload');

                    if(!builded)
                        list.build();

                    if(list.request)
                        list.request.abort();

                    if(!list.response)
                        list.table.find('> tbody').html('<tr><td colspan="' + list.columns.length + '">Загрузка</td></tr>');

                    if(list.refresh){
                        clearTimeout(timerRefresh);
                    }

                    if(!silent){
                        timerLoading = setTimeout(function(){
                            elementLoading.addClass('long');
                        }, 1500);
                        elementLoading.css({opacity: 0}).show().animate({opacity: 0.5}, 1000);
                    }
                    list.request = ajax('?phastui', list.makeQuery())
                        .success(function(response){
                            if(Phast.check(response)){
                                list.response = response;
                                list.data = response;

                                if(response.$parameters)
                                    $.extend(list.parameters, response.$parameters);

                                list.render();
                            }else{
                                // Ошибка
                            }
                        })
                        .complete(function(){
                            if(list.refresh){
                                clearTimeout(timerRefresh);
                                timerRefresh = setTimeout(function(){
                                    list.load(true);
                                }, list.refresh);
                            }
                            clearTimeout(timerLoading);
                            elementLoading.removeClass('long').stop().animate({opacity: 0}, 100, function(){
                                $(this).hide();
                            });
                        })
                        .error(function(xhr, type, exception){
                            Phast.error(type, xhr.responseText);
                        })
                    ;


                },

                makeQuery: function(){
                    var parameters = {};
                    var output = {
                        $id: list.id,
                        $pages: list.pages,
                        $opened: list.opened,
                        $parameters: {},
                    }

                    if(list.box){
                        $.extend(parameters, list.box.parameters);

                        if(parameters.pk){
                            parameters.owner = parameters.pk;
                            delete parameters.pk;
                        }

                        $.extend(list.parameters, parameters);

                    }

                    if(list.makeParameters)
                        list.makeParameters.call(this, list.parameters);

                    if(list.parameters)
                        $.extend(output.$parameters, list.parameters);

                    if(!filterRendered){
                        output.$renderFilters = 1;
                    }


                    return output;
                },

                renderSkeleton: function(){
                    var output = '', i, control;

                    if(list.controls.length){
                        output += '<div class="phast-list-controls">';
                        for(i in list.controls){
                            control = list.controls[i];
                            output += '<a href="#" data-guid="' + (i-0+1) + '" class="icon-' + (control.icon || 'create') + '">' + (control.caption || 'Кнопка') + '</a>';
                        }
                        output += '</div>';
                    }

                    output +=
                        '<table>' +
                        '<thead><tr>' + list.renderСolumns() + '</tr></thead>' +
                        '<tbody></tbody>' +
                        '</table>' +
                        '<i class="loading"></i>'
                    ;
                    return output;
                },

                renderСolumns: function(){
                    var output = '', column, i = -1;
                    while(i++, undefined !== (column = list.columns[i])){
                        if('.' == column)
                            output += '<th style="width:1%;"></th>';
                        else
                            output += '<th>' + column + '</th>';
                    }

                    return output;
                },

                render: function(){
                    //if(window.console && window.console.time) console.time('RenderTime');

                    if(list.data.$filters){
                        filterRendered = true;
                        list.renderFilters(list.data.$filters);
                        delete list.data.$filters;
                    }

                    var output = '', pattern, level = 0, i;

                    renderRows = [];

                    $.each(list.data.items, function(name, items){
                        pattern = list.pattern(name);
                        list.runPattern(pattern);
                    });

                    for(i = renderRows.length - 1; i >= 0; i--){
                        output = list.renderRow(renderRows[i], level, i) + output;
                        level = renderRows[i].level;
                    }

                    table.find('> tbody').html(output)
                        .tableDnD({
                            onDragClass: 'move',
                            onDrop: function($table, row) {
                                if(sortData !== $.tableDnD.serialize()){
                                    var item = $(row).closest('tr'),
                                        sort = item.data('sort'),
                                        selector = 'tr[data-sort="'+sort+'"]:first',
                                        prev = $(row).prevAll(selector),
                                        next = $(row).nextAll(selector);

                                    ajax('?phastui&sort', $.extend(list.makeQuery(), {pattern: item.data('pattern'), pk: item.data('pk'), prev: (prev.length ? prev.data('pk') : 0), next: (next.length ? next.data('pk') : 0)}))
                                        .success(function(response){
                                            if(Phast.check(response)){
                                                list.load();
                                            }else{
                                                list.render();
                                            }
                                        })
                                        .complete(function(){
                                        })
                                        .error(function(xhr, type, exception){
                                            Phast.error(type, xhr.responseText);
                                        })
                                    ;
                                }else{
                                    $('> tbody > tr', table).removeClass('nodrop');
                                }

                            },
                            onDragStart: function($table, row) {
                                var item = $(row).closest('tr'),
                                    sort = item.data('sort');

                                sortData = $.tableDnD.serialize();
                                $('> thead > tr', table).addClass('nodrop');
                                $('> tbody > tr:not([data-sort="'+sort+'"])', table).addClass('nodrop');

                            },
                            dragHandle: 'a.sort'
                        });

                    // Элементы не найдены
                    if(!renderRows.length) this.element.find(' > table > tbody').html('<tr><td colspan="'+this.columns.length+'">'+list.empty+'</td></tr>');
                    this.element.trigger('phast-render', list);

                    //if(window.console && window.console.time) console.timeEnd('RenderTime');
                },

                reloadFilters: function(){
                    filterRendered = false;
                },

                renderFilters: function(template){

                    var filtersNode = element.find('> form.phast-list-filters');
                    if(!filtersNode.length){
                        filtersNode = $('<form class="phast-list-filters phast-box">'+template+'<div class="phast-box-buttons"><button class="phast-ui-button phast-filters-reset" type="reset">Очистить</button> <button class="phast-ui-button phast-filters-reload" type="submit">Обновить</button></div></form>');
                        table.before(filtersNode);
                    }

                    Phast.populateSelect(filtersNode, list.data);

                    if(list.data.$placeholder){
                        $.each(list.data.$placeholder, function(field, value){
                            filtersNode.find('[name="'+field+'"]').attr('placeholder', value);
                        });
                    }

                    if(list.parameters)
                        filtersNode.populate(list.parameters);

                    filtersNode.on('change', 'select', function(){
                        filtersNode.submit();
                    });

                    filtersNode.on('change', 'input[type="radio"]', function(){
                        filtersNode.submit();
                    });

                    filtersNode.on('change', 'input[type="checkbox"]', function(){
                        filtersNode.submit();
                    });

                    filtersNode.on('click', 'button.phast-filters-reset', function(){
                        filtersNode.resetForm().submit();
                    });

                    filtersNode.on('submit', function(){
                        var parameters = {};
                        filtersNode.find('input[type="checkbox"], input[type="radio"]').each(function(){
                            parameters[$(this).prop('name').replace(/\[\w+\]$/, '')] = '';
                        });

                        $.extend(list.parameters, parameters, filtersNode.serializeJSON());
                        list.load();
                        return false;
                    });

                    filtersNode.find('div.phast-box-calendar').each(function(){
                        $(this).find('> input').datepicker(
                            {
                                dateFormat: 'd MM yy',
                                numberOfMonths: 3,
                                showOn: 'button',
                                buttonImage: '/sfPhastPlugin/icons/date.png',
                                buttonImageOnly: true
                            }
                        );
                    });

                },

                runPattern: function(pattern, relation, relId, level){
                    var data, level = level || 0, mask;

                    if(relation){
                        list.data.items[relation.target] && (
                            data = list.data.items[relation.target][relation.source + ' ' + relId]
                        );
                    }else{
                        list.data.items[pattern.name] && (
                            data = list.data.items[pattern.name]['.']
                        );
                    }

                    if(!data) return false;
                    mask = pattern.name + ' ' + (relation ? relation.source + ' ' + relId : '.');

                    $.each(data, function(x, item){
                        if('pages' == x) return;

                        renderRows.push({
                            mode: 'Item',
                            pattern: pattern,
                            item: item,
                            mask: pattern.name + ' ' + (relation ? relation.source + ' ' + relId : '.') + ' ' + item.$pk,
                            relation: relation,
                            relId: relId,
                            level: level
                        });


                        if(pattern.relations)
                            if(!pattern.flex || (list.opened[mask + ' ' + item.$pk]))
                                $.each(pattern.relations, function(x, rel){
                                    list.runPattern(list.pattern(rel.target), rel, rel.source_field == '%' ? '%' : item[rel.source_field], level + 1);
                                });
                    });

                    if(data.pages)
                        renderRows.push({
                            mode: 'Pages',
                            mask: pattern.name + ' ' + (relation ? relation.source + ' ' + relId : '.'),
                            pages: data.pages,
                            level: level
                        });

                    return true;
                },

                getRow: function(guid){
                    return renderRows[guid];
                },

                renderRow: function(options, prelevel, guid){
                    options.guid = guid;
                    return list['renderRow' + options.mode](options, prelevel);
                },

                renderRowItem: function(options, prelevel){
                    var output = '', num = -1, i, control, column, filled = 0, script;
                    var item = options.item,
                        pattern = options.pattern,
                        mask = options.mask,
                        relation = options.relation,
                        guid = options.guid,
                        level = options.level;

                    output += '<tr' + pattern.getItemHTMLAttributes(item, relation, level, mask, guid) + '>';
                    while(num++, column = pattern.template[num]){
                        if(!filled && column == '*' && ++filled){
                            output += list.repeatColumn(list.columns.length - pattern.template.length + 1);
                            continue;
                        }

                        output += '<td ' + (num == 0 ? 'class="control"' : '') + '>';

                        if(num == 0){
                            output += '<div style="padding-left: ' + (level * 30) + 'px">';
                            output += '<i ' + (level > prelevel ? 'class="last"' : '') + '>' + new Array(level + 1).join('<i></i>') + '</i>';
                            if(pattern.flex){
                                if(!item.$hasChildren){
                                    output += '<span class="no-children"></span>';
                                }else if(list.opened[mask]){
                                    output += '<a href="#collapse" class="collapse"></a>';
                                }else{
                                    output += '<a href="#expand" class="expand"></a>';
                                }
                            }
                            output += '<a href="#action" class="action level' + level + ' icon-' + (item.$icon || pattern.icon || 'edit') + '">';

                        }

                        if(column == '.visible'){
                            output += List.toggleButton('.visible', item.visible, 'visible', 'hidden');
                        }else if(column == '.delete'){
                            output += List.actionButton(':.delete', 'delete');
                        }else if(':' == column[0]){
                            script = pattern.scripts[column.substring(1)];
                            output += script ? script.call(options, item, null, list, pattern) : '';
                        }else{
                            output += $.isFunction(column) ? column(item, level, pattern) : (item[column] !== undefined ? (item[column] !== null ? item[column] : '') : column || '');
                        }

                        if(num == 0){
                            output += '</a>';
                            if(pattern.sort) output += '<a href="#sort" class="sort"></a>';
                            if(pattern.controls.length){
                                output += '<div class="controls">';
                                for(i in pattern.controls){
                                    control = pattern.controls[i];
                                    if(item.$controls && !~$.inArray(control.icon, item.$controls)) continue;
                                    output += '<a href="#" data-guid="' + (i-0+1) + '" class="icon-' + (control.icon || 'create') + '" title="' + (control.caption || 'Кнопка') + '"></a>';
                                }
                                output += '</div>';
                            }

                            output += '</div>';
                        }
                        output += '</td>';
                    }

                    if(!filled && pattern.template.length < list.columns.length){
                        output += list.repeatColumn(list.columns.length - pattern.template.length);
                        filled = true;
                    }

                    output += '</tr>';
                    return output;
                },

                renderRowPages: function(options, prelevel){
                    var output = '', page;
                    var item = options.item,
                        mask = options.mask,
                        pages = options.pages,
                        level = options.level;


                    output += '<tr class="pages">';
                    output += '<td class="control">';

                    output += '<div style="padding-left: ' + (level * 30) + 'px">';
                    output += '<i ' + (level > prelevel ? 'class="last"' : '') + '>' + new Array(level + 1).join('<i></i>') + '</i>';

                    for(page = 1; pages >= page; page++){
                        if((page == 1 && !list.pages[mask]) || (list.pages[mask] == page)){
                            output += '<a href="#" class="active" data-mask="' + mask + '">' + page + '</a>';
                        }else{
                            output += '<a href="#" data-mask="' + mask + '">' + page + '</a>';
                        }
                    }

                    output += '</div>';
                    output += '</td>';
                    output += list.repeatColumn(list.columns.length - 1);
                    output += '</tr>';


                    return output;
                },

                repeatColumn: function(count){
                    var output = '';
                    while(count-- > 0) output += '<td></td>';
                    return output;
                },

                renderLevel: function(){
                    return '';
                },

                pattern: function(id){
                    return list.layout[id];
                },

                choose: function(value, caption){
                    list.chooseNode.find('> input[type=hidden]').val(value);
                    list.chooseNode.find('> input[type=text]').val(caption);
                    list.box.close(true);
                },

                build: function(){
                    builded = true;

                    element.html(
                        list.renderSkeleton()
                    );

                    element.on('phast-load.phast', function(){
                        list.load();
                    });

                    list.table = table = element.find('> table');

                    elementLoading = element.find('> i.loading');

                    element.on('click.phast', 'div.phast-list-controls > a', function(event){
                        var guid = $(this).data('guid')-1,
                            control = list.controls[guid];

                        if(control.action)
                            control.action.call(control, $(this), list, event);

                        return false;
                    });

                    table.on('click', 'tbody > tr.pages > td > div > a', function(){
                        if($(this).hasClass('active')) return;
                        list.pages[$(this).data('mask')] = $(this).text();
                        list.load();
                        return false;
                    });

                    table.on('click', 'tbody > tr > td.control > div > div.controls > a', function(event){
                        var row = $(this).closest('tr'),
                            pattern = list.pattern(row.data('pattern')),
                            data = renderRows[row.data('guid')],
                            item = data.item,
                            guid = $(this).data('guid')-1,
                            control = pattern.controls[guid];

                        if(control.action)
                            control.action.call(data, item, $(this), list, pattern, event);

                        return false;
                    });

                    table.on('click', 'tbody > tr > td.control > div > a.collapse', function(){
                        list.opened[$(this).closest('tr').data('mask')] = 0;
                        list.render();
                        return false;
                    });

                    table.on('click', 'tbody > tr > td.control > div > a.expand', function(){
                        list.opened[$(this).closest('tr').data('mask')] = 1;
                        list.load();
                        return false;
                    });

                    table.on('click', 'tbody > tr > td.control > div > a.action', function(event){
                        var row = $(this).closest('tr'),
                            pattern = list.pattern(row.data('pattern')),
                            data = renderRows[row.data('guid')],
                            item = data.item;
                        if(pattern.action)
                            pattern.action.call(data, item, $(this), list, pattern, event);
                        return false;
                    });

                    table.on('click', 'tbody > tr > td a.phast-list-toggle', function(){
                        var node = $(this),
                            success = 'icon-' + node.data('success'),
                            fail = 'icon-' + node.data('fail'),
                            successCaption = node.data('success-caption'),
                            failCaption = node.data('fail-caption'),
                            action = node.data('action'),
                            row = node.closest('tr'),
                            pattern = list.pattern(row.data('pattern')),
                            data = renderRows[row.data('guid')],
                            item = data.item;

                        if(node.hasClass(success)){
                            node.addClass(fail);
                            node.removeClass(success);
                            node.html(failCaption);
                        }else{
                            node.removeClass(fail);
                            node.addClass(success);
                            node.html(successCaption);
                        }

                        pattern.request(action, item.$pk);

                        return false;
                    });

                    table.on('click', 'tbody > tr > td a.phast-list-action', function(event){
                        var node = $(this),
                            action = node.data('action'),
                            row = node.closest('tr'),
                            pattern = list.pattern(row.data('pattern')),
                            data = renderRows[row.data('guid')],
                            item = data.item,
                            script;

                        if(action.indexOf(':') === 0){
                            script = pattern.scripts[action.substring(1)];
                            if(script){
                                script.call(data, item, $(this), list, pattern, event);
                            }else{
                                alert('Скрипт ' + action.substring(1) + ' не найден');
                            }

                        }else{
                            pattern.request(action, item.$pk);
                        }

                        return false;
                    });

                    table.on('click', 'tbody > tr > td.control > div > a.sort', function(e){
                        e.preventDefault();
                    });
                },

                destroy: function(){
                    element.empty().off('.phast');
                    list = undefined;
                }


            }
        );

        $.each(list.layout, function(name, pattern){
            pattern.name = name;
            pattern.scripts['.delete'] = function(item, node, list){
                if(confirm('Удалить элемент «'+node.closest('tr').find('> td.control > div > a.action').text()+'»?')){
                    pattern.request('.delete', item.$pk);
                    node.closest('tr').remove();
                }
            };
            $.extend(pattern, {
                getItemHTMLAttributes: function(item, relation, level, mask, guid){
                    var output = '', attributes = {};

                    attributes.pattern = this.name;
                    attributes.level = level;
                    attributes.pk = item.$pk;
                    attributes.mask = mask;
                    attributes.guid = guid;
                    if(this.sort) attributes.sort = mask.substring(0, mask.lastIndexOf(' '));

                    if(this.relations)
                        $.each(this.relations, function(x, rel){
                            if(rel.source_field != '%'){
                                attributes[rel.source_field] = item[rel.source_field];
                            }
                        });

                    if(relation){
                        if(relation.target_field != '%') {
                            attributes[relation.target_field] = item[relation.target_field];
                        }
                    }

                    $.each(attributes, function(key, value){
                        output += ' data-' + key + '="' + value + '"';
                    });

                    if(item.$class) output += ' class="' + item.$class + '"';
                    if(item.$style) output += ' style="' + item.$style + '"';

                    return output;
                },
                request: function(action, pk, options){
                    if(!options)
                        options = {};

                    if(!options.success)
                        options.success = function(response){
                            if(!Phast.check(response)){
                                list.render();
                            }
                        }

                    var query = $.extend(list.makeQuery(), {$pattern: this.name, $action: action, $pk: pk}, options.parameters || {});

                    ajax('?phastui', query)
                        .success(options.success)
                        .error(function(xhr, type, exception){
                            Phast.error(type, xhr.responseText);
                        })
                    ;
                }
            });
        });


        $(function(){
            list.element = element = $(list.attach);
            element.addClass('phast-list phast-ui');

            if(list.autoload)
                list.load();
            else
                element.html(list.wait || '');
        });

    }

    $.extend(List, {
        model: {},
        register: function(model){
            this.model[model.id] = model;
        },
        create: function(id, options){
            if(!this.model[id]){
                alert(id + ' не найден');
                return;
            }

            return new List(this.model[id], options);
        },
        toggleButton: function(action, condition, success, fail, success_caption, fail_caption){
            return '<a href="#'+action+'" class="phast-list-toggle '+(success_caption !== undefined ? 'with-caption' : '')+' icon-'+(condition ? success : fail)+'" data-action="'+action+'" data-success="'+success+'" data-fail="'+fail+'" data-success-caption="'+(success_caption||'')+'" data-fail-caption="'+(fail_caption||'')+'">'+(condition ? (success_caption||'') : (fail_caption||''))+'</a>';
        },
        actionButton: function(action, icon, caption, hint){
            return '<a href="#'+action+'" class="phast-list-action icon-'+icon+' ' + (caption !== null && caption !== undefined ? 'with-caption' : '') + '" data-action="'+action+'" '+(hint?'title="'+hint+'"':'')+'>' + (caption !== null ? caption||'' : '') + '</a>';
        },
        iconCaption: function(icon, caption, hint){
            return '<div class="phast-list-iconcaption '+(caption === null ?'without-caption':'')+' icon-'+icon+'" '+(hint?'title="'+hint+'"':'')+'>' + (caption !== null ? caption||'' : '') + '</div>';
        },
        noticeLine: function(content, hint){
            return '<span class="notice" '+(hint?'title="'+hint+'"':'')+'>' + (content !== null ? content : '') + '</span>';
        },
    });


    /**
     * Box
     * @mode custom | editor | select
     *
     *
     */
    var Box = function(model, options){

        var root = this,
            request,
            response;

        var formNode,
            rootNode,
            loadingNode;

        var toggleAutoClose;

        var initialized,
            loading,
            opened,
            locked

        var serialized;

        var execute = function(name){
            if(root.events[name])
                root.events[name].call(root, root, rootNode);
        }

        var lists = [];

        $.extend(
            root,
            {
                id: null,
                receive: false,
                relation: null,
                parameters: {},
                data: {},
                attach: null,
                autoload: false,
                list: null,
                events: {}
            },
            model,
            options || {},
            {
                makeQuery: function(){
                    var parameters = {};
                    var output = {
                        $id: root.id,
                    };

                    if(root.list){
                        $.extend(parameters, root.list.parameters);
                        /**
                         * todo Проверить что все работает и ничго не сломалось (pk > owner)
                         */
                        if(parameters.pk){
                            parameters.owner = parameters.pk;
                            delete parameters.pk;
                        }
                        $.extend(root.parameters, parameters);
                    }

                    if(!$.isEmptyObject(root.parameters)){
                        $.each(root.parameters, function(name, value){
                            output['$parameters['+name+']'] = value;
                        });
                    };

                    return output;
                },

                open: function(){
                    if(opened) return;

                    if(root.attach)
                        rootNode.removeClass('phast-box-modal');
                    else
                        rootNode.addClass('phast-box-modal');

                    rootNode.appendTo(root.attach || $$.Box.attach || 'body').hide();
                    root.show();

                    if(root.$afterOpen)
                        root.$afterOpen.call(root, root, rootNode);

                    execute('afterOpen');

                    return root;
                },

                populate: function(){
                    formNode.populate(root.data);
                    rootNode.find('textarea.phast-box-textedit').each(function(){
                        var item = $(this),
                            editor = tinyMCE.editors[item.prop('id')];
                        if(editor) editor.load();
                    });
                    formNode.find('div.phast-box-static').each(function(i, item){
                        item = $(item);
                        var field = item.data('field');
                        if(root.data[field])
                            item.html(root.data[field]);
                    });
                    if(root.data.$placeholder){
                        $.each(root.data.$placeholder, function(field, value){
                            formNode.find('[name="'+field+'"]').attr('placeholder', value);
                        });
                    }
                },

                refresh: function(){
                    if(root.receive){
                        root.load();
                    }else{
                        root.render();
                    }
                },

                load: function(){
                    locked = true;
                    loading = true;

                    if(request)
                        request.abort();

                    root.loadingAnimate();
                    request = ajax('?phastui&receive', root.makeQuery())
                        .success(function(response){
                            if(Phast.check(response)){
                                response = response;
                                root.data = response;

                                if(root.data.$parameters)
                                    $.extend(root.parameters, root.data.$parameters);
                                $.each(lists, function(i, list){
                                    if(root.parameters.pk || list.ignorePk)
                                        list.load();
                                });

                                root.render();

                            }else{
                                //if(opened)
                                root.close();
                                //else
                                //	root.destroy();
                            }

                        })
                        .complete(function(){
                            locked = false;
                            loading = false;
                            root.loadedAnimate();
                        })
                        .error(function(xhr, type, exception){
                            Phast.error(type, xhr.responseText);
                        })
                    ;
                },

                render: function(){

                    execute('beforeRender');

                    if(root.uri){
                        var params = [];
                        $.each(root.uri, function(i, part){
                            params.push(root.parameters[part]);
                        });
                        document.location.hash = '/' + root.id + '/' + params.join('/');
                    }

                    Phast.populateSelect(formNode, root.data);

                    rootNode.find('textarea.phast-box-textedit').each(function(){
                        var item = $(this),
                            editor = tinyMCE.editors[item.prop('id')];

                        if(editor) editor.remove();
                        item.tinymce($.extend(
                            {
                                box: root,
                                save_onsavecallback: function(){
                                    formNode.submit();
                                }
                            },
                            tinymceSettings[item.data('mode') || 'default']
                        ));
                    });


                    rootNode.find('input[data-autocomplete]').each(function(){
                        $(this).autocomplete({
                            source: $(this).data('autocomplete')
                        });
                    });


                    rootNode.find('div.phast-box-calendar').each(function(){
                        $(this).find('> input').datepicker(
                            {
                                dateFormat: 'd MM yy',
                                numberOfMonths: 3,
                                showOn: 'button',
                                buttonImage: '/sfPhastPlugin/icons/date.png',
                                buttonImageOnly: true
                            }
                        );
                    });


                    root.populate();
                    serialized = root.serialize();

                    if(root.$afterRender)
                        root.$afterRender.call(root, root, rootNode);

                    execute('afterRender');

                    rootNode.find('input[type=text]:not([readonly]), input[type=password], textarea').eq(0).focus();
                },

                serialize: function(){
                    return formNode.serialize();
                },

                save: function(){
                    if(locked) return;
                    locked = true;
                    loading = true;

                    tinymce.triggerSave();

                    root.loadingAnimate();
                    rootNode.find('form').ajaxSubmit({
                        url: '?phastui&save',
                        type: 'post',
                        data: root.makeQuery(),
                        dataType: 'json',
                        success: function(response, statusText, xhr, $form){
                            locked = false;
                            loading = false;
                            root.loadedAnimate();
                            if(Phast.check(response)){

                                if(response.$success)
                                    alert(response.$success);

                                /**
                                 * Зарефакторить смешевание параметров
                                 */
                                if(response.$parameters)
                                    $.extend(root.parameters, response.$parameters);

                                execute('afterSave');

                                if(response.$documentReload){
                                    document.location.reload();
                                }else{
                                    if(toggleAutoClose || response.$closeBox){
                                        root.close(true);
                                    }else if(!response.$noRefresh){
                                        root.refresh();
                                    }

                                    if(root.list)
                                        root.list.load();
                                }



                            }else{
                                toggleAutoClose = false;
                            }
                        },
                        error: function(xhr, type, exception){
                            locked = false;
                            loading = false;
                            root.loadedAnimate();
                            Phast.error(type, xhr.responseText);
                        }
                    });

                },

                close: function(force){
                    if(root.$closeDisabled && !force)
                        return;

                    if(!force && !loading && root.serialize() !== serialized && !confirm('Вы уверены, что хотите закрыть окно?'))
                        return;

                    if(request)
                        request.abort();

                    root.hide();
                    rootNode.remove();

                    if(root.uri)
                        document.location.hash = '';

                    execute('afterClose');
                },

                show: function(){
                    if(!root.attach)
                        Box.show(root);

                    rootNode.show();
                },

                hide: function(){
                    rootNode.hide();

                    if(!root.attach)
                        Box.hide(root);
                },


                getNode: function(){
                    return rootNode;
                },

                loadingAnimate: function(){
                    loadingNode.show().stop().animate({opacity: 0.5}, 500);

                },

                loadedAnimate: function(){
                    loadingNode.stop().animate({opacity: 0}, 300, function(){
                        $(this).hide();
                    });
                },

                attachList: function(list){
                    lists.push(list);
                },

                getInnerList: function(index){
                    var result, i;
                    if('number' == typeof index){
                        result = lists[index] || null;
                    }else{
                        for(i in lists){
                            if(lists[i].id == index){
                                result = lists[i];
                                break;
                            }
                        }
                    }
                    return result;
                }
            }
        );


        var initialize = function(){
            rootNode = $('<div class="phast-ui phast-box"><form method="post"'+ (root.multipart ? ' enctype="multipart/form-data"' : '') +'>' + (root.template||'Шаблон не найден') + '</form><i class="loading"></i></div>');
            formNode = rootNode.find('> form:eq(0)');
            loadingNode = rootNode.find('> i.loading');

            formNode.on('click', '.phast-box-close', function(event){
                event.preventDefault();
                if(locked) return;
                root.close();
            });

            formNode.on('click', '.phast-box-save', function(event){
                if(event.ctrlKey || event.shiftKey)
                    toggleAutoClose = true;
            });


            formNode.on('keydown', 'input[type=text], input[type=password], textarea', function(event){
                if(event.ctrlKey && event.keyCode == 13){
                    toggleAutoClose = true;
                    formNode.submit();
                    return false;
                }
            });


            formNode.on('click', '.phast-box-choose > input[type=text]', function(event){
                event.preventDefault();
                if(locked) return;

                var node = $(this).parent(),
                    valueNode = node.find('> input[type=hidden]'),
                    header = node.data('header'),
                    list = node.data('list');

                Box.create({
                    $afterOpen: function(){
                        this.attachList($$.List.create(list, {
                            attach: this.getNode().find('div.phast-choosebox-list'),
                            box: this,
                            chooseNode: node,
                            parameters: {
                                pk: root.parameters.pk,
                                relation: valueNode.val()
                            }
                        }));
                    },
                    template: '<div class="phast-box-section"><div class="phast-box-buttons"><button type="button" class="phast-box-close phast-ui-button">Закрыть</button></div>' + (header || 'Выберите элемент') + '</div><div class="phast-choosebox-list"></div>'
                }).open();
            });

            formNode.on('click', '.phast-box-choose > a.clear', function(event){
                event.preventDefault();
                if(locked) return;

                var node = $(this).parent(),
                    valueNode = node.find('> input[type=hidden]'),
                    captionNode = node.find('> input[type=text]'),
                    empty = node.data('empty');

                valueNode.val(null);
                captionNode.val(empty);
            });

            formNode.on('submit', function(event){
                event.preventDefault();
                if(locked) return;
                root.save();
            });

            root.refresh();
        }

        if($.isReady){
            initialize();
        }else{
            $(initialize());
        }

    }

    $.extend(Box, {
        model: {},
        stack: [],
        offset: 100,
        blackout: null,

        get: function(id){
            return Box.model[id];
        },
        register: function(model){
            this.model[model.id] = model;
        },
        create: function(id, options){
            if('string' == typeof(id)){
                return new Box(this.model[id], options);
            }else{
                id.id = 'custom';
                return new Box(id);
            }
        },
        createContainerForList: function(list, title, parameters, model){
            return this.create(
                $.extend({
                        $afterOpen: function(){
                            this.attachList($$.List.create(list, {
                                attach: this.getNode().find("div.phast-custom-list"),
                                box: this,
                                parameters: parameters || {}
                            }));
                        },
                        template: $$.Box.template.listonly(title)
                    },
                    model || {})
            );
        },
        show: function(object){
            var scroll = $(window).scrollTop(),
                depth = this.depth() + (object.depth || 0) + 5;

            this.stack.push({
                object: object,
                depth: depth,
                scroll: scroll
            });

            object.getNode().css({
                zIndex: this.offset + depth - 2,
            });

            this.blackout.css('z-index', this.offset + depth - 3).fadeIn(300);
            this.cascade();

            $(window).scrollTop((this.stack.length-1) * 15);

        },

        hide: function(object){
            var i, current, top;
            for(i in this.stack){
                if(this.stack[i].object === object){
                    current = this.stack[i];
                    top = i == this.stack.length-1;
                    break;
                }
            }

            this.stack.splice(i, 1);

            if(!this.stack.length){
                this.blackout.fadeOut(300);
            }else if(top){
                var depth = this.stack[this.stack.length-1].depth;
                this.blackout.css('z-index', this.offset + depth - 3);
            }else{
                this.cascade();
            }
            $(window).scrollTop(current.scroll);

        },

        closeTop: function(){
            if(this.stack.length)
                this.stack[this.stack.length-1].object.close();
        },

        cascade: function(){
            var item, i = -1, offset;
            while(i++, item = this.stack[i]){
                offset = 20 + 15 * i;
                item.object.getNode().css({
                    top: offset,
                    left: offset,
                    right: offset,
                });
            }
        },

        depth: function(){
            return this.stack.length ? this.stack[this.stack.length - 1].depth : 0;
        },

        template: {
            listonly: function(title){
                return '<div class=\"phast-box-section\"><div class=\"phast-box-buttons\"><button type="button" class=\"phast-box-close phast-ui-button\">Закрыть</button></div>'+title+'</div><div class=\"phast-custom-list\"></div>';
            },
            listcustom: function(title, html){
                return '<div class=\"phast-box-section\"><div class=\"phast-box-buttons\"><button type="button" class=\"phast-box-close phast-ui-button\">Закрыть</button></div>'+title+'</div>'+html;
            }
        }

    });

    Box.createForList = Box.createContainerForList;

    Phast.List = List;
    Phast.Box = Box;
    Phast.ajax = ajax;
    window.phast = window.$$ = Phast;

    var time = (new Date).getTime();
    var file_browser_callback = function(field, url, type, win){
        $$.Box.create('PhastFileBrowser', {depth: 300000, filebrowser: {field: field, url: url, type: type, win: win}}).open();
    };

    var tinymceSettings = {
        default: {
            language : "ru",
            theme : "advanced",
            element_format : "html",
            schema: "html5",
            doctype: '<!DOCTYPE html>',
            plugins : "widget,safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
            dialog_type : "modal",
            file_browser_callback : file_browser_callback,

            font_size_classes : "h",

            theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,sub,sup,|,removeformat,charmap,|,formatselect,styleselect,|,nonbreaking",
            theme_advanced_buttons2 : "tablecontrols,|,bullist,numlist,|,link,unlink,anchor,widget,media",
            theme_advanced_buttons3 : "undo,redo,|,cut,copy,paste,pastetext,pasteword,|,search,replace,|,print,fullscreen,code",
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            theme_advanced_statusbar_location : "none",
            theme_advanced_resizing : false,

            fix_list_elements : true,
            fix_table_elements : true,
            fix_nesting : true,
            convert_urls : false,
            paste_remove_styles: true,

            force_br_newlines : false,
            force_p_newlines : true,

            content_css : "/css/tinymce.css?_=" + time
        }

    };

    tinymceSettings.full = $.extend({}, tinymceSettings.default, {
        theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,forecolor,backcolor,|,fontsizeselect,formatselect,|,cite,abbr,acronym,del,ins,|,styleprops,|,attribs,|,visualchars,nonbreaking,template,pagebreak",
        theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,|,insertdate,inserttime,preview",
        theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,widget,media,advhr,|,print,|,ltr,rtl,|,fullscreen"
    });

    tinymceSettings.simple = $.extend({}, tinymceSettings.default, {
        plugins : "paste,advlink",
        theme_advanced_buttons1 : "bold,italic,underline,removeformat",
        theme_advanced_buttons2 : "",
        theme_advanced_buttons3 : ""
    });

    tinymceSettings.link = $.extend({}, tinymceSettings.simple, {
        theme_advanced_buttons1 : "bold,italic,underline,link,unlink,removeformat"
    });

    tinymceSettings.text = $.extend({}, tinymceSettings.simple, {
        theme_advanced_buttons1 : "bold,italic,underline,link,unlink,bullist,numlist,|,formatselect,styleselect,|,removeformat"
    });

    $(function(){
        Box.blackout = $('<i id="phast-blackout"></i>').on('click', function(){Box.closeTop();}).appendTo('body');


        if(document.location.hash.length > 2){
            var uri = document.location.hash.substring(2).split('/'),
                box = Box.get(uri[0]),
                params = [];

            if(box && box.uri){
                $.each(box.uri, function(i, part){
                    params[part] = uri[i+1];
                });
                Box.create(uri[0], {parameters: params}).open();
            }

        }

        if(Phast.ready) Phast.ready();
    });

})(window);

function transliterate(string){
    var map = {
        'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd',
        'е': 'e', 'ё': 'e', 'ж': 'j', 'з': 'z', 'и': 'i',
        'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n',
        'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't',
        'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'ts', 'ч': 'ch',
        'ш': 'sh', 'щ': 'sch', 'ъ': 'y', 'ы': 'yi', 'ь': '',
        'э': 'e', 'ю': 'yu', 'я': 'ya'
    };

    string = string.toLowerCase();
    string = string.replace(/[а-яё]/g, function(str){
        return map[str] || str;
    });

    string = string.replace(/[ \/\.]/g, '-');
    string = string.replace(/([_-])+/g, '$1');
    string = string.replace(/(^[\s_-]*|[\s_-]*$|[^\w_-])/g, '');

    return string;
}

function transliterateHandler(node){
    var $title = node.find(".phast-box-field-title input"),
        $uri = node.find(".phast-box-field-uri input"),
        uriVal = $uri.val();

    $title.on("keyup", function(){
        if(!uriVal){
            $uri.val(transliterate($title.val()));
        }
    });

    $uri.on("keyup", function(){
        uriVal = $uri.val();
    });
}