<?php

namespace App\Repositories;

use App\Models\DireccionModel;
use App\Models\PropiedadModel;
use App\Models\TipoInmuebleModel;
use App\Models\UsuarioModel;
use CodeIgniter\Database\BaseConnection;

class PropiedadRepository
{
    private BaseConnection $db;

    public function __construct(
        private ?PropiedadModel $model = null,
        private ?DireccionModel $direcciones = null,
        private ?TipoInmuebleModel $tipos = null,
        private ?UsuarioModel $usuarios = null,
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? db_connect();
        $this->model ??= new PropiedadModel();
        $this->direcciones ??= new DireccionModel();
        $this->tipos ??= new TipoInmuebleModel();
        $this->usuarios ??= new UsuarioModel();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarTipos(): array
    {
        return $this->tipos->orderBy('nombre', 'ASC')->findAll();
    }

    public function existeTipo(int $idTipoInmueble): bool
    {
        return $this->tipos
            ->where('id_tipo_inmueble', $idTipoInmueble)
            ->countAllResults() > 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarPropietariosActivos(): array
    {
        return $this->usuarios
            ->select('id_usuario, nombre_completo, mail')
            ->where('rol', 'propietario')
            ->where('estado', 'activo')
            ->orderBy('nombre_completo', 'ASC')
            ->findAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarPropietarios(): array
    {
        return $this->usuarios
            ->select('id_usuario, nombre_completo, mail, estado')
            ->where('rol', 'propietario')
            ->orderBy('nombre_completo', 'ASC')
            ->findAll();
    }

    public function esPropietarioActivo(int $idUsuario): bool
    {
        return $this->usuarios
            ->where('id_usuario', $idUsuario)
            ->where('rol', 'propietario')
            ->where('estado', 'activo')
            ->countAllResults() > 0;
    }

    public function existeAliasParaPropietario(
        string $alias,
        int $idPropietario,
        ?int $idPropiedadExcluir = null,
    ): bool
    {
        $consulta = $this->model
            ->where('alias', $alias)
            ->where('id_propietario', $idPropietario);

        if ($idPropiedadExcluir !== null) {
            $consulta->where('id_propiedad !=', $idPropiedadExcluir);
        }

        return $consulta->countAllResults() > 0;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function crearDireccion(array $datos): int
    {
        $resultado = $this->direcciones->insert($datos, true);

        return $resultado === false ? 0 : (int) $resultado;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): int
    {
        $resultado = $this->model->insert($datos, true);

        return $resultado === false ? 0 : (int) $resultado;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizar(int $idPropiedad, array $datos): bool
    {
        return $this->model->update($idPropiedad, $datos);
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizarDireccion(int $idDireccion, array $datos): bool
    {
        return $this->direcciones->update($idDireccion, $datos);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->model->find($id) ?: null;
    }

    public function bloquearParaVinculacion(int $idPropiedad): bool
    {
        return $this->db->query(
            'SELECT id_propiedad FROM propiedad WHERE id_propiedad = ? FOR UPDATE',
            [$idPropiedad],
        )->getNumRows() === 1;
    }

    /**
     * @param array{ciudad: string, tipo: string, estado: string, q: string, propietario: string} $filtros
     * @return array{inmuebles: list<array<string, mixed>>, pager: \CodeIgniter\Pager\PagerInterface|null}
     */
    public function listar(array $filtros, int $pagina, ?int $idPropietario): array
    {
        $consulta = $this->model
            ->select('propiedad.*, direccion.calle, direccion.numero, direccion.piso, direccion.depto, direccion.ciudad, tipo_inmueble.nombre AS tipo_nombre, usuario.nombre_completo AS propietario_nombre')
            ->join('direccion', 'direccion.id_direccion = propiedad.id_direccion')
            ->join('tipo_inmueble', 'tipo_inmueble.id_tipo_inmueble = propiedad.id_tipo_inmueble')
            ->join('usuario', 'usuario.id_usuario = propiedad.id_propietario');

        if ($idPropietario !== null) {
            $consulta->where('propiedad.id_propietario', $idPropietario);
        } elseif ($filtros['propietario'] !== '') {
            $consulta->where('propiedad.id_propietario', (int) $filtros['propietario']);
        }

        if ($filtros['estado'] === '') {
            $consulta->where('propiedad.estado !=', 'inactivo');
        } else {
            $consulta->where('propiedad.estado', $filtros['estado']);
        }

        if ($filtros['ciudad'] !== '') {
            $consulta->like('direccion.ciudad', $filtros['ciudad']);
        }

        if ($filtros['tipo'] !== '') {
            $consulta->where('tipo_inmueble.nombre', $filtros['tipo']);
        }

        if ($filtros['q'] !== '') {
            $consulta
                ->groupStart()
                ->like('propiedad.alias', $filtros['q'])
                ->orLike('direccion.calle', $filtros['q'])
                ->orLike('direccion.ciudad', $filtros['q'])
                ->groupEnd();
        }

        $inmuebles = $consulta
            ->orderBy('propiedad.id_propiedad', 'DESC')
            ->paginate(12, 'default', $pagina);

        return ['inmuebles' => $inmuebles, 'pager' => $this->model->pager];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorIdConDireccion(int $idPropiedad): ?array
    {
        return $this->model
            ->select('propiedad.*, direccion.calle, direccion.numero, direccion.piso, direccion.depto, direccion.ciudad, tipo_inmueble.nombre AS tipo_nombre, usuario.nombre_completo AS propietario_nombre')
            ->join('direccion', 'direccion.id_direccion = propiedad.id_direccion')
            ->join('tipo_inmueble', 'tipo_inmueble.id_tipo_inmueble = propiedad.id_tipo_inmueble')
            ->join('usuario', 'usuario.id_usuario = propiedad.id_propietario')
            ->where('propiedad.id_propiedad', $idPropiedad)
            ->first() ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function bloquearParaServicios(int $idPropiedad): ?array
    {
        return $this->db->query(
            'SELECT id_propiedad, id_propietario, estado FROM propiedad WHERE id_propiedad = ? FOR UPDATE',
            [$idPropiedad],
        )->getRowArray() ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listarServicios(int $idPropiedad): array
    {
        return $this->db->table('propiedad_servicio')
            ->select(
                'propiedad_servicio.id_propiedad_servicio, propiedad_servicio.estado, '
                . 'tipo_servicio.tipo, tipo_servicio.proveedor_predeterminado',
            )
            ->join(
                'tipo_servicio',
                'tipo_servicio.id_tipo_servicio = propiedad_servicio.id_tipo_servicio',
            )
            ->where('propiedad_servicio.id_propiedad', $idPropiedad)
            ->orderBy('propiedad_servicio.id_propiedad_servicio', 'DESC')
            ->get()
            ->getResultArray();
    }
}
