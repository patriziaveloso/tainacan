<script>
    /*
    * Vars used along the script
    * */
    var src = $('#src').val();
    var current_meta_type = $("#property_metadata_type").val();
    var $current_meta_form = "#submit_form_property_data_" + current_meta_type;
    var $form_ranking = $("#meta-voting #submit_form_ranking");
    var ranking_types = ["binary", "stars", "like"];

    $("#tainacan-breadcrumbs").show();
    $("#tainacan-breadcrumbs .current-config").text('<?php _e('Metadata','tainacan') ?>');

    $('.property_data_use_filter').click(function() {
        if( $(this).attr('checked') == 'checked') {
            $('div.data-widget').show();
        } else {
            $('div.data-widget').hide();
        }
    });

    // reverse property
    $("#property_object_category_id").change(function (e) {
        $('#show_reverse_properties').hide();
        $('#property_object_is_reverse_false').prop('checked', true);
    });

    // reverse property
    $('#property_object_is_reverse_true').click(function (e) {
        list_reverses();
        $('#show_reverse_properties').show();
    });
    //reverse property
    $('#property_object_is_reverse_false').click(function (e) {
        $('#show_reverse_properties').hide();
    });
    // cardinality type 1
    $('#socialdb_property_term_cardinality_1').click(function (e) {
        $('#socialdb_property_term_widget').html('');
        $('#socialdb_property_term_widget').append('<option value="menu"><?php _e('Menu','tainacan') ?></option>');
        $('#socialdb_property_term_widget').append('<option value="tree"><?php _e('Tree','tainacan') ?></option>');
        $('#socialdb_property_term_widget').append('<option value="radio"><?php _e('Radio','tainacan') ?></option>');
        $('#socialdb_property_term_widget').append('<option value="selectbox"><?php _e('Selectbox','tainacan') ?></option>');
    });
    // cardinality type n
    $('#socialdb_property_term_cardinality_n').click(function (e) {
        $('#socialdb_property_term_widget').html('');
        $('#socialdb_property_term_widget').append('<option value="checkbox"><?php _e('Checkbox','tainacan') ?></option>');
        $('#socialdb_property_term_widget').append('<option value="multipleselect"><?php _e('Multipleselect ','tainacan') ?></option>');
        $('#socialdb_property_term_widget').append('<option value="tree_checkbox"><?php _e('Tree - Checkbox','tainacan') ?></option>');
    });
    $('#socialdb_property_term_cardinality_1').trigger('click');
    $('.edit').click(function (e) {
        var id = $(this).closest('td').find('.post_id').val();
        $.get(src + '/views/ranking/edit.php?id=' + id, function (data) {
            $("#form").html(data).show();
            $("#list").hide();
            $('#create_button').hide();
            e.preventDefault();
        });
        e.preventDefault();
    });
    $('.remove').click(function (e) {
        var id = $(this).closest('td').find('.post_id').val();
        $.get(src + '/views/ranking/delete.php?id=' + id, function (data) {
            $("#remove").html(data).show();
            $("#form").hide(data);
            $("#list").hide();
            $('#create_button').hide();
        });
        e.preventDefault();
    });

    /* Executed by script's start */
    $(function () {
        if ($('#open_wizard').val() == 'true') {
            $('#btn_back_collection').hide();
            $('#submit_configuration').hide();
            $('.back-to-collection').hide();
        } else {
            $('#properties_create_opt').hide();
            $('#categories_title').hide();
            $('#save_and_next').hide();
            $('.back-to-collection').show();
        }
        $('#collection_list_ranking_id').val($('#collection_id').val());
        $('#collection_ranking_type_id').val($('#collection_id').val());
        $('#collection_ranking_id').val( $('#collection_id').val());
        $('#property_term_collection_id').val($('#collection_id').val());
        $('#property_data_collection_id').val($('#collection_id').val());// setando o valor da colecao no formulario
        $('#property_object_collection_id').val($('#collection_id').val());// setando o valor da colecao no formulario

        showPropertyCategoryDynatree(src);
        showTermsDynatree(src);//mostra o dynatree
        list_collection_metadata();
    });


    /**
    ****************************************************************************
    ************************* FACETS FUNCTIONS *********************************
    ****************************************************************************
    **/
    $( "#filters-accordion, #metadata-container" ).sortable({
        cursor: "n-resize",
        connectWith: ".connectedSortable",
        revert: 250,
        helper: "clone",
        // axis: "x",
        //distance: 50,
        //tolerance: "intersect",
        receive: function(event, ui) {
            var $ui_container = ui.item.context.parentNode.id;
            var item_id =  ui.item.context.id;
            var item_search_widget = $("#"+item_id).attr("data-widget") || "tree";
            var is_fixed_meta = $("#"+item_id).hasClass('fixed-meta');
            var $sorter_span = "<span class='glyphicon glyphicon-sort sort-filter'></span>";

            if ( $ui_container === "filters-accordion" ) {
                $("#filters-accordion").addClass("receiving-metadata");
                $( "#" + item_id + " .action-icons").append( $sorter_span );

                if ( is_fixed_meta ) {
                    setCollectionFacet("add", item_id, "tree");
                } else {
                    setCollectionFacet( "add", item_id, item_search_widget );
                }

            } else if ( $ui_container === "metadata-container" ) {
                $(ui.item.context).addClass('hide');
            }
        },
        remove: function(event, ui) {
            var $ui_container = ui.item.context.parentNode.id;
            if ( $ui_container === "metadata-container" ) {
                removeFacet(ui.item.context.id);
            }
        },
        stop: function(event, ui) {
            var $ui_container = ui.item.context.parentNode.id;
            var sortedIds = $("#filters-accordion").sortable("toArray");
            $("#filters-accordion").removeClass("adding-meta");

            if ( $ui_container === "filters-accordion" ) {
                updateFacetPosition(sortedIds);
            }
            $("#metadata-container").removeClass("change-meta-container");
        },
        sort: function(event, ui) {
            $("#filters-accordion").addClass("adding-meta");
            var filtros_atuais = get_current_filters();

            if ( ui.item.context.parentNode.id === "metadata-container" ) {
                $("#metadata-container").addClass("change-meta-container");
            }
        }

    }).disableSelection();

    $("#filters-accordion").sortable("option", "placeholder", "testclass");

    function add_facets() {
        var selKeys = $.map($("#categories_dynatree").dynatree("getSelectedNodes"), function (node) {
            return node.data.key;
        });
        var selectedCategories = selKeys.join(",");
        $.ajax({
            type: "POST",
            url: $('#src').val() + "/controllers/category/category_controller.php",
            data: {collection_id: $('#category_collection_id').val(), operation: 'vinculate_facets', facets: selectedCategories}
        }).done(function (result) {
            $('.dropdown-toggle').dropdown();
            $("#categories_dynatree").dynatree("getTree").reload();
            elem = jQuery.parseJSON(result);

            getRequestFeedback(elem.success);
        });
    }

    function setCollectionFacet( operation, metadata_id, metadata_widget, color_facet, counter_obj, menu_style_id ) {
        var collection_id = $("#property_data_collection_id").val();
        var meta_id;

        if ( typeof metadata_id != 'undefined' ) {

            if ( typeof metadata_id == "number" ) {
                meta_id = metadata_id.toString();
            } else {
                if ( metadata_id.indexOf("socialdb_") != -1 ) {
                    meta_id = metadata_id;
                } else if (metadata_id.indexOf("meta-item") === -1 ) {
                    meta_id = metadata_id;
                } else {
                    meta_id = metadata_id.replace("meta-item-", "");
                }
            }

            var cf = color_facet || "color_property2";
            var search_data_widget = metadata_widget || "tree";

            var data = {};
            if ( counter_obj ) {
                var item_counter_range = counter_obj.counter_range || "0";
                data = counter_obj.sent_data;
            } else if( search_data_widget == "menu" && menu_style_id ) {
                data = { collection_id: collection_id, search_data_widget: search_data_widget, operation: operation, select_menu_style: menu_style_id };
            } else {
                data = { collection_id: collection_id, search_data_widget: search_data_widget, operation: operation };
            }

            data.property_id = meta_id;
            data.color_facet = cf;
            data.search_add_facet = meta_id;
            data.search_data_orientation = "left-column";

            $.ajax({ url: src + '/controllers/search/search_controller.php', type: 'POST', data: data }).done(function(r){
                list_collection_facets();
            });
        }
    }

    function removeFacet(item_id) {
        var collection_id = $("#collection_id").val();
        $.ajax({
            type: "POST",
            url: src + "/controllers/search/search_controller.php",
            data: { operation: 'delete_facet', facet_id: item_id, collection_id: collection_id }
        });
    }

    function updateFacetPosition(arrFacets) {
        var prepared_arr = [];
        $.each( arrFacets, function(idx, el) { prepared_arr[prepared_arr.length] = [el, idx]; });
        $.ajax({
            url: src + '/controllers/search/search_controller.php',
            type: 'POST',
            data: {arrFacets: prepared_arr, operation: 'save_new_priority', collection_id: $('#collection_id').val()}
        });
    }

    function list_collection_facets() {
        $.ajax({
            url: src + '/controllers/search/search_controller.php',
            type: 'POST',
            data: {operation: 'list_facets', collection_id: $('#collection_id').val()},
            success: function (data) {
                if (data) {
                    $("#filters-accordion").html('');
                    var facetsObj = $.parseJSON(data);

                    if (facetsObj && facetsObj != null) {
                        $.each(facetsObj, function(index, el) {
                            if ( el.nome == null && el.id != "tree" ) {
                                removeFacet(el.id);
                            } else {
                                var current_prop = getPropertyType(el.prop);

                                var item_html = '<li id="'+ el.id +'" data-widget="'+el.widget+'" class="form-group metadata-facet filter-'+el.id+'">' +
                                    '<label class="title-pipe">' + el.nome + '<div class="pull-right">';

                                if ( current_prop == "data" ) {
                                    item_html += '<a onclick="edit_metadata('+ el.id +')" class="edit-filter"><span class="glyphicon glyphicon-edit"></span></a>';
                                } else if(current_prop == "object") {
                                    item_html += '<a onclick="edit_object('+ el.id +')" class="edit-filter"><span class="glyphicon glyphicon-edit"></span></a>';
                                }
                                else if ( current_prop == "ranking_stars" || current_prop == "ranking_binary" || current_prop == "ranking_like" ) {
                                    item_html += '<a onclick="edit_ranking('+ el.id + ')" class="edit-filter"><span class="glyphicon glyphicon-edit"><span></a>';
                                } else if (current_prop == "term") {
                                    item_html += '<a onclick="edit_term('+ el.id +')" class="edit-filter"><span class="glyphicon glyphicon-edit"></span></a>';
                                } else if( el.id == "tag" ) {
                                    item_html += '<a onclick="edit_tag(this)" class="'+ el.id +'" data-filter="true" data-title="'+el.nome+'"  class="edit-filter"><span class="glyphicon glyphicon-edit"></span></a>';
                                } else {
                                    if ( (el.id).indexOf("socialdb_") == 0 ) {
                                        item_html += '<a onclick="edit_filter(this)" class="'+ el.id +'" data-filter="true" data-title="'+el.nome+'" class="edit-filter"><span class="glyphicon glyphicon-edit"></span></a>';
                                    }
                                }

                                item_html += '<span class="glyphicon glyphicon-sort sort-filter"></span></div> </label></li>';

                                $("#filters-accordion").append( item_html );
                            }
                        });
                    }
                }
            }
        });
    }

    function getPropertyType(property) {
        if ( null !== property ) {
            return prop_type = property.replace("socialdb_property_", "");
        }
    }

    function get_edit_box(propType, propId) {
        var property = getPropertyType(propType);

        if ( property == 1 ) {
            return edit_metadata(propId);
        } else if ( property == 2 ) {
            return edit_object(propId);
        } else if ( property == undefined ) {
            return "fazer mais um check aqui";
        }
    }

    function changeFacetPosition(tableID) {
        var arrFacets = [];
        var facet_priority = tableID + " .facet-priority";
        $(facet_priority).each(function (idx, el) {
            console.log( $(el).text() );
            count = $(this).parent().children().index($(this)) + 1;
            var input_id = $(this).find("input[class='find_facet']").attr('id') + '';
            if (input_id != 'undefined') {
                var facet_id = input_id.split('_')[1];
                arrFacets.push([facet_id, count]);
                var html_insert = count + "<input class='find_facet' type='hidden' id='position_" + facet_id + "' value='" + facet_id + "_" + count + "' />";
                $(this).find('.priority-left').html(html_insert);
            }
        });

        var filters_num = $("#filters-accordion label").length;
        if ( filters_num > 1 ) {
        } else {
        }

        $.ajax({
            url: src + '/controllers/search/search_controller.php',
            type: 'POST',
            data: {arrFacets: arrFacets, operation: 'save_new_priority', collection_id: $('#collection_id').val()}
        });
    }


    /**
     ****************************************************************************
     ************************* PROPERTY DATA FUNCTIONS *********************************
     ****************************************************************************
     **/
    $('form.form_property_data').each(function(idx, el) {
        $(el).submit(function(e) {
            e.preventDefault();
            var current_data = $(el).serialize();
            var path = src + '/controllers/property/property_controller.php';

            $.ajax({
                url: path,
                data: current_data,
                processData: false
            }).done(function(result) {
                elem = $.parseJSON(result);

                var current_operation = elem.operation;
                var current_modal = "#meta-" + elem.property_data_widget;
                var new_property_id = elem.new_property_id;
                var current_property_id = elem.property_data_id;
                var property_widget = elem.search_data_widget;
                var color_facet = elem.color_facet;
                var collection_id = $('#collection_id').val();

                if ( elem.property_data_use_filter == "use_filter" )  {
                    if (current_operation == "add_property_data") {
                        setCollectionFacet( "add", new_property_id, property_widget, color_facet );
                    } else if( current_operation == "update_property_data" ) {
                        setCollectionFacet( "update", current_property_id, property_widget, color_facet );
                    }
                }

                add_property_data_ordenation(collection_id, new_property_id);

                $(current_modal).modal('hide');

                list_collection_metadata();
                getRequestFeedback(elem.type, elem.msg);
            });
        })
    });

    function add_property_data_ordenation( collection_id, property_id ){
        $.ajax({
            type: "POST",
            url: $('#src').val() + "/controllers/search/search_controller.php",
            data: { collection_id: collection_id, property_id: property_id, operation: 'add_property_ordenation' }
        }).done(function (result) {
            // $('#collection_order').html('');
            elem = jQuery.parseJSON(result);

            // return false;
            // list_m_ordenation();
            // list_properties_data_ordenation();
            // list_properties_data_selected_ordenation();

            showAlertGeneral(elem.title, elem.msg, elem.type);
        });
    }

    function edit_metadata(id) {
        $.ajax({
            url: $('#src').val() + '/controllers/property/property_controller.php',
            type: 'POST',
            data: {collection_id: $("#collection_id").val(), operation: 'edit_property_data', property_id: id}
        }).done(function (result) {
            elem = jQuery.parseJSON(result);

            if (elem) {
                var current_filters = $("#filters-accordion li"),
                    filters_ids = [],
                    current_widget = elem.metas.socialdb_property_data_widget;

                var item_required = $.parseJSON(elem.metas.socialdb_property_required);
                var meta_widget = "#meta-" + current_widget;

                $( meta_widget ).modal('show');

                if( $("#meta-item-"+id).hasClass('root_category') ) {
                    $( meta_widget + " .metadata-common-fields").hide();
                } else {
                    $( meta_widget + " .metadata-common-fields").show();
                }

                var nome = elem.name;
                var meta_help = elem.metas.socialdb_property_help;
                var default_value = elem.metas.socialdb_property_default_value;
                var operation = 'update_property_data';
                var search_widget = elem.search_data_widget;
                var facet_widget = $("#meta-item-"+id).attr('data-widget');

                $(current_filters).each(function(idx, el) {
                    filters_ids.push($(el).attr('id'));
                });

                var formatted_id = "" + id + "";
                if ( $.inArray(formatted_id, filters_ids) > -1 ) {
                    var use_filter = "use_filter";
                    $( meta_widget + " .form_property_data .property_data_use_filter").prop("checked", true);
                    $( meta_widget + " .form_property_data .data-widget").show();
                }

                if ( use_filter === "use_filter" ) {
                    var current_data_widget = $("#meta-item-"+id).attr('data-widget');
                    $( meta_widget + " .form_property_data #search_data_widget").val( current_data_widget );
                }

                $(meta_widget + " .form_property_data #search_add_facet").val(id);

                $( meta_widget + " .form_property_data #property_data_id").val(id);
                $( meta_widget + " .form_property_data #property_data_name").val(nome);
                $( meta_widget + " .form_property_data #socialdb_property_data_help").val(meta_help);
                $( meta_widget + " .form_property_data #socialdb_property_data_default_value").val(default_value);
                $( meta_widget + " .form_property_data #operation_property_data").val(operation);

                if (item_required) {
                    $( meta_widget + " .form_property_data #property_data_required_true").prop('checked', true);
                    $( meta_widget + " .form_property_data #property_data_required_false").removeAttr('checked');
                } else {
                    $( meta_widget + " .form_property_data #property_data_required_false").prop('checked', true);
                }
                $( meta_widget + " h4.modal-title").text('<?php _e('Edit property','tainacan') ?> - ' +  current_widget );


                if ( facet_widget != null && facet_widget != undefined ) {
                    $( meta_widget + " .form_property_data select#search_data_widget").focus().val( facet_widget );
                }

                $("#property_data_widget").val(elem.metas.socialdb_property_data_widget);
            }

        });
    }

    function list_property_data() {
        $.ajax({
            type: "POST",
            url: $('#src').val() + "/controllers/property/property_controller.php",
            data: {collection_id: $('#collection_id').val(), operation: 'list_property_data', category_id: $('#property_category_id').val()}
        }).done(function (result) {
            elem = jQuery.parseJSON(result);
            list_collection_facets();

            if (elem.no_properties !== true) {
                $.each(elem.property_data, function (idx, property) {
                    var current_id = property.id;

                    if ( property.metas.is_repository_property && property.metas.is_repository_property === true ||
                        (property.metas.socialdb_property_created_category && $('#property_category_id').val() !== property.metas.socialdb_property_created_category) ) {
                        $('ul#metadata-container').append(
                            '<li id="meta-item-' + current_id + '" data-widget="' + property.search_widget + '" class="root_category ui-widget-content ui-corner-tr">' +
                            '<label class="title-pipe">' + property.name + '</label>' +
                            '<a onclick="edit_metadata(' + current_id + ')" class="edit_property_data" href="javascript:void(0)">' +
                            '<div class="action-icons"> <span class="glyphicon glyphicon-edit"></span></a> ' +
                            '<span class="glyphicon glyphicon-trash no-edit"></span></div></li>');
                    } else {
                        if ( $.inArray(property.type, ranking_types) == -1 ) {
                            $('ul#metadata-container').append(
                                '<li id="meta-item-' + current_id + '" data-widget="' + property.search_widget + '" class="ui-widget-content ui-corner-tr"><label class="title-pipe">' + property.name +
                                '</label><div class="action-icons">' +
                                '<a onclick="edit_metadata(' + current_id + ')" class="edit_property_data" href="javascript:void(0)">' +
                                '<span class="glyphicon glyphicon-edit"><span></a> ' +
                                '<input type="hidden" class="property_id" value="' + property.id + '">' +
                                '<input type="hidden" class="property_name" value="' + property.name + '">' +
                                '<a onclick="delete_property(' + current_id + ',' + 1 + ')" class="delete_property" href="#">' +
                                '<span class="glyphicon glyphicon-trash"><span></a></div></li>');
                        }
                    }
                });
            }
        });
    }

    function edit_filter(item) {
        var item_id = $(item).attr('class');
        var item_title = $(item).attr('data-title');
        var is_filter = $(item).attr('data-filter');

        var cfs = get_current_filters();

        if( is_metadata_filter( item_id ) ) {
            $("#meta-filter .operation").val('update');
            $("#meta-filter .property_data_use_filter").prop('checked', true);
            $("#meta-filter .data-widget").show();
            // $("#meta-filter #color_field_property_search").show();
        } else {
            $("#meta-filter .data-widget").hide();
            $("#meta-filter .property_data_use_filter").prop('checked', false);
        }

        $("#meta-filter .tainacan-filter-title").text( item_title );
        $("#meta-filter #search_add_facet").val( item_id );
        $("#meta-filter").modal('show');
    }

    $("#submit_form_filter").submit(function(e) {
        e.preventDefault();
        var id_correto = $("#meta-filter #search_add_facet").val();
        var cor_faceta = $('input[name=color_facet]:checked', "#submit_form_filter").val();
        var use_filter = $("#meta-filter .property_data_use_filter").prop('checked');
        var operation = $("#meta-filter .operation").val();

        if (use_filter) {
            if ( operation == "update" ) {
                setCollectionFacet(operation, id_correto, "tree", cor_faceta);
            } else {
                setCollectionFacet("add", id_correto, "tree", cor_faceta);
            }
        } else {
            removeFacet(id_correto);
            $("#meta-filter .operation").val('');
        }

        $("#meta-filter").modal('hide');
        list_collection_facets();
    });


    function edit_tag(item) {
        var item_id = $(item).attr('class');
        var item_title = $(item).attr('data-title');
        var is_filter = $(item).attr('data-filter');

        if (is_filter) {
            $("#meta-tag .property_data_use_filter").prop('checked', true);
            $("#meta-tag .data-widget").show();
        }

        $("#meta-tag .tainacan-filter-title").text( item_title );
        $("#meta-tag #search_add_facet").val( item_id );
        $("#meta-tag").modal('show');
    }

    $("#submit_form_tag").submit(function(e) {
        e.preventDefault();
        var id_correto = $("#meta-tag #search_add_facet").val();
        var data_widget = $("#meta-tag #search_data_widget").val();

        $("#meta-tag").modal('hide');
        var use_filter = $("#meta-tag .property_data_use_filter").prop('checked');

        if (use_filter) {
            setCollectionFacet( "add", id_correto, data_widget);
        } else {
            removeFacet(id_correto);
        }

        list_collection_facets();
    });

    function edit(id, operation) {
        $.ajax({
            url: $('#src').val() + '/controllers/property/property_controller.php',
            type: 'POST',
            data: { collection_id: $("#collection_id").val(), operation: operation, property_id: id }
        }).done(function(result) {
            elem = $.parseJSON(result);
        });
    }

    function delete_property(id, type) {
        $("#property_delete_collection_id").val( $("#collection_id").val() );
        var name_html = $("#meta-item-" + id + " .property_name").val();
        $("#property_delete_id").val( id );
        $("#modal_remove_property #property_category_id").val( $("#property_category_id").val() );

        $("#type").val(type);

        $("#deleted_property_name").text(name_html);
        $("#modal_remove_property").modal('show');
    }

    function list_reverses(selected) {
        $.ajax({
            url: $('#src').val() + '/controllers/property/property_controller.php',
            type: 'POST',
            data: {collection_id: $("#collection_id").val(), category_id: $("#property_object_category_id").val(), operation: 'show_reverses', property_id: $('#property_category_id').val()}
        }).done(function (result) {
            elem = jQuery.parseJSON(result);
            $('#property_object_reverse').html('');
            if (elem.no_properties === false) {
                $.each(elem.property_object, function (idx, property) {
                    if (property.id == selected) {
                        $('#property_object_reverse').append('<option selected="selected" value="' + property.id + '">' + property.name + ' - (' + property.type + ')</option>');
                    } else {
                        $('#property_object_reverse').append('<option value="' + property.id + '">' + property.name + ' - (' + property.type + ')</option>');
                    }
                });
            } else {
                $('#property_object_reverse').append('<option value="false"><?php _e('No properties added','tainacan'); ?></option>');
            }
        });
    }

    /**
     ****************************************************************************
     ************************* PROPERTY OBJECT FUNCTIONS ************************
     ****************************************************************************
     **/
    $('#submit_form_property_object').submit(function (e) {
        e.preventDefault();
        $('#modalImportMain').modal('show');

        $.ajax({
            url: src + '/controllers/property/property_controller.php',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false
        }).done(function (result) {
            $('#modalImportMain').modal('hide');
            elem = jQuery.parseJSON(result);
            $("#meta-relationship").modal('hide');

            var current_operation = elem.operation;
            if ( elem.property_data_use_filter == "use_filter" ) {
                if ( current_operation == "add_property_object" ) {
                    setCollectionFacet("add", elem.results.new_property_id, elem.search_data_widget, elem.color_facet );
                } else if (current_operation == "update_property_object") {
                    setCollectionFacet("update", elem.property_object_id, elem.search_data_widget, elem.color_facet );
                }
            }

            list_collection_metadata();
            getRequestFeedback(elem.type, elem.msg);
        });
    });

    $('ul.add-property-dropdown a').on('click', function() {
        clear_buttons();
    });

    function list_property_object() {
        $.ajax({
            type: "POST",
            url: $('#src').val() + "/controllers/property/property_controller.php",
            data: {collection_id: $('#collection_id').val(), operation: 'list_property_object', category_id: $('#property_category_id').val()}
        }).done(function (result) {
            elem = jQuery.parseJSON(result);
            if (elem && elem.no_properties !== true) {
                $('#no_properties_object').hide();
                $('#table_property_object').html('');
                $.each(elem.property_object, function (idx, property) {
                    var current_id = property.id;

                    if (property.metas.is_repository_property && property.metas.is_repository_property === true ||
                        (property.metas.socialdb_property_created_category && $('#property_category_id').val() !== property.metas.socialdb_property_created_category)) {
                        $('ul#metadata-container').append(
                            '<li id="meta-item-'+current_id+'" data-widget="' + property.search_widget + '" class="root_category ui-widget-content ui-corner-tr">' +
                            '<label class="title-pipe">' + property.name + '</label>' +
                            '<a onclick="edit_object('+ current_id +')" class="edit_property_data" href="javascript:void(0)">' +
                            '<div class="action-icons default-metadata"><span class="glyphicon glyphicon-edit"><span></a> ' +
                            ' <span class="glyphicon glyphicon-trash no-edit"><span> </div></li>' );
                    } else {
                        if ( $.inArray(property.type, ranking_types) == -1 ) {
                            $('ul#metadata-container').append(
                                '<li id="meta-item-'+current_id+'" data-widget="' + property.search_widget + '" class="ui-widget-content ui-corner-tr"><label class="title-pipe">' + property.name +
                                '</label><div class="action-icons">' +
                                '<a onclick="edit_object('+ current_id +')" class="edit_property_data" href="javascript:void(0)">' +
                                '<span class="glyphicon glyphicon-edit"><span></a> ' +
                                '<input type="hidden" class="property_object_id" value="' + current_id + '">' +
                                '<input type="hidden" class="property_name" value="' + property.name + '">' +
                                '<a onclick="delete_property('+ current_id + ',' + 2 + ')" class="delete_property" href="#">' +
                                '<span class="glyphicon glyphicon-trash"><span></a></div></li>');
                        }
                    }
                });
            }

        });
    }

    function get_current_filters() {
        var current_filters = $("#filters-accordion li");
        var filters_data = [];

        $(current_filters).each(function(idx, el) {
            filters_data.push( { id:  $(el).attr('id'), widget: $(el).attr('data-widget') });
        });

        return filters_data;
    }

    function is_metadata_filter(meta_id) {
        var current_filters = $("#filters-accordion li");
        var filters_ids = [];
        $(current_filters).each(function(idx, el) { filters_ids.push( $(el).attr('id') ); });

        var formatted_id = meta_id.toString();
        if ( $.inArray( formatted_id, filters_ids ) > -1 ) {
            return true;
        }
    }

    function get_metadata_widget(meta_id) {
        var formatted_id = meta_id.toString();
        return $("#filters-accordion li#" + formatted_id).attr("data-widget");
    }

    function edit_object(id) {
        $.ajax({
            url: $('#src').val() + '/controllers/property/property_controller.php',
            type: 'POST',
            data: {collection_id: $("#collection_id").val(), operation: 'edit_property_object', property_id: id}
        }).done(function (result) {
            elem = jQuery.parseJSON(result);

            if ( is_metadata_filter( elem.id ) ) {
                var use_filter = "use_filter";
                $( "#meta-relationship .property_data_use_filter").prop("checked", true);
                $( "#meta-relationship .data-widget").show();
                var meta_widget = get_metadata_widget(elem.id);
                $("#meta-relationship #search_data_widget").focus().val( meta_widget );
            }

            if( $("#meta-item-"+id).hasClass('root_category') ) {
                $( "#meta-relationship .metadata-common-fields").hide();
            } else {
                $( "#meta-relationship .metadata-common-fields").show();
            }

            $("#meta-relationship").modal('show');
            var related_collection = elem.metas.socialdb_property_object_category_id;

            if ( related_collection != null ) {
                $("#property_object_category_id").val(related_collection);
            }

            $("#property_object_title").text('<?php _e('Edit property','tainacan') ?>');
            $("#property_object_id").val(elem.id);
            $("#property_object_name").val(elem.name);

            if (elem.metas.socialdb_property_object_is_facet === 'false') {
                $("#property_object_facet_false").prop('checked', true);
            } else {
                $("#property_object_facet_true").prop('checked', true);
            }
            if (elem.metas.socialdb_property_object_is_reverse === 'false') {
                $("#property_object_is_reverse_false").prop('checked', true);
                $('#show_reverse_properties').hide();
            } else {
                $("#property_object_is_reverse_true").prop('checked', true);
                list_reverses(elem.metas.socialdb_property_object_reverse);
                $('#show_reverse_properties').show();
            }
            if (elem.metas.socialdb_property_required === 'false') {
                $("#property_object_required_false").prop('checked', true);
            } else {
                $("#property_object_required_true").prop('checked', true);
            }
            $("#operation_property_object").val('update_property_object');

            getRequestFeedback(elem.type, elem.msg);
        });
    }

    /**
     ****************************************************************************
     ************************* PROPERTY TERMS FUNCTIONS *************************
     ****************************************************************************
     **/
    $('#submit_form_property_term').submit(function (e) {
        e.preventDefault();
        $.ajax({
            url: src + '/controllers/property/property_controller.php',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false
        }).done(function (result) {
            elem = jQuery.parseJSON(result);
            cl(elem);

            var current_operation = elem.operation;
            var menu_style_id = elem.select_menu_style;
            var term_root_id = elem.socialdb_property_term_root;
            if ( elem.property_data_use_filter == "use_filter" ) {
                if ( current_operation == "add_property_term" ) {
                    setCollectionFacet("add", term_root_id, elem.socialdb_property_term_widget, elem.color_facet, "", menu_style_id );
                } else if (current_operation == "update_property_term") {
                    setCollectionFacet("update", term_root_id, elem.socialdb_property_term_widget, elem.color_facet, "", menu_style_id );
                }
            }
        
            $("#meta-category").modal('hide');
            list_collection_metadata();
            getRequestFeedback(elem.type, elem.msg);
        });
    });

    function list_property_terms() {
        $.ajax({
            type: "POST",
            url: $('#src').val() + "/controllers/property/property_controller.php",
            data: {collection_id: $('#collection_id').val(), operation: 'list_property_terms', category_id: $('#property_category_id').val()}
        }).done(function (result) {
            elem = jQuery.parseJSON(result);

            if (elem && elem.no_properties !== true) {
                $.each(elem.property_terms, function (idx, property) {

                    var current_id = property.id;

                    var repository_property = property.metas.is_repository_property;
                    var created_category = property.metas.socialdb_property_created_category;

                    if ( repository_property === true || created_category && created_category !== $("#property_category_id").val() ) {
                        $('ul#metadata-container').append(
                            '<li id="meta-item-' + current_id + '" data-widget="' + property.search_widget + '" class="root_category ui-widget-content ui-corner-tr"><label class="title-pipe">' + property.name +
                            '</label><div class="action-icons">' +
                            '<a onclick="edit_term(' + current_id + ')" class="edit_property_data" href="javascript:void(0)">' +
                            '<span class="glyphicon glyphicon-edit"><span></a> ' +
                            '<span class="glyphicon glyphicon-trash no-edit"><span></div></li>');
                    } else {
                        if ( $.inArray(property.type, ranking_types) == -1 ) {
                            $('ul#metadata-container').append(
                                '<li id="meta-item-' + current_id + '" data-widget="' + property.search_widget + '" class="ui-widget-content ui-corner-tr"><label class="title-pipe">' + property.name +
                                '</label><div class="action-icons"> <input type="hidden" class="property_data_id" value="' + current_id + '">' +
                                '<a onclick="edit_term(' + current_id + ')" class="edit_property_data" href="javascript:void(0)">' +
                                '<span class="glyphicon glyphicon-edit"><span></a> ' +
                                '<a onclick="delete_property(' + current_id + ',' + 3 + ')" class="delete_property" href="#">' +
                                '<span class="glyphicon glyphicon-trash"><span></a></div></li>');
                        }
                    }

                });
            }
        });
    }
    function edit_term(id) {
        $.ajax({
            url: $('#src').val() + '/controllers/property/property_controller.php',
            type: 'POST',
            data: {collection_id: $("#collection_id").val(), operation: 'edit_property_term', property_id: id}
        }).done(function (result) {
            elem = $.parseJSON(result);

            $("#meta-category").modal('show');
            cl(elem);

            if( $("#meta-item-"+id).hasClass('root_category') ) {
                $( "#meta-category .metadata-common-fields").hide();
            } else {
                $( "#meta-category .metadata-common-fields").show();
            }

            $("#property_term_id").val(elem.id);
            $("#property_term_name").val(elem.name);

            if (elem.type == "menu") {
                $("#select_menu_style").show();
                $('.select2-menu').select2('data', { id: elem.chosen_menu_style_id, text: "(Estilo selecionado)" } );
            }

            $("#meta-category .modal-title .edit").text('<?php _e('Edit property','tainacan') ?>');

            $("#meta-category #property_term_name").val( elem.name );
            $("#meta-category #socialdb_property_help").val( elem.metas.socialdb_property_help );

            if (elem.metas.socialdb_property_term_cardinality === '1') {
                $('#meta-category #socialdb_property_term_cardinality_1').prop('checked', true);
            } else {
                $("#meta-category #socialdb_property_term_cardinality_n").prop('checked', true);
            }


            if (elem.metas.socialdb_property_required === 'false') {
                $("#property_term_required_false").prop('checked', true);
            } else {
                $("#property_term_required_true").prop('checked', true);
            }
            if(elem.metas.socialdb_property_help){
                $("#socialdb_property_help").val(elem.metas.socialdb_property_help);
            }

            if (elem.metas.socialdb_property_term_root) {
                get_category_root_name(elem.metas.socialdb_property_term_root);
            }
            $("#socialdb_property_term_widget").val(elem.metas.socialdb_property_term_widget);

            $("#operation_property_term").val('update_property_term');
        });
    }

    function toggle_term_widget(el) {
        if (el.checked) {
            $("#meta-category .term-widget").show();
        } else {
            $("#meta-category .term-widget").hide();
        }
    }
    function term_widget_options(el) {
        var curr_val = $(el).val();

        if (curr_val == "tree") {
            $("#meta-category #color_field_property_search").fadeIn();
            $("#meta-category #select_menu_style").hide();
        } else if ( curr_val == "menu") {
            $("#meta-category #select_menu_style").fadeIn();
            $("#meta-category #color_field_property_search").hide();
        } else {
            $("#meta-category #color_field_property_search").fadeOut();
            $("#meta-category #select_menu_style").fadeOut();
        }
    }


    /**
     ****************************************************************************
     ************************* RANKING FUNCTIONS ********************************
     ****************************************************************************
     **/
    $( $form_ranking ).submit( function( e ) {
        e.preventDefault();
        $.ajax( {
            url: src+'/controllers/ranking/ranking_controller.php',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false
        }).done(function( result ) {
            elem  = $.parseJSON(result);
        
            var current_operation = elem.operation;
            var new_ranking_id = elem.new_ranking_id;
            var ranking_widget = elem.search_data_widget;
            var counter_range = elem.counter_range;

            var range_obj = { counter_range: counter_range, sent_data: elem };            

            if ( elem.property_data_use_filter === "use_filter" ) {
                if ( current_operation == "add" ) {
                    setCollectionFacet("add", new_ranking_id, ranking_widget, "", range_obj);
                } else if (current_operation == "edit") {
                    setCollectionFacet("update", elem.ranking_id, ranking_widget);
                }
            }

            document.getElementById('submit_form_ranking').reset();
            $('#meta-voting').modal('hide');
            getRequestFeedback(elem.success, elem.msg);
            list_collection_metadata();

        });
    });

    $('#submit_delete_ranking').submit(function (e) {
        e.preventDefault();
        $("#modal_remove_ranking").modal('hide');
        $('#modalImportMain').modal('show');
        $.ajax({
            url: src + '/controllers/ranking/ranking_controller.php',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false
        }).done(function (result) {
            $('#modalImportMain').modal('hide');
            elem = jQuery.parseJSON(result);

            getRequestFeedback(elem.success, elem.msg);
            list_collection_metadata();
        });

    });

    function list_ranking() {
        $.ajax({
            type: "POST",
            url: $('#src').val() + "/controllers/ranking/ranking_controller.php",
            data: {collection_id: $('#collection_list_ranking_id').val(), operation: 'list_ranking'}
        }).done(function (result) {
            elem = $.parseJSON(result);

            if (elem.no_properties !== true) {

                $.each(elem.rankings, function (idx, ranking) {
                    if (ranking.metas.socialdb_property_created_category==elem.category_root) {
                        var current_id = ranking.id;
                        var current_title = ranking.name;

                        if (ranking.range_options !== false ) {
                            $.each(ranking.range_options, function (key, value) {
                                increase_range();
                                $('#submit_form_ranking #range_' + $('#counter_range').val() + '_1').val(value.value_1);
                                $('#submit_form_ranking #range_' + $('#counter_range').val() + '_2').val(value.value_2);
                            });
                        }

                        $('ul#metadata-container').append(
                            '<li id="meta-item-'+current_id+'" data-widget="' + ranking.search_widget + '" class="ui-widget-content ui-corner-tr"><label class="title-pipe">' + current_title +
                            '</label><div class="action-icons"> <input type="hidden" class="property_data_id" value="'+ current_id +'">' +
                            '<a onclick="edit_ranking('+ current_id + ')" class="edit_ranking" href="javascript:void(0)">' +
                            '<span class="glyphicon glyphicon-edit"><span></a> ' +
                            '<input type="hidden" class="ranking_id" value="' + current_id + '">' +
                            '<input type="hidden" class="ranking_name" value="' + current_title + '">' +
                            '<a onclick="delete_ranking(' + current_id + ')" class="delete_ranking" href="#">' +
                            '<span class="glyphicon glyphicon-trash"><span></a></div></li>');
                    }
                });
            }
        });
    }

    function delete_ranking(ranking_id) {
        var ranking_title = $("#meta-item-" + ranking_id + " .title-pipe").text();
        $("#ranking_delete_collection_id").val($("#collection_id").val());
        $("#ranking_delete_id").val(ranking_id);
        $("#deleted_ranking_name").text(ranking_title);
        $("#modal_remove_ranking").modal('show');
    }

    function edit_ranking(element) {
        var id = element;
        var collection_id = $('#collection_list_ranking_id').val();

        $("#meta-voting span.ranking-action").text('<?php _e('Edit', 'tainacan') ?>');

        $.ajax({
            type: "POST",
            url: src + "/controllers/ranking/ranking_controller.php",
            data: { collection_id: $('#collection_list_ranking_id').val(), ranking_id: id, operation: "edit_ranking" }
        }).done(function (result) {
            elem = $.parseJSON(result);

            var item_type = elem.ranking.type;
            $("#submit_form_ranking .ranking-type").focus().val(item_type);

            if ( is_metadata_filter(elem.ranking_id) ) {
                var use_filter = "use_filter";
                $("#submit_form_ranking .property_data_use_filter").prop('checked', true);
                $("#submit_form_ranking .use-voting-filter").show();

                define_voting_widget(item_type);
            }

            $("#submit_form_ranking #range_submit").show();

            $("#meta-voting").modal('show');

            var nome = elem.ranking.name;
            var operation = "edit";
            var ranking_type = elem.ranking.type;
            var collection_ranking_id = elem.ranking.id;

            $("#submit_form_ranking #ranking_name").val(nome);
            $("#submit_form_ranking #ranking_id").val(collection_ranking_id);
            $("#submit_form_ranking #ranking_type").val(ranking_type);
            $("#submit_form_ranking #operation").val(operation);

        });
    }

    function increase_range() {
        var count = $('#counter_range').val();
        $('#counter_range').val( parseInt(count) + 1 );

        $.ajax({
            type: "POST",
            async: false,
            url: $('#src').val() + "/controllers/search/search_controller.php",
            data: {
                collection_id: $('#collection_id').val(),
                facet_id: $('#submit_form_ranking #ranking_id').val(),
                counter: $('#counter_range').val(),
                operation: 'append_range'}
        }).done(function (result) {
            $('#submit_form_ranking #range_form').append(result);
        });
    }

    /**
     ****************************************************************************
     ************************* GENERAL META FUNCTIONS ***************************
     ****************************************************************************
     **/
    function load_menu_style_data() {
        $.ajax({
            type: "POST",
            async: false,
            url: src + "/controllers/search/search_controller.php",
            data: { operation: 'get_menu_ids' }
        }).error(function() {
            cl('<?php _e("Something went wrong. Try again later.", "tainacan") ?>');
        }).done(function(result) {
            var menu_item = $.parseJSON(result);
            cl(menu_item);

            $(menu_item).each( function(index, item) {
                $("select#select_menu_style").append('<option value="menu_style_'+ item + '" id="menu_style_'+ item +'"> #' + item + '</option>');
                /*
                var terms = item.terms;
                $("select#select_menu_style").append('<option value="menu_style_'+ item.id + '" id="menu_style_'+ item.id +'"> #' + item.id + '</option>');
                terms.map( function(class_name) {
                    $("select#select_menu_style option#menu_style_" + item.id).addClass(class_name);
                });
                */
            });
        });
    }

    // load_menu_style_data();

    function resetAllForms() {
        var forms = ['submit_form_property_data_text', 'submit_form_property_data_textarea', 'submit_form_property_data_date',
            'submit_form_property_data_numeric', 'submit_form_property_data_autoincrement', 'submit_form_ranking'];
        $(forms).each(function(idx, el){
            document.getElementById(el).reset();
            var cur = "#" + el;
            $(cur + " .data-widget").hide();
            $(cur + " #color_field_property_search").hide();
        });
    }

    $('#submit_delete_property').submit(function (e) {
        e.preventDefault();
        $("#modal_remove_property").modal('hide');
        $('#modalImportMain').modal('show');
        var form_data = $(this).serialize();

        $.ajax({
            url: src + '/controllers/property/property_controller.php',
            type: 'POST',
            data: form_data
        }).done(function (result) {
            $('#modalImportMain').modal('hide');
            elem = jQuery.parseJSON(result);

            if ( elem != null ) {
                list_collection_metadata();
                getRequestFeedback(elem.type, elem.msg);
            }            
        });
    });

    function list_collection_metadata() {
        var fixed_meta = $("ul#metadata-container .fixed-meta");
        $("ul#metadata-container").html('').append(fixed_meta);

        list_property_data();
        list_property_terms();
        list_property_object();
        list_ranking();
    }

    function get_category_root_name(id) {
        $.ajax({
            type: "POST",
            url: $('#src').val() + "/controllers/category/category_controller.php",
            data: {operation: 'get_category_root_name', category_id: id}
        }).done(function (result) {
            elem_first = jQuery.parseJSON(result);
            $("#socialdb_property_term_root").html('').append('<option selected="selected" value="' + elem_first.key + '">' + elem_first.title + '</option>');
        });
    }

    function showMetadataSchema() {
        $("#show_metadata_link").hide('slow');
        $("#hide_metadata_link").show('slow');
        $(".categories_menu").show('slow');
    }

    function hideMetadataSchema() {
        $("#properties_tabs").hide('slow');
        $("#hide_metadata_link").hide('slow');
        $("#show_metadata_link").show('slow');
    }

    function nextStep() {
        showRankingConfiguration('<?php echo get_template_directory_uri() ?>');
    }

    function nextRankingStep() {
        // $('#configuration').hide();
        // $('#configuration').html('');
        showSearchConfiguration('<?php echo get_template_directory_uri() ?>');
    }

    function showPersonalizeRanking() {
        $("#show_ranking_link").hide('slow');
        $("#hide_ranking_link").show('slow');
        $(".categories_menu").show('slow');
    }

    function hidePersonalizeRanking() {
        $("#ranking_tabs").hide('slow');
        $("#hide_ranking_link").hide('slow');
        $("#show_ranking_link").show('slow');
    }

    function getRequestFeedback(status, error_msg) {
        if (status === 'success' || status === 'true' ) {
            $("#alert_error_properties").hide();
            $("#alert_success_properties").show();
        } else {
            $("#alert_success_properties").hide();
            $("#alert_error_properties").show();

            if ( error_msg != null ) {
                $("#default_message_error").hide();
                $("#message_category").html( error_msg ).show();
            }
        }
        setTimeout(function(){
            $('.action-messages').fadeOut('slow');
        }, 3000);
    }

    function get_metadata_widgets(meta_type) {
        var $search_data_widget = $( "#meta-" + meta_type + ' #search_data_widget');
        $($search_data_widget).html('');

        if ( meta_type == 'numeric' || meta_type == 'date' ) {
            $($search_data_widget)
                .append('<option value="range"> ' + '<?php _e('Range', 'tainacan') ?>' + ' </option>')
                .append('<option value="from_to">' + '<?php _e('From/To', 'tainacan') ?>' + '</option>');
        } else if(meta_type == 'socialdb_property_object') {
            $("#meta-relationship #search_data_widget").html('')
                .append('<option value="multipleselect"> ' + '<?php _e('Multiple Select', 'tainacan') ?>' + ' </option>')
                .append('<option value="tree"> ' + '<?php _e('Tree', 'tainacan') ?>' + ' </option>');
        } else {
            $($search_data_widget)
                .append('<option value="searchbox">' + '<?php _e('Search box with autocomplete', 'tainacan') ?>' + '</option>')
                .append('<option value="tree">' + '<?php _e('Tree', 'tainacan') ?>' + '</option>')
                .append('<option value="cloud">' + '<?php _e('Tag Cloud', 'tainacan') ?>' + '</option>');
        }

        $('#range_submit').hide();
    }

    function select_tree_color( modal ) {
        var which_widget = $( modal + " #search_data_widget").val();
        if(which_widget == 'tree') {
            $(modal + " #color_field_property_search").show();
        } else {
            $(modal + " #color_field_property_search").hide();
        }
    }

    function showTermsDynatree(src) {
        $("#terms_dynatree").dynatree({
            checkbox: true,
            // Override class name for checkbox icon:
            classNames: {checkbox: "dynatree-radio"},
            selectMode: 1,
            selectionVisible: true, // Make sure, selected nodes are visible (expanded).
            initAjax: {
                url: src + '/controllers/category/category_controller.php',
                data: {
                    collection_id: $("#collection_id").val(),
                    operation: 'initDynatreeTerms'
                }
                , addActiveKey: true
            },
            onLazyRead: function (node) {
                node.appendAjax({
                    url: src + '/controllers/category/category_controller.php',
                    data: {
                        collection_id: $("#collection_id").val(),
                        category_id: node.data.key,
                        classCss: node.data.addClass,
                        //hide_checkbox: 'true',
                        operation: 'findDynatreeChild'
                    }
                });
                $('.dropdown-toggle').dropdown();
            },
            onClick: function (node, event) {
                // Close menu on click
//                $.ajax({
//                    type: "POST",
//                    url: $('#src').val() + "/controllers/category/category_controller.php",
//                    data: {collection_id: $('#collection_id').val(), operation: 'verify_has_children', category_id: node.data.key}
//                }).done(function (result) {
//                    $('.dropdown-toggle').dropdown();
//                    elem_first = jQuery.parseJSON(result);
//                    if (elem_first.type === 'error') {
//                        showAlertGeneral(elem_first.title, elem_first.msg, elem_first.type);
//                    } else {
//                        showAlertGeneral(elem_first.title, elem_first.msg, elem_first.type);
//                        $("#socialdb_property_term_root").html('');
//                        $("#socialdb_property_term_root").append('<option selected="selected" value="' + node.data.key + '">' + node.data.title + '</option>');
//
//                    }
//
//                });
            },
            onKeydown: function (node, event) {
                // Eat keyboard events, when a menu is open
                if ($(".contextMenu:visible").length > 0)
                    return false;

                switch (event.which) {

                    // Open context menu on [Space] key (simulate right click)
                    case 32: // [Space]
                        $(node.span).trigger("mousedown", {
                                preventDefault: true,
                                button: 2
                            })
                            .trigger("mouseup", {
                                preventDefault: true,
                                pageX: node.span.offsetLeft,
                                pageY: node.span.offsetTop,
                                button: 2
                            });
                        return false;

                    // Handle Ctrl-C, -X and -V
                    case 67:
                        if (event.ctrlKey) { // Ctrl-C
                            copyPaste("copy", node);
                            return false;
                        }
                        break;
                    case 86:
                        if (event.ctrlKey) { // Ctrl-V
                            copyPaste("paste", node);
                            return false;
                        }
                        break;
                    case 88:
                        if (event.ctrlKey) { // Ctrl-X
                            copyPaste("cut", node);
                            return false;
                        }
                        break;
                }
            },
            onCreate: function (node, span) {
                // bindContextMenu(span);
            },
            onPostInit: function (isReloading, isError) {
                //$('#parentCat').val("Nenhum");
                //$( "#btnExpandAll" ).trigger( "click" );
            },
            onActivate: function (node, event) {
                // Close menu on click
                if ($(".contextMenu:visible").length > 0) {
                    $(".contextMenu").hide();
                    //          return false;
                }
            },
            onSelect: function (flag, node) {
                $.ajax({
                    type: "POST",
                    url: $('#src').val() + "/controllers/category/category_controller.php",
                    data: {collection_id: $('#collection_id').val(), operation: 'verify_has_children', category_id: node.data.key}
                }).done(function (result) {
//                    console.log($("#socialdb_property_term_root").val());
                    $('.dropdown-toggle').dropdown();
                    elem_first = jQuery.parseJSON(result);
                    if (elem_first.type === 'error') {
                        showAlertGeneral(elem_first.title, elem_first.msg, elem_first.type);
                        node.select(false);
                    } else if($("#socialdb_property_term_root").val()=='null'||$("#socialdb_property_term_root").val()!=node.data.key) {
                        showAlertGeneral(elem_first.title, elem_first.msg, elem_first.type);
                        $("#socialdb_property_term_root").html('');
                        $("#socialdb_property_term_root").append('<option selected="selected" value="' + node.data.key + '">' + node.data.title + '</option>');

                    }

                });
            },
            dnd: {
                preventVoidMoves: true, // Prevent dropping nodes 'before self', etc.
                revert: false, // true: slide helper back to source if drop is rejected
                onDragStart: function (node) {
                    /** This function MUST be defined to enable dragging for the tree.*/

//                    logMsg("tree.onDragStart(%o)", node);
                    if (node.data.isFolder) {
                        return false;
                    }
                    return true;
                },
                onDragStop: function (node) {
//                    logMsg("tree.onDragStop(%o)", node);
                },
                onDragEnter: function (node, sourceNode) {
                    if (node.parent !== sourceNode.parent)
                        return false;
                    return ["before", "after"];
                },
                onDrop: function (node, sourceNode, hitMode, ui, draggable) {
                    sourceNode.move(node, hitMode);
                }
            }
        });
    }

    function hide_fields(e){
        if($(e).val()==='autoincrement'){
            $('#default_field').hide();
            $('#required_field').hide();
        }else{
            $('#default_field').show();
            $('#required_field').show();
        }
    }

    function set_voting_widget(item) {
        var ranking_type = $(item).val();
        var $widget_select = $("#meta-voting #search_data_widget");

        $("#meta-voting #search_data_widget option").remove();
        if ( ranking_type === 'stars' ) {
            $widget_select.append('<option value="stars"><?php _e('Stars','tainacan'); ?></option>');
            $("#submit_form_ranking #range_submit").hide();
        } else {
            $("#submit_form_ranking #range_submit").show();
            $widget_select.append('<option value="range"><?php _e('Range','tainacan'); ?></option><option value="from_to"><?php _e('From/To','tainacan'); ?></option>');
        }
    }

    function define_voting_widget(ranking_type) {
        //var ranking_type = $(item).val();
        var $widget_select = $("#meta-voting #search_data_widget");

        $("#meta-voting #search_data_widget option").remove();
        if ( ranking_type === 'range' ) {
            $("#meta-voting #submit_form_ranking #range_submit").show();
        } else if ( ranking_type === 'stars' ) {
            $widget_select.append('<option value="stars"><?php _e('Stars','tainacan'); ?></option>');
        } else {
            $widget_select.append('<option value="range"><?php _e('Range','tainacan'); ?></option><option value="from_to"><?php _e('From/To','tainacan'); ?></option>');
        }
    }

    function toggle_widget(el) {
        if ( el.checked) {
            $('.use-voting-filter').show('fast');
            $("#range_submit").fadeIn();
        } else {
            $('.use-voting-filter').hide();
            $("#range_submit").fadeOut();
        }
    }

    function toggle_range_submit(el) {
        ($(el).val() == "range") ? $("#range_submit").fadeIn() : $("#range_submit").fadeOut();
    }

    function clear_relation() {
        $("#property_object_category_id").val('');
        $("#property_object_category_name").val('');
    }

    function hide_alert() { $(".alert").hide(); }

    function clear_buttons() {
        $('#show_reverse_properties').hide();
        $("#property_data_title").text('<?php _e('Add new property','tainacan') ?>');
        $("#property_data_id").val('');
        $("#property_data_name").val('');
        $("#property_data_widget").val('');
        $("#property_data_column_ordenation_false").prop('checked', true);
        $("#property_data_required_false").prop('checked', true);
        $("#property_object_title").text('<?php _e('Add new property','tainacan') ?>');
        $("#property_object_id").val('');
        $("#property_object_name").val('');
        $("#property_object_category_id").val('');
        $('#socialdb_property_default_value').val('');
        $("#property_object_facet_false").prop('checked', true);
        $("#property_object_is_reverse_false").prop('checked', true);
        $("#property_object_required_false").prop('checked', true);

        $("#property_term_title").text('<?php _e('Add new property','tainacan') ?>');
        $("#property_term_id").val('');
        $("#property_term_name").val('');

        $('#default_field').show();
        $('#required_field').show();

        $("#operation_property_data").val('add_property_data');
        $("#operation_property_object").val('add_property_object');
        $("#operation_property_term").val('add_property_term');
        $('#submit_form_property_term').parents('form').find('input[type=text],textarea,select').filter(':visible').val('');
    }

    function showPropertyCategoryDynatree(src) {
        $("#property_category_dynatree").dynatree({
            selectionVisible: true, // Make sure, selected nodes are visible (expanded).
            checkbox: true,
            initAjax: {
                url: src + '/controllers/category/category_controller.php',
                data: {
                    collection_id: $("#collection_id").val(),
                    operation: 'initPropertyDynatree'
                }
                , addActiveKey: true
            },
            onLazyRead: function (node) {
                node.appendAjax({
                    url: src + '/controllers/category/category_controller.php',
                    data: {
                        collection_id: $("#collection_id").val(),
                        category_id: node.data.key,
                        operation: 'findDynatreeChild'
                    }
                });
            },
            onClick: function (node, event) {
                // Close menu on click
                $("#property_object_category_id").val(node.data.key);
                $("#property_object_category_name").val(node.data.title);

            },
            onKeydown: function (node, event) {
                // Eat keyboard events, when a menu is open
                if ($(".contextMenu:visible").length > 0)
                    return false;

                switch (event.which) {

                    // Open context menu on [Space] key (simulate right click)
                    case 32: // [Space]
                        $(node.span).trigger("mousedown", {
                                preventDefault: true,
                                button: 2
                            })
                            .trigger("mouseup", {
                                preventDefault: true,
                                pageX: node.span.offsetLeft,
                                pageY: node.span.offsetTop,
                                button: 2
                            });
                        return false;

                    // Handle Ctrl-C, -X and -V
                    case 67:
                        if (event.ctrlKey) { // Ctrl-C
                            copyPaste("copy", node);
                            return false;
                        }
                        break;
                    case 86:
                        if (event.ctrlKey) { // Ctrl-V
                            copyPaste("paste", node);
                            return false;
                        }
                        break;
                    case 88:
                        if (event.ctrlKey) { // Ctrl-X
                            copyPaste("cut", node);
                            return false;
                        }
                        break;
                }
            },
            onCreate: function (node, span) {
            },
            onPostInit: function (isReloading, isError) {
                //$( "#btnExpandAll" ).trigger( "click" );
            },
            onActivate: function (node, event) {
                // Close menu on click
                if ($(".contextMenu:visible").length > 0) {
                    $(".contextMenu").hide();
                }
            },
            onSelect: function (flag, node) {
            },
            dnd: {
                preventVoidMoves: true, // Prevent dropping nodes 'before self', etc.
                revert: false, // true: slide helper back to source if drop is rejected
                onDragStart: function (node) {
                    if (node.data.isFolder) {
                        return false;
                    }
                    return true;
                },
                onDragStop: function (node) {
                    // logMsg("tree.onDragStop(%o)", node);
                },
                onDragEnter: function (node, sourceNode) {
                    if (node.parent !== sourceNode.parent)
                        return false;
                    return ["before", "after"];
                },
                onDrop: function (node, sourceNode, hitMode, ui, draggable) {
                    sourceNode.move(node, hitMode);
                }
            }
        });
    }

    /**
     ****************************************************************************
     ************** UNUSED CODE -  REVISE BEFORE DELETE *************************
     ****************************************************************************
     **/
    /*

     // let the gallery items be draggable
     $( "li", $gallery ).draggable({
     cancel: "a.ui-icon", // clicking an icon won't initiate dragging
     revert: "invalid", // when not dropped, the item will revert back to its initial position
     containment: "document",
     helper: "clone",
     cursor: "move"
     });

     // let the trash be droppable, accepting the gallery items
     $trash.droppable({
     accept: "#metadata-container > li",
     activeClass: "ui-state-highlight",
     drop: function( event, ui ) {
     deleteImage( ui.draggable );
     }
     });

     // let the gallery be droppable as well, accepting items from the trash
     $gallery.droppable({
     accept: "#filters-accordion li",
     activeClass: "custom-state-active",
     drop: function( event, ui ) {
     recycleImage( ui.draggable );
     }
     });
     }
     */

    /*
     var recycle_icon = "<a href='link/to/recycle/script/when/we/have/js/off' title='Recycle this image' class='ui-icon ui-icon-refresh'>Recycle image</a>";
     function deleteImage( $item ) {
     $item.fadeOut(function() {
     var $list = $( "ul", $trash ).length ?
     $( "ul", $trash ) :
     $( "<ul class='gallery ui-helper-reset'/>" ).appendTo( $trash );

     $item.find( "a.ui-icon-trash" ).remove();
     $item.append( recycle_icon ).appendTo( $list ).fadeIn(function() {
     $item
     .animate({ width: "100%" })
     .find( "img" )
     .animate({ height: "36px" });
     });
     });
     }
     */

    /*
     var trash_icon = "<a href='link/to/trash/script/when/we/have/js/off' title='Delete this image' class='ui-icon ui-icon-trash'>Delete image</a>";
     function recycleImage( $item ) {
     $item.fadeOut(function() {
     $item.find( "a.ui-icon-refresh" ).remove().end()
     .append( trash_icon ).find( "img" ).css( "height", "72px" )
     .end().appendTo( $gallery ).fadeIn();
     });
     }

     // resolve the icons behavior with event delegation
     $( "ul.gallery > li" ).click(function( event ) {
     var $item = $( this ),
     $target = $( event.target );

     if ( $target.is( "a.ui-icon-trash" ) ) {
     deleteImage( $item );
     } else if ( $target.is( "a.ui-icon-zoomin" ) ) {
     viewLargerImage( $target );
     } else if ( $target.is( "a.ui-icon-refresh" ) ) {
     recycleImage( $item );
     }
     });
     */
</script>