<div>
    <button
            type='button'
            class='btn btn-primary'
            data-toggle='modal'
            data-target='#logs_overlay_modal'
            style="display: none;"
    >
        Dummy button
    </button>

    <div class="modal fade" id="logs_overlay_modal" role="dialog">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Informação do registo
                    </h5>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn waves-effect waves-light grey" id="download_log_btn">
                        Descarregar
                    </button>
                    <button type="button" class="btn waves-effect waves-light" data-dismiss="modal">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
