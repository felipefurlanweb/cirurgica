<?php   include_once 'header.php'; ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h4 class="mb-0">Cidades</h4>
  <div class="d-flex gap-2">
    <a href="form_cidade.php" class="btn btn-primary">+ Nova Cidade</a>
    <a href="index.php" class="btn btn-outline-secondary">Voltar</a>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table id="tabela-cidades" class="table table-striped table-hover align-middle" style="width:100%">
        <thead class="table-light">
          <tr>
            <th>Cidade</th>
            <th style="width:90px">UF</th>
            <th style="width:110px">Urgente</th>
            <th style="width:110px">Coleta</th>
            <th style="width:160px">Ações</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<script>
$(function () {
  const table = $('#tabela-cidades').DataTable({
    serverSide: true,
    processing: true,
    responsive: true,
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50, 100],
    order: [[1, 'asc']], // Cidade
    ajax: {
      url: 'dt_cidades.php',
      type: 'POST'
    },
    columns: [
      { data: 'nome' },
      { data: 'uf', className: 'text-nowrap' },
      { data: 'urgente_html', orderData: 3, className: 'text-nowrap' },
      { data: 'coleta_html',  orderData: 4, className: 'text-nowrap' },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-nowrap',
        render: function (data, type, row) {
          const id = row.id;
          return `
            <div class="btn-group btn-group-sm" role="group">
              <a class="btn btn-warning" href="edit_cidade.php?id=${id}">Editar</a>
              <a class="btn btn-danger" href="del_cidade.php?id=${id}" onclick="return confirm('Tem certeza que deseja remover esta cidade?')">Excluir</a>
            </div>
          `;
        }
      }
    ],
    language: {
      url: 'https://cdn.datatables.net/plug-ins/2.0.8/i18n/pt-BR.json'
    }
  });
});
</script>

<?php include_once 'footer.php'; ?>
