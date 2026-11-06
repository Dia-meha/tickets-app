<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="/ticket_delete.php">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="deleteTicketId" value="">
            <div class="modal-header">
                <h2 class="modal-title h5" id="deleteModalLabel">Supprimer ce ticket ?</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Vous allez supprimer définitivement le ticket :</p>
                <p class="fw-semibold mb-0" id="deleteTicketTitle"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </div>
        </form>
    </div>
</div>
