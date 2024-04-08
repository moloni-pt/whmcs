if (pt === undefined) {
    var pt = {};
}

if (pt.moloni === undefined) {
    pt.moloni = {};
}

if (pt.moloni.Logs === undefined) {
    pt.moloni.Logs = {};
}

pt.moloni.Logs = (function ($) {
    var currentPageAction;
    var logs = [];

    function init(_currentPageAction) {
        currentPageAction = _currentPageAction;

        startObservers();
    }

    function startObservers() {
        var datatable = $('.moloniTable');

        datatable
            .on('preXhr.dt', disableTable) // https://datatables.net/reference/event/preXhr
            .dataTable({
                "processing": true,
                "serverSide": true,
                "bStateSave": false,
                "order": [[ 0, 'desc' ]],
                "ajax": {
                    "url": currentPageAction,
                    "data": {
                        "ajax": true,
                    }
                },
                "columns": [
                    {
                        data: 'created_at',
                        orderable: true,
                    },
                    {
                        data: 'log_level',
                        orderable: false,
                        render: renderLevelCol,
                    },
                    {
                        data: 'message',
                        defaultContent: '',
                        orderable: false,
                    },
                    {
                        data: 'context',
                        orderable: false,
                        render: renderContextCol,
                    },
                ],
                "columnDefs": [
                    {
                        className: "center-align",
                        targets: [1, 3]
                    },
                ],
                "fnDrawCallback": enableTable, // https://datatables.net/reference/option/drawCallback
                "searchDelay": 2000,
                "lengthMenu": [10, 25, 50, 75],
                "pageLength": 10,
                "sPaginationType": "simple_numbers",
                "sDom": '<"top"<"MolSearch"f><"MolShowing"l>>rt<"bottom row"<"MolInfo col s6"i><"MolPagination col s6"p>><"clear">',
                "language": {
                    "sLengthMenu": "_MENU_",
                    "sZeroRecords": "Sem resultados encontrados",
                    "sInfo": "A mostrar <b>_START_</b> - <b>_END_</b> de <b>_TOTAL_</b> registos",
                    "sInfoEmpty": "Sem resultados para apresentar",
                    "sInfoFiltered": "(Filtrados de _MAX_)",
                    "sSearch": "",
                    "sSearchPlaceholder": "Pesquisar...",
                    "oPaginate": {
                        "sPrevious": "Anterior",
                        "sNext": "Seguinte",
                    }
                }
            });

        // Duct tape to fix, multiple ajax requests while searching.
        // Only searches when pressing "enter"
        $('.dataTables_filter input')
            .off('')
            .bind('keyup', function (e) {
                if (e.keyCode !== 13) {
                    return;
                }

                datatable.fnFilter($(this).val());
            });
    }

    //       PRIVATES       //

    function disableTable() {
        $('.moloniTable').addClass('dataTable--disabled');

        logs = [];
    }

    function enableTable() {
        $('.moloniTable').removeClass('dataTable--disabled');
    }

    function openLogOverlay(logId) {
        var overlay = $("#logs_overlay_modal");
        var overlayBtn = $("[data-target='#logs_overlay_modal']");
        var content = '';

        content += '<pre class="logs-content">';
        content += logs[logId];
        content += '</pre>';

        overlay.find('.modal-body').html(content);
        overlay.find('.modal-footer')
            .find('#download_log_btn')
            .off('click')
            .on('click', function () {
                downloadLogOverlay(logId);
            });

        overlayBtn.trigger('click');
    }

    function downloadLogOverlay(logId) {
        var element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(logs[logId]));
        element.setAttribute('download', 'log.txt');
        element.style.display = 'none';

        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }

    //       RENDERS       //

    function renderLevelCol(data, type, row, meta) {
        var cssClass = '';
        var labelText = '';

        switch (data) {
            case 'debug':
                cssClass = 'grey white-text';
                labelText = 'Debug';

                break;
            case 'warning':
                cssClass = 'yellow black-text';
                labelText = 'Alerta';

                break;
            case 'critical':
                cssClass = 'red white-text';
                labelText = 'Crítico';

                break;
            case 'error':
                cssClass = 'red white-text';
                labelText = 'Erro';

                break;
            default:
            case 'info':
                cssClass = 'blue white-text';
                labelText = 'Informativo';

                break;
        }

        var html = "";

        html += "<span class='badge " + cssClass + "'>";
        html += labelText;
        html += "</span>";

        return html;
    }

    function renderContextCol(data, type, row, meta) {
        data = data || '{}';
        data = JSON.parse(data);

        var logId = row.id;
        var html = "";

        html += "<button type='button' class='waves-effect waves-light btn-small' onclick='pt.moloni.Logs.openLogOverlay(" + logId + ");'>";
        html += "Ver";
        html += "</button>";

        logs[logId] = JSON.stringify(data || {}, null, 2);

        return html;
    }

    return {
        init: init,
        openLogOverlay: openLogOverlay,
    }
}(jQuery));
