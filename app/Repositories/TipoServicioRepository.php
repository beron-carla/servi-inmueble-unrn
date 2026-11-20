<?php

namespace App\Repositories;

use App\Models\TipoServicioModel;
use CodeIgniter\Database\BaseConnection;

class TipoServicioRepository
{
    private BaseConnection $db;

    public function __construct(
        private ?TipoServicioModel $model = null,
        ?BaseConnection $db = null,
    )
    {
        $this->model ??= new TipoServicioModel();
        $this->db = $db ?? db_connect();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->model->find($id) ?: null;
    }

    public function tieneServiciosVinculados(int $id): bool
    {
        return $this->db->table('propiedad_servicio')
            ->where('id_tipo_servicio', $id)
            ->countAllResults() > 0;
    }

    public function contarPropiedadesVinculadas(int $id): int
    {
        $fila = $this->db->query(
            'SELECT COUNT(DISTINCT id_propiedad) AS cantidad
             FROM propiedad_servicio
             WHERE id_tipo_servicio = ?',
            [$id],
        )->getRowArray();

        return (int) ($fila['cantidad'] ?? 0);
    }

    public function eliminar(int $id): bool
    {
        if ($this->tieneServiciosVinculados($id)) {
            return false;
        }

        return $this->model->delete($id) !== false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarActivosParaVinculacion(): array
    {
        return $this->model
            ->select('id_tipo_servicio, tipo, categoria, unidad_medida, proveedor_predeterminado')
            ->where('activo', 1)
            ->orderBy('tipo', 'ASC')
            ->orderBy('proveedor_predeterminado', 'ASC')
            ->findAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarActivoPorId(int $id): ?array
    {
        return $this->model
            ->where('id_tipo_servicio', $id)
            ->where('activo', 1)
            ->first() ?: null;
    }

    public function existePorTipoYProveedor(string $tipo, string $proveedor): bool
    {
        return $this->model
            ->where('tipo', $tipo)
            ->where('proveedor_predeterminado', $proveedor)
            ->countAllResults() > 0;
    }

    public function existeOtroPorTipoYProveedor(string $tipo, string $proveedor, int $excluirId): bool
    {
        return $this->model
            ->where('tipo', $tipo)
            ->where('proveedor_predeterminado', $proveedor)
            ->where('id_tipo_servicio <>', $excluirId)
            ->countAllResults() > 0;
    }

    /**
     * @param array{q: string, categoria: string, activo: string} $filtros
     * @return array{tipos: list<array<string, mixed>>, pager: \CodeIgniter\Pager\PagerInterface|null}
     */
    public function listarParaAdministracion(array $filtros, int $pagina): array
    {
        $this->aplicarFiltros($filtros);

        if ($filtros['activo'] !== '') {
            $this->model->where('activo', (int) $filtros['activo']);
        }

        return $this->paginar($pagina);
    }

    /**
     * @param array{q: string, categoria: string, activo: string} $filtros
     * @return array{tipos: list<array<string, mixed>>, pager: \CodeIgniter\Pager\PagerInterface|null}
     */
    public function listarActivos(array $filtros, int $pagina): array
    {
        $this->model->where('activo', 1);
        $this->aplicarFiltros($filtros);

        return $this->paginar($pagina);
    }

    /**
     * @param array{tipo: string, categoria: string, unidad_medida: string, proveedor_predeterminado: string, activo: int} $datos
     */
    public function crear(array $datos): bool
    {
        return $this->model->insert($datos) !== false;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizar(int $id, array $datos): bool
    {
        return $this->model->update($id, $datos);
    }

    /**
     * @param array{q: string, categoria: string, activo: string} $filtros
     */
    private function aplicarFiltros(array $filtros): void
    {
        if ($filtros['categoria'] !== '') {
            $this->model->where('categoria', $filtros['categoria']);
        }

        if ($filtros['q'] !== '') {
            $this->model
                ->groupStart()
                ->like('tipo', $filtros['q'])
                ->orLike('proveedor_predeterminado', $filtros['q'])
                ->groupEnd();
        }
    }

    /**
     * @return array{tipos: list<array<string, mixed>>, pager: \CodeIgniter\Pager\PagerInterface|null}
     */
    private function paginar(int $pagina): array
    {
        $tipos = $this->model
            ->orderBy('tipo', 'ASC')
            ->orderBy('proveedor_predeterminado', 'ASC')
            ->paginate(20, 'default', $pagina);

        return [
            'tipos' => $tipos,
            'pager' => $this->model->pager,
        ];
    }
}
